<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivewireRateLimitTest extends TestCase
{
    public function test_livewire_update_route_is_throttled_per_ip(): void
    {
        $route = app('router')->getRoutes()->getByName('livewire.update');
        $this->assertNotNull($route);
        $this->assertContains('throttle:livewire', $route->gatherMiddleware());

        $server = ['REMOTE_ADDR' => '198.51.100.77'];
        $last = null;
        foreach (range(1, 121) as $i) {
            $last = $this->withServerVariables($server)->postJson('/livewire/update', []);
        }

        $last->assertStatus(429);
    }

    public function test_other_ips_are_not_affected_by_one_ips_limit(): void
    {
        foreach (range(1, 121) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.78'])->postJson('/livewire/update', []);
        }

        $this->assertNotSame(
            429,
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.79'])->postJson('/livewire/update', [])->getStatusCode()
        );
    }
}
