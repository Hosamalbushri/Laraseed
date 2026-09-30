<?php

namespace Webkul\Core\Auth;

use Closure;
use Illuminate\Http\Request;
use Webkul\Core\Contracts\AuthenticationRedirectResolver as AuthenticationRedirectResolverContract;

class AuthenticationRedirectResolver implements AuthenticationRedirectResolverContract
{
    /** @var array<string, array{matches: Closure, destination: Closure, priority: int}> */
    private array $rules = [];

    public function register(
        string $key,
        Closure $matches,
        Closure $destination,
        int $priority = 100,
    ): void {
        $this->rules[$key] = compact('matches', 'destination', 'priority');
    }

    public function resolve(Request $request): ?string
    {
        $rules = $this->rules;

        uasort($rules, fn (array $left, array $right): int => $right['priority'] <=> $left['priority']);

        foreach ($rules as $rule) {
            if (($rule['matches'])($request)) {
                return ($rule['destination'])($request);
            }
        }

        return null;
    }
}
