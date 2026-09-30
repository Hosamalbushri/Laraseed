<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Core\Packages\OptionalPackageComposition;

class PackageDiagnosticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campushub:packages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the installed and enabled state of CampusHub optional packages (read-only)';

    /**
     * Execute the console command.
     */
    public function handle(OptionalPackageComposition $composition): int
    {
        $catalog = $composition->packages();
        $enabledIds = $composition->enabledPackages();

        $rows = [];

        foreach ($catalog as $id => $metadata) {
            $providerClass = (string) ($metadata['provider'] ?? '');
            $segments = explode('\\', ltrim($providerClass, '\\'));
            $displayName = $segments[1] ?? $id;

            $isInstalled = $providerClass !== '' && class_exists($providerClass);
            $isEnabled = $composition->isEnabled($id);
            $isProviderLoaded = $isEnabled && $providerClass !== '' && $this->laravel->providerIsLoaded($providerClass);

            $providerState = '-';
            if ($isEnabled) {
                $providerState = $isProviderLoaded ? 'registered' : 'pending';
            }

            $status = ($isEnabled && $isProviderLoaded) ? 'ACTIVE' : 'DISABLED';
            $requires = empty($metadata['requires']) ? '-' : implode(', ', $metadata['requires']);

            $rows[] = [
                $displayName,
                $id,
                $isInstalled ? 'YES' : 'NO',
                $isEnabled ? 'YES' : 'NO',
                $providerState,
                $requires,
                $status,
            ];
        }

        $this->table(
            ['Package', 'ID', 'Installed', 'Enabled', 'Provider', 'Requires', 'Status'],
            $rows,
        );

        if ($enabledIds === []) {
            $this->line('Active optional composition: Foundation only.');
            $this->line('Optional package composition is controlled through CAMPUSHUB_OPTIONAL_PACKAGES.');
        } else {
            $this->line('Active optional composition: '.implode(', ', $enabledIds));
        }

        return self::SUCCESS;
    }
}
