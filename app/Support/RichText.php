<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Nettoie le HTML produit par l'éditeur de description (liste blanche).
 *
 * Seuls la mise en forme, les liens http(s)/mailto et les images sont conservés :
 * scripts, gestionnaires d'événements (onclick…), iframes, styles arbitraires et
 * liens « javascript: » sont supprimés. Les images collées en base64 sont
 * enregistrées comme fichiers.
 */
class RichText
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'span', 'sub', 'sup',
        'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'hr', 'a', 'img',
    ];

    /**
     * Balises supprimées avec tout leur contenu.
     */
    private const DROPPED_TAGS = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input',
        'button', 'textarea', 'select', 'option', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base', 'title', 'head',
    ];

    private const MAX_INLINE_IMAGE_BYTES = 5 * 1024 * 1024;

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim(strip_tags($html, '<img>')) === '') {
            return null;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><!DOCTYPE html><html><body><div id="rt-root">'.$html.'</div></body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('rt-root');
        if ($root === null) {
            return null;
        }

        self::cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        $out = trim($out);

        return trim(strip_tags($out, '<img>')) === '' ? null : $out;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        // Copie : la liste change pendant le parcours.
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMText) {
                // Les espaces insécables en série empêchent le retour à la ligne.
                $node->nodeValue = str_replace("\u{00A0}", ' ', $node->nodeValue);

                continue;
            }

            if (! $node instanceof DOMElement) {
                $parent->removeChild($node); // commentaires, instructions…

                continue;
            }

            $tag = strtolower($node->tagName);

            // Éléments internes de l'éditeur Quill (puces de liste).
            if (str_contains(' '.$node->getAttribute('class').' ', ' ql-ui ') || in_array($tag, self::DROPPED_TAGS, true)) {
                $parent->removeChild($node);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Balise inconnue : on garde son contenu.
                self::cleanChildren($node);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            self::cleanAttributes($node, $tag);

            if ($tag === 'img' && ! $node->hasAttribute('src')) {
                $parent->removeChild($node);

                continue;
            }

            self::cleanChildren($node);
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $keep = [];

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            $clean = match (true) {
                $name === 'style' => self::cleanStyle($value),
                $name === 'class' => self::cleanClass($value),
                $tag === 'a' && $name === 'href' => self::cleanHref($value),
                $tag === 'img' && $name === 'src' => self::cleanImageSrc($value),
                $tag === 'img' && $name === 'alt' => Str::limit($value, 200, ''),
                $tag === 'img' && in_array($name, ['width', 'height'], true) => preg_match('/^\d{1,4}$/', $value) ? $value : null,
                $tag === 'li' && $name === 'data-list' => in_array($value, ['bullet', 'ordered'], true) ? $value : null,
                default => null,
            };

            if ($clean !== null && $clean !== '') {
                $keep[$name] = $clean;
            }
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttribute($attr->name);
        }

        foreach ($keep as $name => $value) {
            $el->setAttribute($name, $value);
        }

        if ($tag === 'a' && isset($keep['href'])) {
            $el->setAttribute('rel', 'noopener nofollow');
            if (str_starts_with($keep['href'], 'http')) {
                $el->setAttribute('target', '_blank');
            }
        }

        if ($tag === 'img') {
            $el->setAttribute('loading', 'lazy');
        }
    }

    private static function cleanStyle(string $style): ?string
    {
        $color = '(#[0-9a-fA-F]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)|[a-zA-Z]{3,20})';
        $rules = [
            'color' => "/^{$color}$/",
            'background-color' => "/^{$color}$/",
            'font-size' => '/^\d{1,3}(\.\d+)?(px|em|rem|%)$/',
            'text-align' => '/^(left|right|center|justify)$/',
            'font-weight' => '/^(bold|normal|[1-9]00)$/',
            'font-style' => '/^(italic|normal)$/',
            'text-decoration' => '/^(underline|line-through|none)$/',
        ];

        $kept = [];
        foreach (explode(';', $style) as $declaration) {
            [$prop, $value] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
            $prop = strtolower($prop);
            if (isset($rules[$prop]) && preg_match($rules[$prop], $value) && ! preg_match('/expression|url\(/i', $value)) {
                $kept[] = "{$prop}: {$value}";
            }
        }

        return $kept ? implode('; ', $kept) : null;
    }

    /**
     * Classes de mise en forme de Quill uniquement (alignement, taille, retrait).
     */
    private static function cleanClass(string $class): ?string
    {
        $kept = array_filter(
            preg_split('/\s+/', $class) ?: [],
            fn ($c) => preg_match('/^ql-(align-(center|right|justify)|size-(small|large|huge)|indent-[1-8])$/', $c),
        );

        return $kept ? implode(' ', $kept) : null;
    }

    private static function cleanHref(string $href): ?string
    {
        if (preg_match('#^(https?://|mailto:)#i', $href) || preg_match('#^/(?!/)#', $href) || str_starts_with($href, '#')) {
            return $href;
        }

        return null;
    }

    private static function cleanImageSrc(string $src): ?string
    {
        if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $src, $m)) {
            return self::storeInlineImage($src, strtolower($m[1]) === 'jpg' ? 'jpeg' : strtolower($m[1]));
        }

        if (preg_match('#^https://#i', $src)) {
            return $src;
        }

        // Images téléversées sur le site (chemins relatifs ou URL du site).
        $base = rtrim(Storage::disk('public')->url(''), '/');
        if (str_starts_with($src, '/storage/') || str_starts_with($src, $base.'/')) {
            return $src;
        }

        return null;
    }

    private static function storeInlineImage(string $dataUri, string $type): ?string
    {
        $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);

        if ($binary === false || strlen($binary) > self::MAX_INLINE_IMAGE_BYTES || @getimagesizefromstring($binary) === false) {
            return null;
        }

        // Images collées : souvent des captures d'écran PNG très lourdes.
        [$binary, $extension] = ImageOptimizer::encode($binary, ImageOptimizer::CONTENT)
            ?? [$binary, $type === 'jpeg' ? 'jpg' : $type];

        $path = 'descriptions/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return Storage::disk('public')->url($path);
    }
}
