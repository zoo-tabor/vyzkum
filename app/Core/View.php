<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT_PATH . '/app/Views/' . $template . '.php';
        $content = ob_get_clean();

        ob_start();
        require ROOT_PATH . '/app/Views/layout.php';
        return (string) ob_get_clean();
    }
}
