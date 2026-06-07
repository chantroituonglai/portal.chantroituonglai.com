<?php

namespace Perfexcrm\Futurecrmagent;

use app\services\ai\Contracts\AiProviderInterface;

defined('BASEPATH') or exit('No direct script access allowed');

class FuturecrmagentProvider implements AiProviderInterface
{
    public function getName(): string
    {
        return 'FutureCRM Agent';
    }

    public static function getModels(): array
    {
        $model = (string) get_option('futureagent_default_chat_model');

        return $model !== '' ? [$model] : [];
    }

    public function chat($prompt): string
    {
        $client = new \Futurecrmagent_llm_client();
        $result = $client->chat_json([
            [
                'role' => 'system',
                'content' => 'Return valid JSON only.',
            ],
            [
                'role' => 'user',
                'content' => (string) $prompt,
            ],
        ]);

        if (empty($result['ok'])) {
            throw new \RuntimeException($result['error'] ?? 'FutureCRM Agent provider request failed.');
        }

        return (string) ($result['content'] ?? json_encode($result['json']));
    }

    public function enhanceText(string $text, string $type): string
    {
        $client = new \Futurecrmagent_llm_client();
        $result = $client->chat_json([
            [
                'role' => 'system',
                'content' => 'Improve CRM text for the requested purpose. Return JSON with key "text" only.',
            ],
            [
                'role' => 'user',
                'content' => json_encode(['type' => $type, 'text' => $text]),
            ],
        ]);

        if (empty($result['ok'])) {
            return $text;
        }

        return (string) ($result['json']['text'] ?? $text);
    }
}
