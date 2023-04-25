<?php

namespace Tests;

use App\Http\Middleware\RecordRequestForSwagger;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->withMiddleware(RecordRequestForSwagger::class);
    }

    final public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app->make(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware(RecordRequestForSwagger::class);

        return $app;
    }
}
