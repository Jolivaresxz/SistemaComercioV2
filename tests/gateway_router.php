<?php

declare(strict_types=1);

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$root = dirname(__DIR__);

if (preg_match('~^/registry/(register|heartbeat|unregister|discover|instances)/?$~', $path, $match)) {
    $_GET['action'] = $match[1];
    require $root . '/registry.php';
    return;
}

if (preg_match('~^/api(?:/(.*))?/?$~', $path, $match)) {
    $_GET['path'] = $match[1] ?? '';
    require $root . '/api.php';
    return;
}

return false;
