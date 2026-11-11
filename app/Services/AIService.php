<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class AIService
{
    private string $primaryUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent';
    private string $fallbackUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';
    private string $lastError = '';

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Resolve effective Gemini API Key.
     * Priority: explicit argument -> authenticated user -> global Setting -> config/env
     */
    public function resolveApiKey(?string $apiKey = null): ?string
    {
        if ($apiKey !== null) {
            $trimmed = trim($apiKey);
            return $trimmed !== '' ? $trimmed : null;
        }

        if (auth()->check() && ! empty(auth()->user()->gemini_api_key)) {
            return trim(auth()->user()->gemini_api_key);
        }

        $globalSetting = Setting::get('gemini_api_key');
        if (! empty($globalSetting)) {
            return trim($globalSetting);
        }

        $envKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        if (! empty($envKey)) {
            return trim($envKey);
        }

        return null;
    }

    /**
     * Test Gemini API connection
     */
    public function testConnection(?string $apiKey = null): array
    {
        $key = $this->resolveApiKey($apiKey);
        if (empty($key)) {
            return ['success' => false, 'message' => 'API Key is empty'];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Ping'],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $key,
            ])->timeout(15)->post($this->primaryUrl, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connection successful! API Key valid.'];
            }

            // Retry fallback
            $fallback = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $key,
            ])->timeout(15)->post($this->fallbackUrl, $payload);

            if ($fallback->successful()) {
                return ['success' => true, 'message' => 'Connection successful! API Key valid.'];
            }

            $errorMsg = 'API request failed (HTTP ' . $response->status() . ')';
            $json = $response->json();
            if (isset($json['error']['message'])) {
                $errorMsg = $json['error']['message'];
            }

            return ['success' => false, 'message' => $errorMsg];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Extract CER triplets from reading text
     *
     * @param string $text
     * @param string|null $apiKey
     * @return array|false
     */
    public function extractCER(string $text, ?string $apiKey = null): array|false
    {
        $key = $this->resolveApiKey($apiKey);
        if (empty($key)) {
            $this->lastError = 'Gemini API Key belum diatur untuk akun Anda. Silakan atur API Key di menu Pengaturan.';
            return false;
        }

        if (trim($text) === '') {
            $this->lastError = 'Teks bacaan tidak boleh kosong.';
            return false;
        }

        $prompt = "Extract Scientific Argumentation triplets (Claim, Evidence, Reasoning) from the following text. 
Identify and extract ALL meaningful and logically complete triplets present in the text. 
Do not invent information; if a claim lacks evidence or reasoning in the text, do not include it. Make a very short but sharply focused text for the Claim, Evidence, and Reasoning.
Use text origin's language for the extracted CER.

IMPORTANT: Your response MUST be ONLY a raw JSON array of objects. Do not include any markdown formatting, backticks, or extra text.
Each object MUST have exactly these keys: 'claim', 'evidence', 'reasoning'.

Text:
" . $text;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $key,
            ])->timeout(25)->post($this->primaryUrl, $payload);

            if (! $response->successful() && in_array($response->status(), [404, 429, 500, 503])) {
                $fallback = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $key,
                ])->timeout(30)->post($this->fallbackUrl, $payload);

                if ($fallback->successful()) {
                    $response = $fallback;
                }
            }

            if (! $response->successful()) {
                $status = $response->status();
                $json = $response->json();
                $msg = $json['error']['message'] ?? "API request failed with HTTP {$status}";
                $this->lastError = $msg;
                return false;
            }

            $result = $response->json();
            if (isset($result['candidates'][0]['content']['parts'])) {
                $fullText = '';
                foreach ($result['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $fullText .= $part['text'];
                    }
                }

                // Clean up markdown code blocks if present
                $jsonText = preg_replace('/^```(?:json)?\s*|\s*```$/is', '', trim($fullText));

                // If extra text surrounds JSON array, isolate array
                if (preg_match('/\[\s*\{.*\}\s*\]/s', $jsonText, $matches)) {
                    $jsonText = $matches[0];
                }

                $triplets = json_decode($jsonText, true);
                if (is_array($triplets)) {
                    // Normalize keys
                    $normalized = [];
                    foreach ($triplets as $t) {
                        if (is_array($t) && isset($t['claim'], $t['evidence'], $t['reasoning'])) {
                            $normalized[] = [
                                'claim'     => (string) $t['claim'],
                                'evidence'  => (string) $t['evidence'],
                                'reasoning' => (string) $t['reasoning'],
                            ];
                        }
                    }

                    if (! empty($normalized)) {
                        return $normalized;
                    }
                }
            }

            $this->lastError = 'Format respon AI tidak dapat dibaca sebagai JSON CER.';
            return false;
        } catch (\Throwable $e) {
            $this->lastError = 'Gagal menghubungi server AI: ' . $e->getMessage();
            return false;
        }
    }
}
