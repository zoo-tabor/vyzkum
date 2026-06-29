<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\FilesRepository;
use App\Repositories\OwnerRepository;
use App\Services\Auth;
use App\Support\FileStorage;

final class FileDownloadController
{
    public function download(string $id): never
    {
        $file = (new FilesRepository())->find((int) $id);
        if ($file === null) {
            http_response_code(404);
            exit('Soubor nenalezen.');
        }

        if (!$this->authorized($file)) {
            http_response_code(403);
            exit('Nemate opravneni k tomuto souboru.');
        }

        $path = FileStorage::absolutePath((string) $file['stored_name']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('Soubor na disku neexistuje.');
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $file['original_name']) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    /** @param array<string, mixed> $file */
    private function authorized(array $file): bool
    {
        $role = Auth::role();
        if (in_array($role, ['research_admin', 'club_viewer'], true)) {
            return true;
        }
        // Majitel: soubory navazane na psa, ktereho vlastni (i historicky).
        if ($role === 'owner' && ($file['owner_type'] ?? '') === 'dog') {
            $owner = (new OwnerRepository())->findByUserId((int) Auth::id());
            return $owner !== null && (new OwnerRepository())->ownsDog((int) $owner['id'], (int) $file['owner_id']);
        }
        return false;
    }
}
