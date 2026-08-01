<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum decides whether a request came from our own SPA by matching the
        // Referer (or Origin) header against config('sanctum.stateful') — not the
        // host. Browsers always send one; the test client sends none, so without
        // this every /api route would silently lose its session middleware and
        // any call to $request->session() would blow up.
        $this->withHeader('Referer', config('app.url'));
    }
}
