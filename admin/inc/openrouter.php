<?php

class OpenRouterClient
{
    private $apiKey;
    private $model;
    private $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct($apiKey, $model = 'google/gemini-2.5-flash')
    {
        $this->apiKey = $apiKey;
        $this->model = $model ?: 'google/gemini-2.5-flash';
    }

    public function generateBio($name, $specialty, $qualification, $experience, $type = 'bio')
    {
        if (empty($this->apiKey)) {
            throw new Exception("API Key is missing.");
        }

        $cleanName = preg_replace('/^Dr\.?\s+/i', '', $name);

        if ($type === 'motto' || $type === 'quote' || $type === 'speech') {
            // Construct patient-facing motto prompt
            $prompt = "Write a professional, inspiring, and patient-centered professional quote/motto for a doctor. " .
                      "It should reflect their medical philosophy, approach to patient care, and dedication. " .
                      "Keep the motto to exactly one short, memorable sentence (maximum 15-20 words). " .
                      "Do not include any quotes around it, and do not write in third-person (use first-person, e.g. 'I strive to...', 'My mission is...', 'Dedicated to...'). " .
                      "Do not include any commentary, just return the raw text of the motto.\n\n" .
                      "Details:\n" .
                      "- Name: Dr. " . $cleanName . "\n" .
                      "- Specialty: " . $specialty . "\n" .
                      "- Qualification: " . ($qualification ?: 'Medical credentials') . "\n" .
                      "- Experience: " . ($experience ? $experience . " years" : "Clinical practice");
            
            $systemInstruction = 'You are a professional medical copywriter writing short, inspiring patient-facing medical philosophies and mottos for healthcare practitioners. Do not output anything other than the motto itself.';
        } else {
            // Construct patient-friendly biography prompt
            $prompt = "Write a professional, patient-friendly medical biography for a doctor. " .
                      "Focus on clinical expertise, care philosophy, and patient outcomes. " .
                      "Return only the bio as plain text, no markdown headers, no quotes around it, " .
                      "and keep it under 150 words.\n\n" .
                      "Details:\n" .
                      "- Name: Dr. " . $cleanName . "\n" .
                      "- Specialty: " . $specialty . "\n" .
                      "- Qualification: " . ($qualification ?: 'Medical credentials') . "\n" .
                      "- Experience: " . ($experience ? $experience . " years" : "Clinical practice");

            $systemInstruction = 'You are a professional medical copywriter writing concise, engaging biographies for healthcare practitioners. Do not output anything other than the biography itself.';
        }

        $postData = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemInstruction
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 300
        ];

        $ch = curl_init($this->apiUrl);
        if ($ch === false) {
            throw new Exception("Failed to initialize cURL session.");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: http://localhost', // Required by OpenRouter for site analytics/tracking
            'X-Title: Doctor Profile Directory'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("CURL connection error: " . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception("API error (HTTP " . $httpCode . "): " . $response);
        }

        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }

        throw new Exception("Invalid API response format: " . $response);
    }
}
