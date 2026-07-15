<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests must not depend on built frontend assets: the CI
        // php job runs without the Vite manifest (public/build is ignored).
        $this->withoutVite();
    }
}
