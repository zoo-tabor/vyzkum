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

function url(string $path): string
{
    return $path === '' ? '/' : $path;
}

function input(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}
