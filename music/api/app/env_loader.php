<?php
/**
 * Minimal .env loader for BusyOwlFramework / music.hitune.in
 *
 * Loads a .env file from the project root into $_ENV and $_SERVER.
 * Supports simple KEY=VALUE lines, ignores comments/blank lines.
 * Does NOT overwrite existing environment variables.
 */

if (!function_exists('bof_load_env')) {
    function bof_load_env(string $path): void
    {
        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and section headers
            if ($line === '' || $line[0] === '#' || $line[0] === '[') {
                continue;
            }

            // Only accept simple KEY=VALUE assignments
            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding quotes if present
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key === '' || isset($_ENV[$key])) {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    function bof_env(string $key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return $default;
    }

    function bof_env_bool(string $key, bool $default = false): bool
    {
        $value = bof_env($key, $default ? 'true' : 'false');
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    function bof_env_int(string $key, int $default = 0): int
    {
        $value = bof_env($key, (string) $default);
        return is_numeric($value) ? (int) $value : $default;
    }
}

// Auto-load root .env on include
$envPath = dirname(dirname(dirname(__FILE__))) . '/.env';
bof_load_env($envPath);
