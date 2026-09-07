<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Laravel 13's CSRF middleware lives in the 'web' group as
        // PreventRequestForgery.  JSON test requests never carry a
        // token, so every POST/PUT/DELETE on a web route returns 419.
        // Disable it here once — CSRF is still enforced in production.
        $this->withoutMiddleware(PreventRequestForgery::class);
    }
}
