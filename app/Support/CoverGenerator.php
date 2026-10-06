<?php

namespace App\Support;

/**
 * Génère des couvertures SVG (ratio 2:3) aux couleurs de la marque.
 */
class CoverGenerator
{
    /**
     * @var array<string, array{0: string, 1: string, 2: string}> fond haut, fond bas, accent
     */
    public const THEMES = [
        'litterature' => ['#4FCDF6', '#0A779C', '#FFFFFF'],
        'developpement-personnel' => ['#72D7F8', '#0896C5', '#FFFFFF'],
        'business' => ['#38C7F4', '#0F617F', '#FFD166'],
        'sciences' => ['#5AD0F7', '#0A779C', '#EBFAFE'],
        'education' => ['#8ADDF9', '#0896C5', '#FFF3C4'],
    ];

    public function svg(string $title, string $author, string $category, string $theme, int $seed = 0): string
    {
        [$top, $bottom, $accent] = self::THEMES[$theme] ?? self::THEMES['litterature'];

        $lines = $this->wrap($title, mb_strlen($title) > 40 ? 16 : 13);
        $fontSize = count($lines) > 3 ? 34 : 40;
        $startY = 250 - (count($lines) - 1) * $fontSize * 0.6;

        $titleSvg = '';
        foreach ($lines as $i => $line) {
            $y = round($startY + $i * $fontSize * 1.18);
            $titleSvg .= '<text x="200" y="'.$y.'" text-anchor="middle" font-family="\'Playfair Display\', Georgia, serif" font-weight="700" font-size="'.$fontSize.'" fill="#FFFFFF" filter="url(#ts)">'.$this->e($line).'</text>';
        }

        // Décor : orbites et étoiles, variés selon la graine.
        $long = mb_strlen($category) > 14;
        $catSize = $long ? 11 : 13;
        $catSpacing = $long ? 2 : 4;

        mt_srand(crc32($title) + $seed);
        $rotation = mt_rand(-35, 35);
        $cx = mt_rand(250, 360);
        $cy = mt_rand(60, 160);
        $stars = '';
        for ($i = 0; $i < 18; $i++) {
            $stars .= '<circle cx="'.mt_rand(10, 390).'" cy="'.mt_rand(10, 590).'" r="'.(mt_rand(5, 18) / 10).'" fill="#FFFFFF" fill-opacity="'.(mt_rand(15, 60) / 100).'"/>';
        }
        mt_srand();

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="600" viewBox="0 0 400 600">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0.4" y2="1">
      <stop offset="0" stop-color="{$top}"/>
      <stop offset="1" stop-color="{$bottom}"/>
    </linearGradient>
    <radialGradient id="glow" cx="{$cx}" cy="{$cy}" r="220" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="{$accent}" stop-opacity="0.55"/>
      <stop offset="1" stop-color="{$accent}" stop-opacity="0"/>
    </radialGradient>
    <filter id="ts" x="-10%" y="-30%" width="120%" height="160%"><feDropShadow dx="0" dy="1.5" stdDeviation="2.5" flood-color="#0B1F2A" flood-opacity="0.45"/></filter>
  </defs>
  <rect width="400" height="600" fill="url(#bg)"/>
  <rect width="400" height="600" fill="url(#glow)"/>
  {$stars}
  <g transform="rotate({$rotation} {$cx} {$cy})" fill="none" stroke="{$accent}" stroke-opacity="0.5">
    <ellipse cx="{$cx}" cy="{$cy}" rx="150" ry="42" stroke-width="1.4"/>
    <ellipse cx="{$cx}" cy="{$cy}" rx="105" ry="28" stroke-width="1"/>
  </g>
  <circle cx="{$cx}" cy="{$cy}" r="24" fill="{$accent}" fill-opacity="0.9"/>
  <rect x="24" y="24" width="352" height="552" rx="6" fill="none" stroke="#FFFFFF" stroke-opacity="0.25"/>
  <text x="200" y="70" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="{$catSize}" letter-spacing="{$catSpacing}" fill="{$accent}">{$this->e(mb_strtoupper($category))}</text>
  {$titleSvg}
  <rect x="170" y="430" width="60" height="3" rx="1.5" fill="{$accent}"/>
  <text x="200" y="475" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="18" fill="#FFFFFF" filter="url(#ts)">{$this->e($author)}</text>
  <text x="200" y="548" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="10" letter-spacing="3" fill="#FFFFFF" fill-opacity="0.6">UNIVERS CONNAISSANCE</text>
</svg>
SVG;
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, int $width): array
    {
        $lines = [];
        $current = '';
        foreach (preg_split('/\s+/u', trim($text)) as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($candidate) > $width && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
