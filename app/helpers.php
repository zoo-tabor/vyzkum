<?php
declare(strict_types=1);

use App\Core\View;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @param array<string, mixed> $data */
function view(string $template, array $data = []): string
{
    return View::render($template, $data);
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function back(string $fallback = '/admin'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // Only follow same-host referers to avoid open-redirect.
    if (is_string($referer) && $referer !== '') {
        $host = parse_url($referer, PHP_URL_HOST);
        if ($host === null || $host === ($_SERVER['HTTP_HOST'] ?? null)) {
            redirect($referer);
        }
    }
    redirect($fallback);
}

function url(string $path): string
{
    return $path === '' ? '/' : $path;
}

function asset(string $path): string
{
    $base = defined('ASSET_BASE_PATH') ? ASSET_BASE_PATH : '';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function input(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function old(string $key, mixed $default = ''): string
{
    return e((string) ($_POST[$key] ?? $default));
}
