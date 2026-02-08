<?php
declare(strict_types=1);

namespace Admin\Services;

class SlugService
{
    /**
     * Zet een titel om naar een URL-vriendelijke slug.
     * Bijvoorbeeld: "Mijn Eerste Post!" -> "mijn-eerste-post"
     */
    public function slugify(string $text): string
    {
        // 1. Zet alles om naar kleine letters
        $text = strtolower($text);

        // 2. Vervang alles wat geen letter of cijfer is door een streepje
        // [^a-z0-9] alles wat NIET a-z of 0-9 is.
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);

        // 3. Verwijder streepjes aan het begin en einde (trim)
        // Bv: "-hallo-" wordt "hallo"
        return trim($text, '-');
    }
}