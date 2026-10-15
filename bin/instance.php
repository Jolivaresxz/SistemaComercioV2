<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/SistemaComercio/Support/autoload.php';

$action = $argv[1] ?? '';

if (!in_array($action, ['register', 'heartbeat', 'unregister', 'watch'], true)) {
    fwrite(STDERR, "Uso: php bin/instance.php register|heartbeat|unregister|watch\n");
    exit(2);
}

$registryUrl = rtrim(trim((string) getenv('REGISTRY_URL')), '/');
$instanceId = trim((string) getenv('INSTANCE_ID'));
$token = trim((string) getenv('REGISTRY_TOKEN'));
$baseUrl = rtrim(trim((string) getenv('INSTANCE_BASE_URL')), '/');

if ($registryUrl === '' || $instanceId === '' || $token === '') {
    fwrite(STDERR, "Configure REGISTRY_URL, INSTANCE_ID y REGISTRY_TOKEN.\n");
    exit(2);
}

if (in_array($action, ['register', 'watch'], true) && $baseUrl === '') {
    fwrite(STDERR, "Configure INSTANCE_BASE_URL antes de registrar.\n");
    exit(2);
}

$send = static function (string $requestAction) use (
    $registryUrl,
    $instanceId,
    $token,
    $baseUrl
): bool {
    $payload = ['instanceId' => $instanceId];

    if ($requestAction === 'register') {
        $payload['baseUrl'] = $baseUrl;
    }

    $curl = curl_init($registryUrl . '/' . $requestAction);

    if ($curl === false) {
        fwrite(STDERR, "No se pudo iniciar la conexión con el Registry.\n");
        return false;
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Registry-Token: ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 8,
    ]);

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        fwrite(STDERR, "No se pudo contactar al Registry: {$error}\n");
        return false;
    }

    echo "HTTP {$status}: {$body}\n";
    return $status >= 200 && $status < 300;
};

if ($action === 'watch') {
    while (!$send('register')) {
        sleep(15);
    }

    while (true) {
        sleep(15);

        if (!$send('heartbeat')) {
            $send('register');
        }
    }
}

exit($send($action) ? 0 : 1);
