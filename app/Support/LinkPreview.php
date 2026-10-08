<?php

namespace App\Support;

use Illuminate\Http\Request;

class LinkPreview
{
    /**
     * Chats that build a card from the first HTML response.
     *
     * @var list<string>
     */
    private const AGENTS = [
        'WhatsApp',
        'facebookexternalhit',
        'Facebot',
        'Twitterbot',
        'LinkedInBot',
        'Slackbot',
        'TelegramBot',
        'Discordbot',
        'Pinterest',
        'redditbot',
        'vkShare',
        'Iframely',
        'Embedly',
    ];

    public static function isUnfurler(Request $request): bool
    {
        $agent = $request->userAgent() ?? '';

        foreach (self::AGENTS as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }
}
