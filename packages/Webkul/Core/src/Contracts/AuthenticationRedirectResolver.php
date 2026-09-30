<?php

namespace Webkul\Core\Contracts;

use Closure;
use Illuminate\Http\Request;

interface AuthenticationRedirectResolver
{
    public function register(
        string $key,
        Closure $matches,
        Closure $destination,
        int $priority = 100,
    ): void;

    public function resolve(Request $request): ?string;
}
