<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Futurecrmagent_llm_client
{
    public function chat_tool_json(array $messages, array $tools, float $temperature = 0.2): array
    {
        $toolInstruction = [
            'role' => 'system',
            'content' => 'You may request CRM tools only by returning JSON with keys answer, tool_requests, action_plan, risks, missing_info. tool_requests must be an array of objects with name and arguments. Do not invent tools outside the provided catalog.',
        ];

        $messages = array_merge([$toolInstruction], $messages, [[
            'role' => 'user',
            'content' => json_encode([
                'available_tools' => $tools,
                'tool_policy' => [
                    'max_tool_turns' => 6,
                    'write_tools_require_approval' => true,
                    'raw_reasoning_hidden' => true,
                ],
            ]),
        ]]);

        return $this->chat_json($messages, $temperature);
    }

    public function chat_json(array $messages, float $temperature = 0.2): array
    {
        $baseUrl = rtrim((string) get_option('futureagent_proxy_base_url'), '/');
        $apiKey = (string) get_option('futureagent_proxy_api_key');
        $model = (string) get_option('futureagent_default_chat_model');
        $timeout = (int) get_option('futureagent_proxy_timeout_seconds');

        if ($timeout <= 0) {
            $timeout = 45;
        } elseif ($timeout < 45) {
            $timeout = 45;
        }

        if ($baseUrl === '' || $apiKey === '' || $model === '') {
            return [
                'ok' => false,
                'error' => 'FutureAgent proxy base URL, API key, and default model are required.',
            ];
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init($baseUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $safeRequest = $body;
        $safeRequest['authorization'] = 'Bearer ***';

        if ($raw === false) {
            return [
                'ok' => false,
                'error' => $curlError ?: 'LLM proxy request failed.',
                'request' => $safeRequest,
                'status' => $status,
            ];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'LLM proxy returned invalid JSON.',
                'request' => $safeRequest,
                'raw' => $raw,
                'status' => $status,
            ];
        }

        if ($status < 200 || $status >= 300) {
            return [
                'ok' => false,
                'error' => $decoded['error']['message'] ?? ('LLM proxy HTTP ' . $status),
                'request' => $safeRequest,
                'body' => $decoded,
                'status' => $status,
            ];
        }

        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $json = json_decode((string) $content, true);
        if (!is_array($json)) {
            $start = strpos((string) $content, '{');
            $end = strrpos((string) $content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $json = json_decode(substr((string) $content, $start, $end - $start + 1), true);
            }
        }

        if (!is_array($json)) {
            return [
                'ok' => false,
                'error' => 'LLM response content was not valid JSON.',
                'request' => $safeRequest,
                'body' => $decoded,
                'content' => $content,
                'status' => $status,
            ];
        }

        return [
            'ok' => true,
            'request' => $safeRequest,
            'body' => $decoded,
            'json' => $json,
            'content' => $content,
            'usage' => $decoded['usage'] ?? null,
            'status' => $status,
        ];
    }
}
