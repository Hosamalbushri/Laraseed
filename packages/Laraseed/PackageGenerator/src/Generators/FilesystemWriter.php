<?php

namespace Laraseed\PackageGenerator\Generators;

use Illuminate\Filesystem\Filesystem;

class FilesystemWriter
{
    public function __construct(
        protected Filesystem $filesystem = new Filesystem
    ) {}

    /**
     * Write planned files to disk or simulate dry-run execution.
     *
     * @return array<int, array{path: string, full_path: string, action: string, bytes: int}>
     */
    public function execute(GenerationPlan $plan, bool $dryRun, bool $force): array
    {
        $targetDir = $plan->targetDirectory();
        $results = [];

        foreach ($plan->files as $relativePath => $content) {
            $fullPath = "{$targetDir}/{$relativePath}";
            $exists = $this->filesystem->exists($fullPath);
            $action = $exists ? 'OVERWRITE' : 'CREATE';

            if (! $dryRun) {
                $directory = dirname($fullPath);
                if (! $this->filesystem->isDirectory($directory)) {
                    $this->filesystem->makeDirectory($directory, 0755, true);
                }

                $this->filesystem->put($fullPath, $content);
            }

            $results[] = [
                'path' => "{$plan->relativePackagePath}/{$relativePath}",
                'full_path' => $fullPath,
                'action' => $action,
                'bytes' => strlen($content),
            ];
        }

        return $results;
    }
}
