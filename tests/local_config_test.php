<?php

declare(strict_types=1);

use SistemaComercio\Support\LocalConfig;

require_once dirname(__DIR__) . '/SistemaComercio/Support/autoload.php';

$path = sys_get_temp_dir() . '/config-local-test-' . bin2hex(random_bytes(8)) . '.php';

try {
    file_put_contents(
        $path,
        "<?php return ['SC_TEST_NAME' => 'pc-a', 'SC_TEST_PRIORITY' => 'archivo'];"
    );
    putenv('SC_TEST_NAME');
    putenv('SC_TEST_PRIORITY=entorno');

    LocalConfig::apply($path);

    if (getenv('SC_TEST_NAME') !== 'pc-a' || getenv('SC_TEST_PRIORITY') !== 'entorno') {
        throw new RuntimeException('No se aplicó la configuración local o su prioridad.');
    }

    echo "LOCAL_CONFIG_TEST_OK\n";
} finally {
    putenv('SC_TEST_NAME');
    putenv('SC_TEST_PRIORITY');

    if (is_file($path)) {
        unlink($path);
    }
}
