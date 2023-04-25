<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeTest extends TestCase
{
    private const URI = 'api/auth/me';

    final public function test_me(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(self::URI)->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->where('status', 200)
                    ->where('success', true)
                    ->has('data'));
    }

    final public function test_without_auth(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }
}
