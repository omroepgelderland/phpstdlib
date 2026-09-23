<?php

declare(strict_types=1);

namespace gldstdlib;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

use function gldstdlib\safe\json_decode;

/**
 * Client voor openai via http. De community client in Composer heeft PHP 8.2
 * nodig.
 *
 * @phpstan-type EasyInputMessageType array{
 *     content: string,
 *     role: 'user'|'assistant'|'system'|'developer',
 *     phase?: 'commentary'|'final_answer'|null,
 * }
 * @phpstan-type ReasoningType array{
 *     context?: 'auto'|'current_turn'|'all_turns'|null,
 *     effort?: 'none'|'minimal'|'low'|'medium'|'high'|'xhigh'|'max'|null,
 *     mode?: string,
 *     summary?: 'auto'|'concise'|'detailed'|null,
 * }
 * @phpstan-type ResponseTextConfigType array{
 *     format: array<mixed>,
 *     verbosity?: 'low'|'medium'|'high'|null,
 * }
 */
final class OpenAIClient
{
    private readonly ClientInterface $http;

    /**
     * @param $api_key OpenAI api key
     * @param $http Mock client voor unittesten
     */
    public function __construct(string $api_key, ?ClientInterface $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers'  => [
                'Authorization' => "Bearer {$api_key}",
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Voer een openai query uit met een structured response.
     *
     * @param array{
     *     input: string|list<EasyInputMessageType>,
     *     instructions?: string,
     *     model?: string,
     *     reasoning?: ReasoningType,
     *     text?: ResponseTextConfigType,
     * } $payload The payload to send to the OpenAI API.
     *
     * @return string Het respons van de api als string. Dit zou in het correcte
     * structured response moeten zijn en kan dan als JSON geparsed geworden.
     *
     * @throws \RuntimeException on non-2xx or unexpected payload
     */
    public function get_struct_response(
        array $payload,
        int $timeout = 0,
    ): string {
        try {
            $response = $this->http->request('POST', 'responses', [
                'json' => $payload,
                'timeout' => $timeout,
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException("HTTP request to OpenAI failed: {$e->getMessage()}", 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("OpenAI API returned status {$status}");
        }

        $data = json_decode((string)$response->getBody(), true);

        if (\is_array($data) && \is_array($data['output'])) {
            $outputs = \array_filter(
                $data['output'],
                fn($v) =>
                    \is_array($v) &&
                    isset($v['type']) &&
                    $v['type'] === 'message'
            );
            $output = \array_shift($outputs);
            if (\is_array($output) && \is_array($output['content'])) {
                $contents = \array_filter(
                    $output['content'],
                    fn($v) =>
                        \is_array($v) &&
                        isset($v['type']) &&
                        $v['type'] === 'output_text'
                );
                $content = \array_shift($contents);
                if (\is_array($content) && \is_string($content['text'])) {
                    return $content['text'];
                }
            }
        }
        throw new \RuntimeException("Ongeldig respons: \"{$response->getBody()}\"");
    }
}
