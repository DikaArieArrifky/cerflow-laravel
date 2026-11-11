<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Triplet;
use App\Models\User;
use Tests\TestCase;

class CerStudioTest extends TestCase
{
    protected User $guru;
    protected User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_studio_test'],
            [
                'namalengkap' => 'Guru Studio Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_studio_test'],
            [
                'namalengkap' => 'Siswa Studio Test',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );
    }

    public function test_guru_can_access_studio_page(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('CER Map');
        $response->assertSee('Studio');
        $response->assertSee('api-client.js');
        $response->assertSee('admin.js');
    }

    public function test_siswa_cannot_access_studio_page(): void
    {
        $response = $this->actingAs($this->siswa)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_studio_loads_with_edit_id_parameter(): void
    {
        $map = CerMap::create([
            'title'          => 'Materi Biologi Sel 3D',
            'allow_feedback' => true,
            'allow_reading'  => true,
            'reading_text'   => 'Teks biologi sel lengkap.',
        ]);

        $triplet = Triplet::create([
            'map_id'    => $map->id,
            'claim'     => 'Sel adalah unit terkecil',
            'evidence'  => 'Pengamatan mikroskop',
            'reasoning' => 'Teori sel Schleiden & Schwann',
        ]);

        $response = $this->actingAs($this->guru)->get("/admin/dashboard?edit_id={$map->id}");

        $response->assertStatus(200);
        $response->assertSee('Materi Biologi Sel 3D');
        $response->assertSee((string) $map->id);

        // Cleanup
        $map->triplets()->delete();
        $map->delete();
    }

    public function test_create_update_and_delete_map_with_triplets_in_studio(): void
    {
        // 1. Create Map
        $createRes = $this->actingAs($this->guru)->postJson('/api/maps', [
            'title'          => 'Studio CRUD Test Map',
            'allow_feedback' => 1,
            'allow_reading'  => 1,
            'reading_text'   => 'Reading material',
            'triplets'       => [
                [
                    'claim'     => 'Klaim A',
                    'evidence'  => 'Bukti A',
                    'reasoning' => 'Alasan A',
                ],
                [
                    'claim'     => 'Klaim B',
                    'evidence'  => 'Bukti B',
                    'reasoning' => 'Alasan B',
                ],
            ],
        ]);

        $createRes->assertStatus(201);
        $mapId = $createRes->json('id');
        $this->assertNotNull($mapId);

        $createdMap = CerMap::with('triplets')->find($mapId);
        $this->assertEquals(2, $createdMap->triplets->count());
        $firstTripletId = $createdMap->triplets->first()->id;

        // 2. Update Map: Modify 1 triplet (keep ID), delete 1, add 1 new
        $updateRes = $this->actingAs($this->guru)->postJson('/api/maps', [
            'map_id'         => $mapId,
            'title'          => 'Studio CRUD Test Map - Updated',
            'allow_feedback' => 0,
            'allow_reading'  => 1,
            'reading_text'   => 'Updated reading material',
            'triplets'       => [
                [
                    'id'        => $firstTripletId,
                    'claim'     => 'Klaim A Diperbarui',
                    'evidence'  => 'Bukti A Diperbarui',
                    'reasoning' => 'Alasan A Diperbarui',
                ],
                [
                    'claim'     => 'Klaim C Baru Ditambahkan',
                    'evidence'  => 'Bukti C Baru',
                    'reasoning' => 'Alasan C Baru',
                ],
            ],
        ]);

        $updateRes->assertStatus(200);
        $updatedMap = CerMap::with('triplets')->find($mapId);
        $this->assertEquals('Studio CRUD Test Map - Updated', $updatedMap->title);
        $this->assertFalse((bool) $updatedMap->allow_feedback);
        $this->assertEquals(2, $updatedMap->triplets->count());

        // Ensure first triplet retained its ID and was updated
        $retainedTriplet = $updatedMap->triplets->where('id', $firstTripletId)->first();
        $this->assertNotNull($retainedTriplet);
        $this->assertEquals('Klaim A Diperbarui', $retainedTriplet->claim);

        // 3. Delete Map
        $deleteRes = $this->actingAs($this->guru)->deleteJson("/api/maps/{$mapId}");
        $deleteRes->assertStatus(200);

        $this->assertDatabaseMissing('cer_maps', ['id' => $mapId]);
        $this->assertDatabaseMissing('triplets', ['map_id' => $mapId]);
    }
}
