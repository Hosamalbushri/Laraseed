<?php

namespace Webkul\Core\Packages;

use Webkul\Core\Exceptions\InvalidPackageComposition;

class OptionalPackageComposition
{
    /**
     * @var list<string>
     */
    protected array $enabled;

    /**
     * @param  array<string, array{
     *     id: string,
     *     composer_name: string,
     *     provider: string,
     *     concord_module: string|null,
     *     capabilities?: array<string, array{provider: string, enabled: bool}>,
     *     requires: list<string>
     * }>  $packages
     * @param  list<string>  $enabled
     */
    public function __construct(
        protected array $packages,
        array $enabled,
    ) {
        $this->normalizeCapabilities();
        $this->assertKnownPackages($enabled);
        $this->assertAcyclic();
        $this->assertEnabledDependencies($enabled);

        $this->enabled = $this->sortByDependencies(array_values(array_unique($enabled)));
    }

    protected function normalizeCapabilities(): void
    {
        foreach ($this->packages as $id => $data) {
            if (! isset($this->packages[$id]['capabilities']) || ! is_array($this->packages[$id]['capabilities'])) {
                $this->packages[$id]['capabilities'] = [];
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function parseEnabledPackageIds(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('trim', explode(',', $value)),
            fn (string $id): bool => $id !== '',
        )));
    }

    public function isEnabled(string $packageId): bool
    {
        $this->assertKnownPackages([$packageId]);

        return in_array($packageId, $this->enabled, true);
    }

    /**
     * @return list<string>
     */
    public function enabledPackages(): array
    {
        return $this->enabled;
    }

    /**
     * @return list<string>
     */
    public function providers(): array
    {
        return array_map(
            fn (string $id): string => $this->packages[$id]['provider'],
            $this->enabled,
        );
    }

    /**
     * @return list<string>
     */
    public function concordModules(): array
    {
        return array_values(array_filter(array_map(
            fn (string $id): ?string => $this->packages[$id]['concord_module'],
            $this->enabled,
        )));
    }

    /**
     * Determine if a package declares a specific capability.
     */
    public function hasCapability(string $packageId, string $capability): bool
    {
        $this->assertKnownPackages([$packageId]);

        return isset($this->packages[$packageId]['capabilities'][$capability]);
    }

    /**
     * Get the declared capability configuration for a package, or null if undeclared.
     *
     * @return array{provider: string, enabled: bool}|null
     */
    public function capability(string $packageId, string $capability): ?array
    {
        $this->assertKnownPackages([$packageId]);

        return $this->packages[$packageId]['capabilities'][$capability] ?? null;
    }

    /**
     * Get all declared capabilities for a package.
     *
     * @return array<string, array{provider: string, enabled: bool}>
     */
    public function capabilities(string $packageId): array
    {
        $this->assertKnownPackages([$packageId]);

        return $this->packages[$packageId]['capabilities'] ?? [];
    }

    /**
     * Get active capability providers for all enabled packages in deterministic dependency order.
     *
     * @return list<string>
     */
    public function capabilityProviders(string $capability): array
    {
        $providers = [];

        foreach ($this->enabled as $packageId) {
            $cap = $this->packages[$packageId]['capabilities'][$capability] ?? null;

            if ($cap !== null && ($cap['enabled'] ?? true) === true) {
                $providers[] = $cap['provider'];
            }
        }

        return array_values(array_unique($providers));
    }

    /**
     * @return array<string, list<string>>
     */
    public function dependencyGraph(): array
    {
        return array_map(
            fn (array $package): array => $package['requires'],
            $this->packages,
        );
    }

    /**
     * @return list<string>
     */
    public function enabledDependents(string $packageId): array
    {
        $this->assertKnownPackages([$packageId]);

        return array_values(array_filter(
            $this->enabled,
            fn (string $candidate): bool => in_array($packageId, $this->packages[$candidate]['requires'], true),
        ));
    }

    public function canDisable(string $packageId): bool
    {
        return $this->enabledDependents($packageId) === [];
    }

    /**
     * @return array<string, array{id: string, composer_name: string, provider: string, concord_module: string|null, capabilities: array<string, array{provider: string, enabled: bool}>, requires: list<string>}>
     */
    public function packages(): array
    {
        return $this->packages;
    }

    /**
     * @param  list<string>  $packageIds
     */
    protected function assertKnownPackages(array $packageIds): void
    {
        foreach ($packageIds as $id) {
            if (! isset($this->packages[$id])) {
                throw new InvalidPackageComposition("Unknown Optional package ID [{$id}].");
            }
        }
    }

    /**
     * @param  list<string>  $enabled
     */
    protected function assertEnabledDependencies(array $enabled): void
    {
        foreach ($enabled as $id) {
            foreach ($this->packages[$id]['requires'] as $dependency) {
                if (! in_array($dependency, $enabled, true)) {
                    throw new InvalidPackageComposition("Optional package \"{$id}\" requires enabled package \"{$dependency}\".");
                }
            }
        }
    }

    protected function assertAcyclic(): void
    {
        $visiting = [];
        $visited = [];

        $visit = function (string $id) use (&$visit, &$visiting, &$visited): void {
            if (isset($visiting[$id])) {
                throw new InvalidPackageComposition("Optional package dependency cycle detected at [{$id}].");
            }

            if (isset($visited[$id])) {
                return;
            }

            $visiting[$id] = true;

            foreach ($this->packages[$id]['requires'] as $dependency) {
                if (! isset($this->packages[$dependency])) {
                    throw new InvalidPackageComposition("Optional package [{$id}] requires unknown Optional package [{$dependency}].");
                }

                $visit($dependency);
            }

            unset($visiting[$id]);
            $visited[$id] = true;
        };

        foreach (array_keys($this->packages) as $id) {
            $visit($id);
        }
    }

    /**
     * @param  list<string>  $enabled
     * @return list<string>
     */
    protected function sortByDependencies(array $enabled): array
    {
        $result = [];
        $visited = [];

        $visit = function (string $id) use (&$visit, &$result, &$visited, $enabled): void {
            if (isset($visited[$id])) {
                return;
            }

            foreach ($this->packages[$id]['requires'] as $dependency) {
                if (in_array($dependency, $enabled, true)) {
                    $visit($dependency);
                }
            }

            $visited[$id] = true;
            $result[] = $id;
        };

        sort($enabled);

        foreach ($enabled as $id) {
            $visit($id);
        }

        return $result;
    }
}
