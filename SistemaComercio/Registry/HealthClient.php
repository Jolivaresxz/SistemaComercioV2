<?php

declare(strict_types=1);

namespace SistemaComercio\Registry;

use RuntimeException;

class HealthClient
{
    public function probe(string $baseUrl): array
    {
        $curl = curl_init(rtrim($baseUrl, '/') . '/health.php');

        if ($curl === false) {
            throw new RuntimeException('No se pudo iniciar la comprobación de salud.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status !== 200) {
            throw new RuntimeException(
                'La instancia no respondió correctamente: ' . ($error ?: 'HTTP ' . $status)
            );
        }

        $health = json_decode($body, true);

        if (
            !is_array($health)
            || ($health['status'] ?? null) !== 'ok'
            || ($health['service'] ?? null) !== 'sistema-comercio'
            || !is_string($health['instanceId'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($health['datasetHash'] ?? ''))
        ) {
            throw new RuntimeException('La respuesta de salud de la instancia no es válida.');
        }

        return $health;
    }
}
