<?php

namespace Tests\Support;

use Symfony\Component\Process\Process;

trait InteractsWithOptionalPackageComposition
{
    /**
     * @param  list<string>  $packages
     * @return array{exit_code: int, output: string, error: string}
     */
    protected function runCompositionCommand(array $packages, array $command): array
    {
        $process = new Process(
            [PHP_BINARY, base_path('artisan'), ...$command],
            base_path(),
            ['CAMPUSHUB_OPTIONAL_PACKAGES' => implode(',', $packages)],
        );
        $process->setTimeout(30);
        $process->run();

        return [
            'exit_code' => $process->getExitCode() ?? 1,
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
        ];
    }

    /**
     * @param  list<string>  $packages
     * @return list<array<string, mixed>>
     */
    protected function routesForComposition(array $packages): array
    {
        $result = $this->runCompositionCommand($packages, ['route:list', '--json']);

        $this->assertSame(0, $result['exit_code'], $result['error'] ?: $result['output']);

        $routes = json_decode($result['output'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($routes);

        return $routes;
    }
}
