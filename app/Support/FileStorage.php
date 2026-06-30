<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Ukladani nahranych souboru mimo web root, trideni plemeno/majitel/pes.
 * Soubory se prejmenovavaji; puvodni nazev se uklada do DB (files.original_name).
 */
final class FileStorage
{
    /** mime => pripona (whitelist) */
    private const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_BYTES = 10 * 1024 * 1024;

    public static function sanitize(string $value): string
    {
        $value = strtolower($value);
        $value = (string) preg_replace('/[^a-z0-9_-]+/', '-', $value);
        $value = trim($value, '-');
        return $value === '' ? 'x' : $value;
    }

    /** Relativni slozka: <plemeno>/owner_<id>/dog_<id> */
    public static function relativeDir(string $breedSlug, int $ownerId, int $dogId): string
    {
        return self::sanitize($breedSlug) . '/owner_' . $ownerId . '/dog_' . $dogId;
    }

    public static function storedName(string $category, string $ext): string
    {
        return self::sanitize($category) . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    }

    /**
     * Ulozi $_FILES polozku a vrati metadata.
     *
     * @param array<string, mixed> $file
     * @return array{relative:string, mime:string, size:int, original:string, ext:string}
     */
    public static function store(array $file, string $breedSlug, int $ownerId, int $dogId, string $category): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Nahrání souboru selhalo.');
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Soubor je příliš velký (max 10 MB).');
        }
        $mime = mime_content_type((string) $file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Nepodporovany typ souboru (povoleno PDF, JPG, PNG, WEBP).');
        }
        $ext = self::ALLOWED[$mime];

        $relativeDir = self::relativeDir($breedSlug, $ownerId, $dogId);
        $absoluteDir = STORAGE_PATH . '/uploads/' . $relativeDir;
        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new \RuntimeException('Nepodarilo se vytvorit slozku pro soubory.');
        }

        $name = self::storedName($category, $ext);
        $absolute = $absoluteDir . '/' . $name;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
            throw new \RuntimeException('Soubor se nepodarilo ulozit.');
        }

        return [
            'relative' => $relativeDir . '/' . $name,
            'mime' => $mime,
            'size' => (int) $file['size'],
            'original' => basename((string) $file['name']),
            'ext' => $ext,
        ];
    }

    public static function absolutePath(string $relative): string
    {
        return STORAGE_PATH . '/uploads/' . $relative;
    }
}
