<?php

declare(strict_types=1);

namespace SistemaComercio\Gateway;

use RuntimeException;

class HttpProxy
{
    public function get(string $url, string $requestId, string $token): array
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('No se pudo iniciar la conexión con la instancia.');
        }

        $headers = [
            'Accept: application/json',
            'X-Request-Id: ' . $requestId,
        ];

        if ($token !== '') {
            $headers[] = 'X-Gateway-Token: ' . $token;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status === 0) {
            throw new RuntimeException('La instancia no respondió: ' . $error);
        }

        json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('La instancia respondió con contenido que no es JSON.');
        }

        return ['status' => $status, 'body' => $body];
    }
}
