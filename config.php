<?php
declare(strict_types=1);

function env_value(string $key, ?string $default = null): ?string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        $path = __DIR__ . '/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                $values[$name] = trim($value, "\"'");
            }
        }
    }
    return $values[$key] ?? getenv($key) ?: $default;
}

return [
    'app_name' => env_value('APP_NAME', 'TESDA Officials Directory'),
    'app_url' => rtrim((string) env_value('APP_URL', ''), '/'),
    'db' => [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '3306'),
        'name' => env_value('DB_NAME', 'tesda_directory'),
        'user' => env_value('DB_USER', 'root'),
        'pass' => env_value('DB_PASS', ''),
    ],
];

