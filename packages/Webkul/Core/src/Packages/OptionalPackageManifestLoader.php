<?php

namespace Webkul\Core\Packages;

use Illuminate\Support\ServiceProvider;
use JsonException;
use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\Core\Exceptions\InvalidPackageComposition;

class OptionalPackageManifestLoader
{
    /**
     * Compile known Optional package manifests into a deterministic catalog.
     *
     * @param  list<string>  $manifestPaths
     * @return array<string, array{
     *     id: string,
     *     composer_name: string,
     *     provider: class-string<ServiceProvider>,
     *     concord_module: class-string<BaseModuleServiceProvider>|null,
     *     requires: list<string>
     * }>
     */
    public function load(array $manifestPaths): array
    {
        $packages = [];
        $composerNames = [];

        foreach ($manifestPaths as $path) {
            $manifest = $this->readManifest($path);
            $metadata = $manifest['extra']['campushub'] ?? null;

            if (! is_array($metadata)) {
                throw new InvalidPackageComposition("Optional package manifest [{$path}] is missing extra.campushub metadata.");
            }

            $id = $metadata['id'] ?? null;
            $composerName = $manifest['name'] ?? null;
            $provider = $metadata['provider'] ?? null;
            $module = $metadata['concord_module'] ?? null;

            if (! is_string($id) || preg_match('/^[a-z][a-z0-9_]*$/', $id) !== 1) {
                throw new InvalidPackageComposition("Optional package manifest [{$path}] has an invalid package ID.");
            }

            if (($metadata['type'] ?? null) !== 'optional') {
                throw new InvalidPackageComposition("Package [{$id}] must declare extra.campushub.type as optional.");
            }

            if (isset($packages[$id])) {
                throw new InvalidPackageComposition("Duplicate Optional package ID [{$id}].");
            }

            if (! is_string($composerName) || $composerName === '') {
                throw new InvalidPackageComposition("Optional package [{$id}] has no Composer package name.");
            }

            if (isset($composerNames[$composerName])) {
                throw new InvalidPackageComposition("Duplicate Optional Composer package name [{$composerName}].");
            }

            if (! is_string($provider) || ! class_exists($provider) || ! is_subclass_of($provider, ServiceProvider::class)) {
                throw new InvalidPackageComposition("Optional package [{$id}] declares an invalid provider class.");
            }

            if ($module !== null && (! is_string($module) || ! class_exists($module) || ! is_subclass_of($module, BaseModuleServiceProvider::class))) {
                throw new InvalidPackageComposition("Optional package [{$id}] declares an invalid Concord module class.");
            }

            $packages[$id] = [
                'id' => $id,
                'composer_name' => $composerName,
                'provider' => $provider,
                'concord_module' => $module,
                'requires' => [],
            ];
            $composerNames[$composerName] = $id;
        }

        foreach ($manifestPaths as $path) {
            $manifest = $this->readManifest($path);
            $id = $manifest['extra']['campushub']['id'];
            $requirements = array_keys($manifest['require'] ?? []);

            $packages[$id]['requires'] = array_values(array_map(
                fn (string $composerName): string => $composerNames[$composerName],
                array_values(array_filter(
                    $requirements,
                    fn (string $composerName): bool => isset($composerNames[$composerName]),
                )),
            ));
        }

        ksort($packages);

        return $packages;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readManifest(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidPackageComposition("Optional package manifest [{$path}] is not readable.");
        }

        try {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidPackageComposition("Optional package manifest [{$path}] contains invalid JSON.", previous: $exception);
        }

        if (! is_array($manifest)) {
            throw new InvalidPackageComposition("Optional package manifest [{$path}] must contain a JSON object.");
        }

        return $manifest;
    }
}
