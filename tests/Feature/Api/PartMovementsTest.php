<?php

use App\Models\PartMovements;
use App\Models\Parts;
use App\Models\User;

test('unauthenticated users cannot access part movements api', function () {
    $response = $this->getJson('/api/part-movements');
    $response->assertStatus(401);
});

test('authenticated users can access part movements api', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $part = Parts::factory()->create(['part_number' => 'TEST-123']);
    PartMovements::factory()->count(3)->create([
        'part_id' => $part->id,
        'type' => 'in',
        'qty' => 100,
    ]);

    $response = $this->withToken($token)->getJson('/api/part-movements');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'count',
            'data' => [
                '*' => [
                    'part_number',
                    'date',
                    'time',
                    'type',
                    'qty',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'count' => 3,
        ]);
});

test('part movements can be filtered by date', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $part = Parts::factory()->create();
    PartMovements::factory()->create([
        'part_id' => $part->id,
        'created_at' => now()->subDays(5),
    ]);
    PartMovements::factory()->create([
        'part_id' => $part->id,
        'created_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/part-movements?start_date='.now()->format('Y-m-d'));

    $response->assertOk()
        ->assertJson(['count' => 1]);
});

test('part movements can be filtered by type', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $part = Parts::factory()->create();
    PartMovements::factory()->count(2)->create([
        'part_id' => $part->id,
        'type' => 'in',
    ]);
    PartMovements::factory()->create([
        'part_id' => $part->id,
        'type' => 'out',
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/part-movements?type=in');

    $response->assertOk()
        ->assertJson(['count' => 2]);
});
