<?php

declare(strict_types=1);

namespace SistemaComercio\Registry;

use RuntimeException;
use SistemaComercio\Gateway\Balancer\StrategyFactory;

/**
 * El Registry completo vive en un archivo JSON. flock evita que dos peticiones
 * modifiquen el archivo a la vez durante esta práctica de aula.
 */
class RegistryStore
{
    public function __construct(private string $path)
    {
        if ($path === '' || !is_dir(dirname($path))) {
            throw new RuntimeException('Configure REGISTRY_FILE_PATH en una carpeta existente.');
        }

        // Crea el JSON la primera vez y detecta un archivo antiguo o dañado.
        $this->access(static fn (array &$data): null => null, true);
    }

    public function register(string $instanceId, string $baseUrl, string $datasetHash): array
    {
        return $this->access(function (array &$data) use ($instanceId, $baseUrl, $datasetHash): array {
            $expected = $this->expectedFrom($data);

            if ($expected === null) {
                $expected = $datasetHash;
                $data['expectedDatasetHash'] = $datasetHash;
            }

            $previous = $data['instances'][$instanceId] ?? null;
            $status = hash_equals($expected, $datasetHash) ? 'UP' : 'MISMATCH';
            $now = time();
            $data['instances'][$instanceId] = [
                'instance_id' => $instanceId,
                'service_name' => 'sistema-comercio',
                'base_url' => $baseUrl,
                'dataset_hash' => $datasetHash,
                'status' => $status,
                'last_seen_at' => $now,
                'active_requests' => (int) ($previous['active_requests'] ?? 0),
                'created_at' => (int) ($previous['created_at'] ?? $now),
            ];

            return [
                'instanceId' => $instanceId,
                'status' => $status,
                'datasetHash' => $datasetHash,
                'expectedDatasetHash' => $expected,
            ];
        }, true);
    }

    public function getInstance(string $instanceId): ?array
    {
        return $this->access(
            static fn (array &$data): ?array => $data['instances'][$instanceId] ?? null
        );
    }

    public function unregister(string $instanceId): bool
    {
        return $this->access(static function (array &$data) use ($instanceId): bool {
            if (!isset($data['instances'][$instanceId])) {
                return false;
            }

            $data['instances'][$instanceId]['status'] = 'DOWN';
            return true;
        }, true);
    }

    public function markDown(string $instanceId): void
    {
        $this->unregister($instanceId);
    }

    public function discover(int $maxAgeSeconds = 45): array
    {
        return $this->access(static function (array &$data) use ($maxAgeSeconds): array {
            $minimum = time() - $maxAgeSeconds;
            $instances = array_values(array_filter(
                $data['instances'],
                static fn (array $instance): bool =>
                    $instance['status'] === 'UP'
                    && (int) $instance['last_seen_at'] >= $minimum
            ));
            usort($instances, static fn (array $a, array $b): int =>
                strcmp($a['instance_id'], $b['instance_id']));

            return array_map(static fn (array $instance): array => [
                'instance_id' => $instance['instance_id'],
                'service_name' => $instance['service_name'],
                'base_url' => $instance['base_url'],
                'dataset_hash' => $instance['dataset_hash'],
                'active_requests' => $instance['active_requests'],
                'last_seen_at' => $instance['last_seen_at'],
            ], $instances);
        });
    }

    public function listInstances(int $maxAgeSeconds = 45): array
    {
        return $this->access(static function (array &$data) use ($maxAgeSeconds): array {
            $instances = array_values($data['instances']);
            usort($instances, static fn (array $a, array $b): int =>
                strcmp($a['instance_id'], $b['instance_id']));

            foreach ($instances as &$instance) {
                if (
                    $instance['status'] === 'UP'
                    && (int) $instance['last_seen_at'] < time() - $maxAgeSeconds
                ) {
                    $instance['status'] = 'STALE';
                }
            }
            unset($instance);

            return array_map(static fn (array $instance): array => [
                'instance_id' => $instance['instance_id'],
                'service_name' => $instance['service_name'],
                'base_url' => $instance['base_url'],
                'dataset_hash' => $instance['dataset_hash'],
                'status' => $instance['status'],
                'active_requests' => $instance['active_requests'],
                'last_seen_at' => $instance['last_seen_at'],
            ], $instances);
        });
    }

