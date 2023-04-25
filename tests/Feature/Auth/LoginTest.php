<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Arr;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private const URI = '/api/auth/login';
    private const PASSWORD = 'password';
    private User $user;
    private array $loginData;

    final protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => self::PASSWORD]);

        $this->loginData = [
            'email' => $this->user->email,
            'password' => self::PASSWORD,
        ];
    }

    final public function test_success(): void
    {
        $this->assertEquals(0, $this->user->tokens()->count());

        $this->postJson(self::URI, $this->loginData)->assertOk()
                 ->assertJson(fn (AssertableJson $json) =>
                    $json
                        ->where('status', 200)
                        ->where('success', true)
                        ->has('data', fn (AssertableJson $json) =>
                            $json
                                ->where('token_type', 'bearer')
                                ->where('expires_in', null)
                                ->has('access_token')
                                ->has('user')));

        $this->assertEquals(1, $this->user->tokens()->count());
    }

    final public function test_wrong_credentials(): void
    {
        $this->assertEquals(0, $this->user->tokens()->count());

        $this->postJson(self::URI, Arr::set($this->loginData, 'password', 'wrong_password'))
             ->assertUnauthorized();

        $this->assertEquals(0, $this->user->tokens()->count());
    }

    final public function test_disabled_user(): void
    {
        $this->user->update(['active' => false]);

        $this->assertEquals(0, $this->user->tokens()->count());

        $this->postJson(self::URI, $this->loginData)->assertForbidden();

        $this->assertEquals(0, $this->user->tokens()->count());
    }

    final public function test_soft_deleted_user(): void
    {
        $this->user->delete();

        $this->assertEquals(0, $this->user->tokens()->count());

        $this->postJson(self::URI, $this->loginData)->assertUnauthorized();

        $this->assertEquals(0, $this->user->tokens()->count());
    }

    final public function test_without_params(): void
    {
        $this->postJson(self::URI)->assertUnprocessable();
    }
}
