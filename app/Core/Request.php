<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @param array<string, string> $params route parameters */
    public function __construct(private array $params = [])
    {
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->params[$key] ?? $default;
    }

    /** @return array<string, string> */
    public function params(): array
    {
        return $this->params;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        return (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    }

    public function ip(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) ? $ip : null;
    }
}
