<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserHiddenTest extends TestCase
{
    public function test_api_token_and_password_are_not_serialized(): void
    {
        $user = new User(['name' => 'Roy', 'email' => 'roy@example.com', 'api_token' => 'secret-token']);
        $user->password = 'secret-hash';

        $array = $user->toArray();
        $this->assertArrayNotHasKey('api_token', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertStringNotContainsString('secret-token', $user->toJson());
        $this->assertSame('Roy', $array['name']);
    }
}
