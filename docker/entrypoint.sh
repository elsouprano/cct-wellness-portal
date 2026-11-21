#!/usr/bin/env sh
set -eu

cd /var/www/html

export PORT="${PORT:-10000}"

# Ensure required writable directories exist with proper ownership
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Render and validate Nginx configuration for Render's dynamic $PORT
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
nginx -t

# Ensure public/storage symlink resolves cleanly
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Rebuild Laravel caches without requiring a database connection
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Temporary non-secret DB/SSL diagnostic running as www-data (stderr -> Render Logs)
su -p -s /bin/sh www-data -c 'php' >&2 <<'DIAG_EOF' || true
<?php
$caPath = getenv('MYSQL_ATTR_SSL_CA') ?: '';
$host = getenv('DB_HOST') ?: '';
$port = (int) (getenv('DB_PORT') ?: 3306);
$db = getenv('DB_DATABASE') ?: '';
$user = getenv('DB_USERNAME') ?: '';
$pass = getenv('DB_PASSWORD');
$pass = $pass === false ? '' : $pass;

$report = [
    'diag' => 'db_ssl_preflight',
    'uid' => function_exists('posix_geteuid') ? posix_geteuid() : null,
    'ca_configured' => $caPath !== '',
    'ca_basename' => $caPath !== '' ? basename($caPath) : null,
    'ca_exists' => $caPath !== '' ? file_exists($caPath) : false,
    'ca_readable' => $caPath !== '' ? is_readable($caPath) : false,
    'ca_size' => ($caPath !== '' && is_file($caPath)) ? @filesize($caPath) : null,
    'ca_mode' => ($caPath !== '' && file_exists($caPath)) ? substr(sprintf('%o', @fileperms($caPath)), -4) : null,
    'x509_valid' => false,
    'x509_valid_to' => null,
    'tcp_ok' => false,
    'tcp_errno' => null,
    'ssl_verify_option_supported' => defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT'),
    'pdo_ssl_ok' => false,
    'ssl_cipher' => null,
    'mysql_version' => null,
    'db_exists' => null,
    'pdo_db_select_1' => false,
    'pdo_code' => null,
    'pdo_driver_code' => null,
    'pdo_stage' => null,
];

if ($report['ca_readable']) {
    $pem = @file_get_contents($caPath);
    $parsed = $pem !== false ? @openssl_x509_parse($pem) : false;
    if (is_array($parsed)) {
        $report['x509_valid'] = true;
        $report['x509_valid_to'] = $parsed['validTo_time_t'] ?? null;
    }
}

if ($host !== '' && $port > 0) {
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5);
    if ($fp !== false) {
        $report['tcp_ok'] = true;
        fclose($fp);
    } else {
        $report['tcp_errno'] = $errno;
    }

    if ($user !== '') {
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ];
        if ($caPath !== '') {
            $opts[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        }
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }
        try {
            $report['pdo_stage'] = 'server_connect';
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, $opts);
            $report['mysql_version'] = $pdo->query('SELECT VERSION()')->fetchColumn();
            $cipherRow = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
            $report['ssl_cipher'] = $cipherRow[1] ?? null;
            $report['pdo_ssl_ok'] = ! empty($report['ssl_cipher']);
            if ($db !== '') {
                $report['pdo_stage'] = 'schema_check';
                $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
                $stmt->execute([$db]);
                $report['db_exists'] = (bool) $stmt->fetchColumn();
                $report['pdo_stage'] = 'db_connect';
                $pdoDb = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, $opts);
                $report['pdo_db_select_1'] = ((int) $pdoDb->query('SELECT 1')->fetchColumn()) === 1;
            }
        } catch (PDOException $e) {
            $report['pdo_code'] = $e->getCode();
            $report['pdo_driver_code'] = $e->errorInfo[1] ?? null;
        }
    }
}

fwrite(STDERR, json_encode($report, JSON_UNESCAPED_SLASHES) . PHP_EOL);
DIAG_EOF

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
