<?php

namespace Tests\Unit;

use App\Models\CerMap;
use App\Models\Triplet;
use App\Services\ScoringService;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    protected ScoringService $scoringService;
    protected CerMap $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = new ScoringService();

        $this->map = CerMap::firstOrCreate(['title' => 'Test Scoring Map']);
        if ($this->map->triplets()->count() === 0) {
            Triplet::create([
                'map_id'    => $this->map->id,
                'claim'     => 'Klaim 1',
                'evidence'  => 'Bukti 1',
                'reasoning' => 'Alasan 1',
            ]);
            Triplet::create([
                'map_id'    => $this->map->id,
                'claim'     => 'Klaim 2',
                'evidence'  => 'Bukti 2',
                'reasoning' => 'Alasan 2',
            ]);
        }
        $this->map->load('triplets');
    }

    public function test_perfect_connections_yield_100_percent(): void
    {
        $t1 = $this->map->triplets[0]->id;
        $t2 = $this->map->triplets[1]->id;

        $connections = [
            ['from' => "c-{$t1}", 'to' => "e-{$t1}"],
            ['from' => "e-{$t1}", 'to' => "r-{$t1}"],
            ['from' => "c-{$t2}", 'to' => "e-{$t2}"],
            ['from' => "e-{$t2}", 'to' => "r-{$t2}"],
        ];

        $score = $this->scoringService->calculateScore($this->map, $connections);
        $this->assertEquals(100.0, $score);
    }

    public function test_duplicate_connections_do_not_inflate_score(): void
    {
        $t1 = $this->map->triplets[0]->id;

        // Same connection repeated 4 times should only count as 1 correct connection
        $connections = [
            ['from' => "c-{$t1}", 'to' => "e-{$t1}"],
            ['from' => "c-{$t1}", 'to' => "e-{$t1}"],
            ['from' => "c-{$t1}", 'to' => "e-{$t1}"],
            ['from' => "c-{$t1}", 'to' => "e-{$t1}"],
        ];

        $score = $this->scoringService->calculateScore($this->map, $connections);
        // Total required is 4. Only 1 unique correct connection = 1/4 * 100 = 25%
        $this->assertEquals(25.0, $score);
    }

    public function test_self_connections_are_rejected(): void
    {
        $t1 = $this->map->triplets[0]->id;

        $connections = [
            ['from' => "c-{$t1}", 'to' => "c-{$t1}"],
            ['from' => "e-{$t1}", 'to' => "e-{$t1}"],
        ];

        $score = $this->scoringService->calculateScore($this->map, $connections);
        $this->assertEquals(0.0, $score);
    }

    public function test_invalid_type_connections_are_rejected(): void
    {
        $t1 = $this->map->triplets[0]->id;

        // Claim directly to Reasoning without Evidence is invalid
        $connections = [
            ['from' => "c-{$t1}", 'to' => "r-{$t1}"],
        ];

        $score = $this->scoringService->calculateScore($this->map, $connections);
        $this->assertEquals(0.0, $score);
    }

    public function test_mismatched_triplet_connections_are_rejected(): void
    {
        $t1 = $this->map->triplets[0]->id;
        $t2 = $this->map->triplets[1]->id;

        $connections = [
            ['from' => "c-{$t1}", 'to' => "e-{$t2}"],
            ['from' => "e-{$t1}", 'to' => "r-{$t2}"],
        ];

        $score = $this->scoringService->calculateScore($this->map, $connections);
        $this->assertEquals(0.0, $score);
    }

    public function test_reverse_dragged_connections_are_supported(): void
    {
        $t1 = $this->map->triplets[0]->id;

        $connections = [
            ['from' => "e-{$t1}", 'to' => "c-{$t1}"],
            ['from' => "r-{$t1}", 'to' => "e-{$t1}"],
        ];

        $score = $this->scoringService->calculateScore($this->map, $connections);
        // 2 out of 4 correct = 50%
        $this->assertEquals(50.0, $score);
    }
}
