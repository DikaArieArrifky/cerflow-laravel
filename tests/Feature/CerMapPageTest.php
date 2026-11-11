<?php

namespace Tests\Feature;

use App\Models\CerMap;
use App\Models\Triplet;
use App\Models\User;
use Tests\TestCase;

class CerMapPageTest extends TestCase
{
    protected User $guru;
    protected User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::firstOrCreate(
            ['username' => 'guru_cermap_test'],
            [
                'namalengkap' => 'Guru Map Test',
                'password'    => 'secret123',
                'role'        => 'guru',
            ]
        );

        $this->siswa = User::firstOrCreate(
            ['username' => 'siswa_cermap_test'],
            [
                'namalengkap' => 'Siswa Map Test',
                'password'    => 'secret123',
                'role'        => 'siswa',
            ]
        );
    }

    public function test_guru_can_open_maps_page(): void
    {
        $response = $this->actingAs($this->guru)->get('/admin/maps');

        $response->assertStatus(200);
        $response->assertSee('Content');
        $response->assertSee('Hub');
        $response->assertSee('Total Materi');
    }

    public function test_siswa_cannot_open_maps_page(): void
    {
        $response = $this->actingAs($this->siswa)->get('/admin/maps');

        $response->assertStatus(403);
    }

    public function test_maps_and_triplets_count_displayed_correctly(): void
    {
        $map = CerMap::create([
            'title'          => 'Pemanasan Global Khusus Test 3C',
            'allow_feedback' => true,
            'allow_reading'  => true,
            'reading_text'   => 'Teks bacaan pemanasan global.',
        ]);

        Triplet::create([
            'map_id'    => $map->id,
            'claim'     => 'Klaim Suhu Naik',
            'evidence'  => 'Data Termometer',
            'reasoning' => 'Efek Gas Rumah Kaca',
        ]);

        Triplet::create([
            'map_id'    => $map->id,
            'claim'     => 'Klaim Es Mencair',
            'evidence'  => 'Foto Satelit Kutub',
            'reasoning' => 'Suhu Air Meningkat',
        ]);

        $response = $this->actingAs($this->guru)->get('/admin/maps');

        $response->assertStatus(200);
        $response->assertSee('Pemanasan Global Khusus Test 3C');
        $response->assertSee('2 Triplet');

        // Cleanup
        $map->triplets()->delete();
        $map->delete();
    }

    public function test_guru_can_delete_map_and_triplets_are_deleted_cascade(): void
    {
        $map = CerMap::create([
            'title'          => 'Materi yang Akan Dihapus 3C',
            'allow_feedback' => true,
            'allow_reading'  => true,
        ]);

        $triplet1 = Triplet::create([
            'map_id'    => $map->id,
            'claim'     => 'Claim 1',
            'evidence'  => 'Evidence 1',
            'reasoning' => 'Reasoning 1',
        ]);

        $triplet2 = Triplet::create([
            'map_id'    => $map->id,
            'claim'     => 'Claim 2',
            'evidence'  => 'Evidence 2',
            'reasoning' => 'Reasoning 2',
        ]);

        $response = $this->actingAs($this->guru)->deleteJson("/api/maps/{$map->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'success',
        ]);

        $this->assertDatabaseMissing('cer_maps', ['id' => $map->id]);
        $this->assertDatabaseMissing('triplets', ['id' => $triplet1->id]);
        $this->assertDatabaseMissing('triplets', ['id' => $triplet2->id]);
    }

    public function test_guru_can_access_preview_map_without_broken_links(): void
    {
        $map = CerMap::create([
            'title'          => 'Materi Preview Test 3C',
            'allow_feedback' => true,
            'allow_reading'  => true,
        ]);

        Triplet::create([
            'map_id'    => $map->id,
            'claim'     => 'Preview Claim',
            'evidence'  => 'Preview Evidence',
            'reasoning' => 'Preview Reasoning',
        ]);

        $response = $this->actingAs($this->guru)->get("/admin/preview/{$map->id}");

        $response->assertStatus(200);
        $response->assertSee('Preview Map');
        $response->assertSee('Materi Preview Test 3C');
        $response->assertSee('Preview Claim');

        // Siswa cannot access preview
        $siswaResponse = $this->actingAs($this->siswa)->get("/admin/preview/{$map->id}");
        $siswaResponse->assertStatus(403);

        // Cleanup
        $map->triplets()->delete();
        $map->delete();
    }
}
