<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIServiceTest extends TestCase
{
    protected User $guru;
    protected User $siswa;
    protected AIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiService = new AIService();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_ai_test'],
            [
                'namalengkap'    => 'Guru AI Test',
                'password'       => 'secret123',
                'role'           => 'guru',
                'gemini_api_key' => 'fake-guru-gemini-key',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_ai_test'],
            [
                'namalengkap' => 'Siswa AI Test',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );
    }

    public function test_valid_mocked_gemini_extraction(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        [
                                            'claim'     => 'Fotosintesis menghasilkan oksigen.',
                                            'evidence'  => 'Gelembung gas terkumpul pada tabung reaksi tanaman hidrila.',
                                            'reasoning' => 'Oksigen adalah produk samping reaksi terang fotosintesis.',
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $triplets = $this->aiService->extractCER('Teks percobaan Ingenhousz menunjukkan bahwa...', 'test-key-123');

        $this->assertIsArray($triplets);
        $this->assertCount(1, $triplets);
        $this->assertEquals('Fotosintesis menghasilkan oksigen.', $triplets[0]['claim']);
        $this->assertEquals('Gelembung gas terkumpul pada tabung reaksi tanaman hidrila.', $triplets[0]['evidence']);
        $this->assertEquals('Oksigen adalah produk samping reaksi terang fotosintesis.', $triplets[0]['reasoning']);
    }

    public function test_malformed_response_handling(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Bukan format json yang valid sama sekali'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->aiService->extractCER('Teks contoh', 'test-key-123');

        $this->assertFalse($result);
        $this->assertNotEmpty($this->aiService->getLastError());
    }

    public function test_api_error_handling(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code'    => 400,
                    'message' => 'API key not valid. Please pass a valid API key.',
                    'status'  => 'INVALID_ARGUMENT',
                ],
            ], 400),
        ]);

        $result = $this->aiService->extractCER('Teks percobaan', 'invalid-key');

        $this->assertFalse($result);
        $this->assertStringContainsString('API key not valid', $this->aiService->getLastError());
    }

    public function test_api_timeout_handling(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out after 25000 milliseconds');
            },
        ]);

        $result = $this->aiService->extractCER('Teks percobaan', 'test-key');

        $this->assertFalse($result);
        $this->assertStringContainsString('timed out', $this->aiService->getLastError());
    }

    public function test_missing_api_key_rejected(): void
    {
        $result = $this->aiService->extractCER('Teks bacaan', '');

        $this->assertFalse($result);
        $this->assertStringContainsString('API Key belum diatur', $this->aiService->getLastError());
    }

    public function test_api_extract_endpoint_works_for_guru(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        [
                                            'claim'     => 'Klaim Ilmiah 1',
                                            'evidence'  => 'Bukti 1',
                                            'reasoning' => 'Penalaran 1',
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->guru)->postJson(route('api.ai.extract'), [
            'text' => 'Ini adalah teks materi ilmiah yang cukup panjang untuk ekstraksi CER.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data'   => [
                [
                    'claim'     => 'Klaim Ilmiah 1',
                    'evidence'  => 'Bukti 1',
                    'reasoning' => 'Penalaran 1',
                ],
            ],
        ]);
    }

    public function test_api_test_endpoint_connection_successful(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => []], 200),
        ]);

        $response = $this->actingAs($this->guru)->postJson(route('api.ai.test'), [
            'gemini_api_key' => 'valid-test-key',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'success',
            'message' => 'Connection successful! API Key valid.',
        ]);
    }

    public function test_siswa_authorization_rejected_for_ai_endpoints(): void
    {
        // Siswa cannot call extract
        $resExtract = $this->actingAs($this->siswa)->postJson(route('api.ai.extract'), [
            'text' => 'Teks coba-coba oleh siswa',
        ]);
        $resExtract->assertStatus(403);

        // Siswa cannot call test connection
        $resTest = $this->actingAs($this->siswa)->postJson(route('api.ai.test'), [
            'gemini_api_key' => 'key',
        ]);
        $resTest->assertStatus(403);
    }
}
