<?php

declare(strict_types=1);

namespace gldstdlib\tests;

use gldstdlib\exception\GLDException;
use gldstdlib\OpenAIClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class OpenAIClientTest extends TestCase
{
    private function get_schema(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'get_struct_response_test',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'is_geldig' => [
                        'type' => 'boolean',
                    ],
                    'artiest' => [
                        'type' => ['string', 'null'],
                    ],
                    'titel' => [
                        'type' => ['string', 'null'],
                    ],
                ],
                'required' => ['is_geldig', 'titel', 'artiest'],
                'additionalProperties' => false,
            ],
            "strict" => true,
        ];
    }

    public function test_get_struct_response_success_gpt_no_reasoning(): void
    {
        $test_json = \json_encode([
            'is_geldig' => false,
            'artiest' => 'a',
            'titel' => 't',
        ]);
        $e_test_json = \json_encode($test_json);
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                <<<EOT
                {
                    "output": [
                        {
                            "id": "msg_68b027e2540081918d6bd3472c9e157e0d73313127fcabed",
                            "type": "message",
                            "status": "completed",
                            "content": [
                                {
                                    "type": "output_text",
                                    "annotations": [],
                                    "logprobs": [],
                                    "text": {$e_test_json}
                                }
                            ],
                            "role": "assistant"
                        }
                    ]
                }
                EOT
            ),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $api = new OpenAIClient('fake-key', $client);

        $data = $api->get_struct_response(
            'system instruction',
            'input',
            $this->get_schema(),
            "gpt-4.1-mini",
        );
        $this->assertJsonStringEqualsJsonString($test_json, $data);
    }

    public function test_get_struct_response_success_reasoning(): void
    {
        $test_json = \json_encode([
            'is_geldig' => false,
            'artiest' => 'a',
            'titel' => 't',
        ]);
        $e_test_json = \json_encode($test_json);
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                <<<EOT
                {
                    "output": [
                        {
                            "id": "rs_68b020a4f4388190bf715bccd0567b1008cfce3fea2cfbfa",
                            "type": "reasoning",
                            "summary": []
                        },
                        {
                            "id": "msg_68b020a885408190bc5f2b125ac888b508cfce3fea2cfbfa",
                            "type": "message",
                            "status": "completed",
                            "content": [
                                {
                                    "type": "output_text",
                                    "annotations": [],
                                    "logprobs": [],
                                    "text": {$e_test_json}
                                }
                            ],
                            "role": "assistant"
                        }
                    ]
                }
                EOT
            ),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $api = new OpenAIClient('fake-key', $client);

        $data = $api->get_struct_response(
            'system instruction',
            'input',
            $this->get_schema(),
            "gpt-5-nano",
        );
        $this->assertJsonStringEqualsJsonString($test_json, $data);
    }

    public function testGetResponseNon2xxThrowsGLDException(): void
    {
        // Arrange: mock a 500 response
        $mock = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'boom'])),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $api = new OpenAIClient('fake-key', $client);

        // Assert (expect exception)
        $this->expectException(GLDException::class);

        // Act
        $api->get_struct_response(
            'system instruction',
            'input',
            $this->get_schema(),
            "gpt-4.1-mini",
        );
    }
}
