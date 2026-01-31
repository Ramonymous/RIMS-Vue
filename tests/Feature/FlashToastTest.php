<?php

use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Inertia\Testing\AssertableInertia as Assert;

test('flash messages are shared in inertia props', function () {
    $user = User::factory()->create([
        'permissions' => ['admin'],
    ]);

    $this->withoutMiddleware([EnsureEmailIsVerified::class])
        ->actingAs($user)
        ->withSession([
            'success' => 'Saved',
            'error' => 'Failed',
            'warning' => 'Warning',
            'info' => 'Info',
        ])
        ->get(route('parts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('flash.success', 'Saved')
            ->where('flash.error', 'Failed')
            ->where('flash.warning', 'Warning')
            ->where('flash.info', 'Info')
        );
});