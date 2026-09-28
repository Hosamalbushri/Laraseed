<?php

namespace Webkul\Admin\Helpers;

use Closure;

class DashboardStatsRegistry
{
    /** @var array<string, Closure> */
    protected array $providers = [];

    public function register(string $type, Closure $provider): void
    {
        $this->providers[$type] = $provider;
    }

    public function has(string $type): bool
    {
        return isset($this->providers[$type]);
    }

    public function resolve(string $type): array
    {
        return ($this->providers[$type])();
    }
}
