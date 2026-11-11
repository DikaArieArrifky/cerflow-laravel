<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    /**
     * Extract CER triplets from reading text using Gemini AI.
     */
    public function extract(Request $request, AIService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string'],
        ]);

        $apiKey = $aiService->resolveApiKey();
        if (empty($apiKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gemini API Key belum diatur untuk akun Anda. Silakan atur API Key di menu Pengaturan.',
            ], 422);
        }

        $triplets = $aiService->extractCER($validated['text'], $apiKey);

        if ($triplets === false) {
            $lastErr = $aiService->getLastError();
            $msg = 'Gagal mengekstraksi komponen CER. ' . ($lastErr ?: 'Pastikan teks berisi informasi ilmiah yang cukup dan API Key valid.');

            return response()->json([
                'status'  => 'error',
                'message' => $msg,
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $triplets,
        ]);
    }

    /**
     * Test Gemini API connection with provided or saved API key.
     */
    public function test(Request $request, AIService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'gemini_api_key' => ['nullable', 'string'],
        ]);

        $apiKey = $aiService->resolveApiKey($validated['gemini_api_key'] ?? null);
        if (empty($apiKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API Key tidak boleh kosong.',
            ], 422);
        }

        $result = $aiService->testConnection($apiKey);

        if (! $result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Koneksi gagal: ' . $result['message'],
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $result['message'],
        ]);
    }
}
