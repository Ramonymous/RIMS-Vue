<?php

use App\Models\Parts;
use App\Models\RequestLists;
use App\Models\Requests;
use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

test('pick command caches request item data', function () {
    $user = User::factory()->create();

    $part = Parts::factory()->create([
        'part_number' => 'PN-1001',
        'part_name' => 'Widget A',
        'address' => 'A01-01-01',
    ]);

    $request = Requests::query()->create([
        'request_number' => fake()->unique()->numerify('REQ-####'),
        'requested_by' => $user->id,
        'requested_at' => now(),
        'destination' => 'Line 1',
        'status' => 'completed',
    ]);

    $requestItem = RequestLists::query()->create([
        'request_id' => $request->id,
        'part_id' => $part->id,
        'qty' => 5,
        'is_urgent' => true,
        'is_supplied' => false,
    ]);

    $this->withoutMiddleware([EnsureEmailIsVerified::class])
        ->actingAs($user)
        ->post(route('request-items.pick-command', $requestItem))
        ->assertRedirect();

    $cached = cache()->get('pick_command');

    expect($cached)->toBe('PN-1001');
});
