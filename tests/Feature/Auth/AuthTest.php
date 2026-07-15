<?php

declare(strict_types=1);

use App\Models\User;

it('registers a new user and starts a session', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'secret-password-123',
        'password_confirmation' => 'secret-password-123',
    ])->assertNoContent();

    $this->assertAuthenticated();
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
});

it('rejects registration with an already taken email', function () {
    $user = User::factory()->create();

    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => $user->email,
        'password' => 'secret-password-123',
        'password_confirmation' => 'secret-password-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertGuest();
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create();

    $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertNoContent();

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials with a validation error', function () {
    $user = User::factory()->create();

    $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertGuest();
});

it('rate limits login after five failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertUnprocessable();
    expect($response->json('errors.email.0'))->toContain('Too many');
});

it('logs out the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/logout')->assertNoContent();

    $this->assertGuest();
});

it('returns the authenticated user profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email);
});

it('rejects unauthenticated access to the profile endpoint', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('issues the CSRF cookie for the SPA handshake', function () {
    $this->get('/sanctum/csrf-cookie')->assertNoContent();
});
