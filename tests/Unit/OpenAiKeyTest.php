<?php

namespace Tests\Unit;

use Tests\TestCase;

class OpenAiKeyTest extends TestCase
{
    public function test_openai_api_key_works()
    {
        $key = config('services.openai.key');
        $this->assertNotEmpty($key, 'OpenAI API key is missing!');

        $response = \Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Say hello in Arabic'],
            ],
            'max_tokens' => 20,
        ]);

        $this->assertTrue($response->successful(), 'OpenAI API did not respond successfully: ' . $response->body());
        $json = $response->json();
        $this->assertArrayHasKey('choices', $json);
        $this->assertNotEmpty($json['choices']);
        $this->assertStringContainsString('مرحبا', $json['choices'][0]['message']['content']);
    }
}
