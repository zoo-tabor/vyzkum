<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\LocaleService;

final class LocaleController
{
    /** Prepnuti jazyka a navrat zpet (verejne, bez prihlaseni). */
    public function switch(string $lang): string
    {
        LocaleService::switchTo($lang);

        // Navrat na lokalni cestu z ?r=..., jinak podle Referer (back).
        $target = (string) input('r');
        if ($target !== '' && $target[0] === '/' && !str_starts_with($target, '//')) {
            redirect($target);
        }
        back('/');
    }
}
