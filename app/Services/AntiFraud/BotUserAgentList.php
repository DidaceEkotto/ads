<?php

namespace App\Services\AntiFraud;

/**
 * Liste des User-Agents de robots connus (indexeurs, outils CLI, aperçus sociaux).
 */
class BotUserAgentList
{
    private const PREVIEW_BOTS = [
        'facebookexternalhit',
        'facebot',
        'twitterbot',
        'linkedinbot',
        'whatsapp',
        'telegrambot',
        'slackbot',
        'discordbot',
    ];

    private const GENERIC_BOTS = [
        'bot',
        'crawler',
        'spider',
        'slurp',
        'curl/',
        'wget/',
        'python-requests',
        'httpclient',
        'headlesschrome',
        'phantomjs',
        'puppeteer',
        'playwright',
        'postmanruntime',
    ];

    public static function isPreviewBot(?string $userAgent): bool
    {
        $ua = strtolower((string) $userAgent);
        if ($ua === '') {
            return false;
        }

        foreach (self::PREVIEW_BOTS as $bot) {
            if (str_contains($ua, $bot)) {
                return true;
            }
        }

        return false;
    }

    public static function isGenericBot(?string $userAgent): bool
    {
        $ua = strtolower((string) $userAgent);
        if ($ua === '') {
            return true; // User-Agent vide suspect
        }

        foreach (self::GENERIC_BOTS as $bot) {
            if (str_contains($ua, $bot)) {
                return true;
            }
        }

        return false;
    }
}
