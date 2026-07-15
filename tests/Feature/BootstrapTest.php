<?php

declare(strict_types=1);

it('serves the SPA shell on the root route', function () {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('app');
});

it('serves the SPA shell for client-side routes', function () {
    $this->get('/monitors')
        ->assertOk()
        ->assertViewIs('app');
});

it('exposes the framework health endpoint', function () {
    $this->get('/up')->assertOk();
});

it('keeps API paths outside the SPA catch-all', function () {
    $this->getJson('/api/missing')->assertNotFound();
});
