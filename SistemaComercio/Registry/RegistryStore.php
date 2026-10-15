<?php

declare(strict_types=1);

namespace SistemaComercio\Registry;

use PDO;
use RuntimeException;
use SistemaComercio\Gateway\Balancer\StrategyFactory;
use Throwable;

class RegistryStore
{
    private PDO $db;

    public function __construct(string $path)
    {
        if ($path === '' || !is_dir(dirname($path))) {
            throw new RuntimeException('Configure REGISTRY_DB_PATH en un directorio existente.');
        }

        $this->db = new PDO('sqlite:' . $path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->exec('PRAGMA busy_timeout = 5000');
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS instances (
                instance_id TEXT PRIMARY KEY,
                service_name TEXT NOT NULL,
                base_url TEXT NOT NULL,
                dataset_hash TEXT NOT NULL,
                status TEXT NOT NULL,
                last_seen_at INTEGER NOT NULL,
                active_requests INTEGER NOT NULL DEFAULT 0,
                created_at INTEGER NOT NULL
            )'
        );
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS requests (
                request_id TEXT PRIMARY KEY,
                client_ip TEXT NOT NULL,
                route TEXT NOT NULL,
                algorithm TEXT NOT NULL,
                instance_id TEXT NOT NULL,
                status TEXT NOT NULL,
                started_at INTEGER NOT NULL,
                finished_at INTEGER,
                duration_ms INTEGER,
                http_status INTEGER,
                error_message TEXT
            )'
        );
    }

    public function register(string $instanceId, string $baseUrl, string $datasetHash): array
    {
        $this->db->exec('BEGIN IMMEDIATE');

        try {
            $expected = $this->expectedHash();

            if ($expected === null) {
                $expected = $datasetHash;
                $statement = $this->db->prepare(
                    'INSERT INTO settings (key, value) VALUES (?, ?)'
                );
                $statement->execute(['dataset_hash', $expected]);
            }

            $status = hash_equals($expected, $datasetHash) ? 'UP' : 'MISMATCH';
            $now = time();
            $statement = $this->db->prepare(
                'INSERT INTO instances (
                    instance_id, service_name, base_url, dataset_hash,
                    status, last_seen_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(instance_id) DO UPDATE SET
                    base_url = excluded.base_url,
                    dataset_hash = excluded.dataset_hash,
                    status = excluded.status,
                    last_seen_at = excluded.last_seen_at'
            );
            $statement->execute([
                $instanceId,
                'sistema-comercio',
                $baseUrl,
                $datasetHash,
                $status,
                $now,
                $now,
            ]);
            $this->db->exec('COMMIT');

            return [
                'instanceId' => $instanceId,
                'status' => $status,
                'datasetHash' => $datasetHash,
                'expectedDatasetHash' => $expected,
            ];
        } catch (Throwable $error) {
            $this->db->exec('ROLLBACK');
            throw $error;
        }
    }

    public function getInstance(string $instanceId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM instances WHERE instance_id = ?');
        $statement->execute([$instanceId]);
        $instance = $statement->fetch();

        return $instance === false ? null : $instance;
    }

    public function unregister(string $instanceId): bool
    {
        $statement = $this->db->prepare(
            "UPDATE instances SET status = 'DOWN' WHERE instance_id = ?"
        );
        $statement->execute([$instanceId]);

        return $statement->rowCount() > 0;
    }

    public function markDown(string $instanceId): void
    {
        $this->unregister($instanceId);
    }

    public function discover(int $maxAgeSeconds = 45): array
    {
        $statement = $this->db->prepare(
            "SELECT instance_id, service_name, base_url, dataset_hash,
                    active_requests, last_seen_at
             FROM instances
             WHERE status = 'UP' AND last_seen_at >= ?
             ORDER BY instance_id"
        );
        $statement->execute([time() - $maxAgeSeconds]);

        return $statement->fetchAll();
    }

    public function listInstances(int $maxAgeSeconds = 45): array
    {
        $instances = $this->db->query(
            'SELECT instance_id, service_name, base_url, dataset_hash,
                    status, active_requests, last_seen_at
             FROM instances ORDER BY instance_id'
        )->fetchAll();

        foreach ($instances as &$instance) {
            if (
                $instance['status'] === 'UP'
                && (int) $instance['last_seen_at'] < time() - $maxAgeSeconds
            ) {
                $instance['status'] = 'STALE';
            }
        }

        return $instances;
    }

    public function reserve(
        string $attemptId,
        string $clientIp,
        string $route,
        string $algorithm,
        string $hashKey,
        array $excludedIds = []
    ): ?array {
        $this->db->exec('BEGIN IMMEDIATE');

        try {
            $now = time();
            $stale = $this->db->prepare(
                "UPDATE requests
                 SET status = 'TIMEOUT', finished_at = ?,
                     error_message = 'La petición no finalizó a tiempo.'
                 WHERE status = 'ACTIVE' AND started_at < ?"
            );
            $stale->execute([$now, $now - 30]);
            $this->db->exec(
                "UPDATE instances SET active_requests = (
                    SELECT COUNT(*) FROM requests
                    WHERE requests.instance_id = instances.instance_id
                      AND requests.status = 'ACTIVE'
                )"
            );

            $statement = $this->db->prepare(
                "SELECT instance_id, service_name, base_url, dataset_hash,
                        active_requests, last_seen_at
                 FROM instances
                 WHERE status = 'UP' AND last_seen_at >= ?"
            );
            $statement->execute([$now - 45]);
            $instances = array_values(array_filter(
                $statement->fetchAll(),
                static fn (array $instance): bool =>
                    !in_array($instance['instance_id'], $excludedIds, true)
            ));

            if ($instances === []) {
                $this->db->exec('COMMIT');
                return null;
            }

            $selected = StrategyFactory::make($algorithm)->select(
                $instances,
                $clientIp,
                $hashKey
            );
            $increase = $this->db->prepare(
                'UPDATE instances SET active_requests = active_requests + 1
                 WHERE instance_id = ?'
            );
            $increase->execute([$selected['instance_id']]);
            $insert = $this->db->prepare(
                "INSERT INTO requests (
                    request_id, client_ip, route, algorithm, instance_id,
                    status, started_at
                ) VALUES (?, ?, ?, ?, ?, 'ACTIVE', ?)"
            );
            $insert->execute([
                $attemptId,
                $clientIp,
                $route,
                $algorithm,
                $selected['instance_id'],
                $now,
            ]);
            $this->db->exec('COMMIT');
            $selected['active_requests'] = (int) $selected['active_requests'] + 1;

            return $selected;
        } catch (Throwable $error) {
            $this->db->exec('ROLLBACK');
            throw $error;
        }
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

        $this->db->exec('BEGIN IMMEDIATE');

        try {
            $find = $this->db->prepare(
                'SELECT instance_id, status FROM requests WHERE request_id = ?'
            );
            $find->execute([$attemptId]);
            $request = $find->fetch();

            if ($request === false || $request['status'] !== 'ACTIVE') {
                $this->db->exec('COMMIT');
                return;
            }

            $update = $this->db->prepare(
                'UPDATE requests
                 SET status = ?, finished_at = ?, duration_ms = ?,
                     http_status = ?, error_message = ?
                 WHERE request_id = ?'
            );
            $update->execute([
                $status,
                time(),
                $durationMs,
                $httpStatus,
                $errorMessage,
                $attemptId,
            ]);
            $decrease = $this->db->prepare(
                'UPDATE instances
                 SET active_requests = MAX(active_requests - 1, 0)
                 WHERE instance_id = ?'
            );
            $decrease->execute([$request['instance_id']]);
            $this->db->exec('COMMIT');
        } catch (Throwable $error) {
            $this->db->exec('ROLLBACK');
            throw $error;
        }
    }

    public function recentRequests(int $limit = 50): array
    {
        $statement = $this->db->prepare(
            'SELECT request_id, client_ip, route, algorithm, instance_id,
                    status, started_at, finished_at, duration_ms,
                    http_status, error_message
             FROM requests ORDER BY started_at DESC, request_id DESC LIMIT ?'
        );
        $statement->bindValue(1, max(1, min($limit, 200)), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function expectedHash(): ?string
    {
        $configured = trim((string) getenv('EXPECTED_DATASET_HASH'));

        if ($configured !== '') {
            if (!preg_match('/^[a-f0-9]{64}$/', $configured)) {
                throw new RuntimeException('EXPECTED_DATASET_HASH debe ser SHA-256.');
            }

            return $configured;
        }

        $statement = $this->db->prepare(
            'SELECT value FROM settings WHERE key = ?'
        );
        $statement->execute(['dataset_hash']);
        $value = $statement->fetchColumn();

        return $value === false ? null : (string) $value;
    }
}
