<?php

declare(strict_types=1);

namespace gldstdlib;

use gldstdlib\exception\GLDException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

use function gldstdlib\safe\json_decode;

/**
 * Client voor openai via http. De community client in Composer heeft PHP 8.2
 * nodig.
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
            'timeout'  => 60.0,
        ]);
    }

    /**
     * Voer een openai query uit met een structured response.
     *
     * @param $system_input generieke instructies.
     * @param $user_input specifieke vraag of door openai te beoordelen content
     * @param array<mixed> $format specificatie van JSON-formaat
     * @param $model model
     *
     * @return string Het respons van de api als string. Dit zou in het correcte
     * structured response moeten zijn en kan dan als JSON geparsed geworden.
     *
     * @throws GLDException on non-2xx or unexpected payload
     */
    public function get_struct_response(
        string $system_input,
        string $user_input,
        array $format,
        string $model,
    ): string {
        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => $system_input,
                ],
                [
                    'role' => 'user',
                    'content' => $user_input,
                ],
            ],
            'text' => [
                'format' => $format,
            ],
        ];

        try {
            $response = $this->http->request('POST', 'responses', [
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new GLDException("HTTP request to OpenAI failed: {$e->getMessage()}", 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new GLDException("OpenAI API returned status {$status}");
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
        throw new GLDException("Ongeldig respons: \"{$response->getBody()}\"");
    }
}
