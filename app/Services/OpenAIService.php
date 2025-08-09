<?php
namespace App\Services;

class OpenAIService
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(array $messages, array $config = []): string
    {
        $model = $config['model'] ?? 'gpt-4o-mini';
        $temperature = $config['temperature'] ?? 0.7;
        $presence = $config['presence_penalty'] ?? 0;
        $frequency = $config['frequency_penalty'] ?? 0;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'presence_penalty' => $presence,
            'frequency_penalty' => $frequency,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);
        $res = curl_exec($ch);
        if ($res === false) {
            return 'Error contacting OpenAI.';
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($res, true);
        if ($status >= 200 && $status < 300 && isset($data['choices'][0]['message']['content'])) {
            return (string)$data['choices'][0]['message']['content'];
        }
        return 'AI response unavailable.';
    }
}