<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard;

use FilesystemIterator;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Analyzer
{
    private array $detectors = [];

    public function __construct()
    {
        $this->detectors = $this->loadDetectors();
    }

    public function analyze(string $directory): array
    {
        $issues = [];
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        foreach ($this->getPhpFiles($directory) as $file) {
            $code = file_get_contents($file);

            try {
                $ast = $parser->parse($code);

                foreach ($this->detectors as $detector) {
                    $traverser = new NodeTraverser();
                    $traverser->addVisitor($detector);
                    $traverser->traverse($ast);

                    foreach ($detector->getIssues() as $issue) {
                        $issue->file = $file;
                        $issues[] = $issue;
                    }

                    $detector->reset();
                }
            } catch (\Exception $e) {
                // Skip files with parse errors
                continue;
            }
        }

        return $issues;
    }

    private function loadDetectors(): array
    {
        return [
            new Detectors\TimeoutGuard\SymfonyHttpDetector(),
            new Detectors\TimeoutGuard\CurlDetector(),
            new Detectors\TimeoutGuard\GuzzleDetector(),
        ];
    }

    private function getPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
