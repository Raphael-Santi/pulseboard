<?php

declare(strict_types=1);

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;

it('requires authentication', function () {
    $this->getJson('/api/status-pages')->assertUnauthorized();
});

it('lists only the user own status pages', function () {
    $user = User::factory()->create();
    StatusPage::factory()->count(2)->for($user)->create();
    StatusPage::factory()->count(3)->create();

    $this->actingAs($user)->getJson('/api/status-pages')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates a status page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/status-pages', [
        'slug' => 'acme-status',
        'title' => 'Acme Status',
    ])->assertCreated()->assertJsonPath('data.slug', 'acme-status');

    $this->assertDatabaseHas('status_pages', ['slug' => 'acme-status', 'user_id' => $user->id]);
});

it('rejects a duplicate slug', function () {
    $user = User::factory()->create();
    StatusPage::factory()->create(['slug' => 'taken']);

    $this->actingAs($user)->postJson('/api/status-pages', [
        'slug' => 'taken',
        'title' => 'Mine',
    ])->assertUnprocessable()->assertJsonValidationErrors('slug');
});

it('forbids updating another user status page', function () {
    $page = StatusPage::factory()->create();

    $this->actingAs(User::factory()->create())
        ->putJson("/api/status-pages/{$page->id}", ['slug' => 'x', 'title' => 'X'])
        ->assertForbidden();
});

it('syncs owned monitors onto the page with pivot metadata', function () {
    $user = User::factory()->create();
    $page = StatusPage::factory()->for($user)->create();
    [$first, $second] = Monitor::factory()->count(2)->for($user)->create();

    $this->actingAs($user)->postJson("/api/status-pages/{$page->id}/monitors", [
        'monitors' => [
            ['id' => $second->id, 'display_name' => 'API', 'sort_order' => 1],
            ['id' => $first->id, 'display_name' => 'Web', 'sort_order' => 2],
        ],
    ])->assertOk()->assertJsonCount(2, 'data.monitors');

    expect($page->monitors()->count())->toBe(2);
});

it('rejects syncing a monitor the user does not own', function () {
    $user = User::factory()->create();
    $page = StatusPage::factory()->for($user)->create();
    $foreign = Monitor::factory()->create();

    $this->actingAs($user)->postJson("/api/status-pages/{$page->id}/monitors", [
        'monitors' => [['id' => $foreign->id]],
    ])->assertUnprocessable()->assertJsonValidationErrors('monitors.0.id');
});

it('deletes an owned status page', function () {
    $user = User::factory()->create();
    $page = StatusPage::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/api/status-pages/{$page->id}")->assertNoContent();

    $this->assertDatabaseMissing('status_pages', ['id' => $page->id]);
});