    public function reserve(
        string $attemptId,
        string $clientIp,
        string $route,
        string $algorithm,
        string $hashKey,
        array $excludedIds = []
    ): ?array {
        return $this->access(static function (array &$data) use (
            $attemptId, $clientIp, $route, $algorithm, $hashKey, $excludedIds
        ): ?array {
            $now = time();

            foreach ($data['requests'] as &$request) {
                if ($request['status'] === 'ACTIVE' && $request['started_at'] < $now - 30) {
                    $request['status'] = 'TIMEOUT';
                    $request['finished_at'] = $now;
                    $request['error_message'] = 'La petición no finalizó a tiempo.';
                }
            }
            unset($request);

            foreach ($data['instances'] as &$instance) {
                $instance['active_requests'] = 0;
            }
            unset($instance);

            foreach ($data['requests'] as $request) {
                if (
                    $request['status'] === 'ACTIVE'
                    && isset($data['instances'][$request['instance_id']])
                ) {
                    $data['instances'][$request['instance_id']]['active_requests']++;
                }
            }

            $candidates = array_values(array_filter(
                $data['instances'],
                static fn (array $instance): bool =>
                    $instance['status'] === 'UP'
                    && $instance['last_seen_at'] >= $now - 45
                    && !in_array($instance['instance_id'], $excludedIds, true)
            ));

            if ($candidates === []) {
                return null;
            }

            if (isset($data['requests'][$attemptId])) {
                throw new RuntimeException('La petición ya está registrada.');
            }

            $selected = StrategyFactory::make($algorithm)->select(
                $candidates, $clientIp, $hashKey
            );
            $id = $selected['instance_id'];
            $data['instances'][$id]['active_requests']++;
            $data['requests'][$attemptId] = [
                'request_id' => $attemptId,
                'client_ip' => $clientIp,
                'route' => $route,
                'algorithm' => $algorithm,
                'instance_id' => $id,
                'status' => 'ACTIVE',
                'started_at' => $now,
                'finished_at' => null,
                'duration_ms' => null,
                'http_status' => null,
                'error_message' => null,
            ];
            $selected['active_requests'] = $data['instances'][$id]['active_requests'];

            return $selected;
        }, true);
    }

    public function finish(
        string $attemptId,
        string $status,
        int $httpStatus,
        int $durationMs,
        ?string $errorMessage = null
    ): void {
        if (!in_array($status, ['COMPLETED', 'FAILED'], true)) {
            throw new RuntimeException('Estado de petición inválido.');
        }

        $this->access(static function (array &$data) use (
            $attemptId, $status, $httpStatus, $durationMs, $errorMessage
        ): null {
            if (
                !isset($data['requests'][$attemptId])
                || $data['requests'][$attemptId]['status'] !== 'ACTIVE'
            ) {
                return null;
            }

            $request = &$data['requests'][$attemptId];
            $request['status'] = $status;
            $request['finished_at'] = time();
            $request['duration_ms'] = $durationMs;
            $request['http_status'] = $httpStatus;
            $request['error_message'] = $errorMessage;
            $id = $request['instance_id'];
            unset($request);

            if (isset($data['instances'][$id])) {
                $data['instances'][$id]['active_requests'] = max(
                    0, $data['instances'][$id]['active_requests'] - 1
                );
            }

            // La práctica solo necesita historial reciente; evita un JSON enorme.
            if (count($data['requests']) > 250) {
                $finished = array_filter($data['requests'], static fn (array $item): bool =>
                    $item['status'] !== 'ACTIVE');
                uasort($finished, static fn (array $a, array $b): int =>
                    ($b['started_at'] <=> $a['started_at'])
                    ?: strcmp($b['request_id'], $a['request_id']));
                foreach (array_slice(array_keys($finished), 200) as $oldId) {
                    unset($data['requests'][$oldId]);
                }
            }

            return null;
        }, true);
    }

    public function recentRequests(int $limit = 50): array
    {
        return $this->access(static function (array &$data) use ($limit): array {
            $requests = array_values($data['requests']);
            usort($requests, static fn (array $a, array $b): int =>
                ($b['started_at'] <=> $a['started_at'])
                ?: strcmp($b['request_id'], $a['request_id']));

            return array_slice($requests, 0, max(1, min($limit, 200)));
        });
    }

    public function expectedHash(): ?string
    {
        return $this->access(fn (array &$data): ?string => $this->expectedFrom($data));
    }

    private function expectedFrom(array $data): ?string
    {
        $configured = trim((string) getenv('EXPECTED_DATASET_HASH'));

        if ($configured !== '') {
            if (!preg_match('/^[a-f0-9]{64}$/', $configured)) {
                throw new RuntimeException('EXPECTED_DATASET_HASH debe ser SHA-256.');
            }

            return $configured;
        }

        return $data['expectedDatasetHash'];
    }

    private function access(callable $action, bool $write = false): mixed
    {
        $file = @fopen($this->path, 'c+');

        if ($file === false) {
            throw new RuntimeException('No se pudo abrir el archivo JSON del Registry.');
        }

        try {
            if (!flock($file, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear el archivo del Registry.');
            }

            rewind($file);
            $text = stream_get_contents($file);

            if ($text === false) {
                throw new RuntimeException('No se pudo leer el archivo del Registry.');
            }

            $data = trim($text) === ''
                ? ['expectedDatasetHash' => null, 'instances' => [], 'requests' => []]
                : json_decode($text, true, 512, JSON_THROW_ON_ERROR);

            if (
                !is_array($data)
                || !array_key_exists('expectedDatasetHash', $data)
                || !is_array($data['instances'] ?? null)
                || !is_array($data['requests'] ?? null)
            ) {
                throw new RuntimeException('El archivo del Registry no tiene un formato válido.');
            }

            $result = $action($data);

            if ($write) {
                $json = json_encode(
                    $data,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                    | JSON_THROW_ON_ERROR
                ) . "\n";
                rewind($file);

                if (!ftruncate($file, 0)) {
                    throw new RuntimeException('No se pudo actualizar el Registry.');
                }

                $offset = 0;

                while ($offset < strlen($json)) {
                    $written = fwrite($file, substr($json, $offset));

                    if ($written === false || $written === 0) {
                        throw new RuntimeException('No se pudo guardar el Registry.');
                    }

                    $offset += $written;
                }

                fflush($file);
            }

            return $result;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
