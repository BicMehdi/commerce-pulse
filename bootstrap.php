<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) require $path;
});
if (is_file(BASE_PATH . '/.env')) {
    foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) && getenv($key) === false) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}
date_default_timezone_set('UTC');
require BASE_PATH . '/app/helpers.php';
