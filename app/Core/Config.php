<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @param array<string, string> $values */
    private function __construct(private array $values)
    {
    }

    public static function load(string $path): self
    {
        $values = [];
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $values[trim($key)] = trim(trim($value), "\"'");
            }
        }

        return new self($values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            return $default;
        }

        $value = $this->values[$key];
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}
