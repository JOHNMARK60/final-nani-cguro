<?php

declare(strict_types=1);

namespace App\Services;

final class MailTemplate
{
    public static function simple(string $title, string $body, string $ctaLabel = '', string $ctaUrl = ''): string
    {
        $cta = $ctaLabel !== '' && $ctaUrl !== ''
            ? '<p><a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '">' . htmlspecialchars($ctaLabel, ENT_QUOTES) . '</a></p>'
            : '';

        return '<html><body style="font-family:Arial,sans-serif;color:#111">' .
            '<h2>' . htmlspecialchars($title, ENT_QUOTES) . '</h2>' .
            '<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES)) . '</p>' .
            $cta .
            '</body></html>';
    }
}
