<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Reporters;

use Phpresilience\CiGuard\Models\Issue;

class JsonReporter implements ReporterInterface
{
    /**
     * @param array<Issue> $issues
     */
    public function report(array $issues): void
    {
        $output = [
            'summary' => [
                'total' => \count($issues),
                'by_severity' => $this->countBySeverity($issues),
            ],
            'issues' => array_map(fn ($issue) => [
                'file' => $issue->file,
                'line' => $issue->line,
                'type' => $issue->type,
                'library' => $issue->library,
                'method' => $issue->method,
                'severity' => $issue->severity,
                'message' => $issue->message,
                'suggestion' => $issue->suggestion,
            ], $issues),
        ];

        echo \json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<Issue> $issues
     * @return array<string, int>
     */
    private function countBySeverity(array $issues): array
    {
        $counts = [];
        foreach ($issues as $issue) {
            $counts[$issue->severity] = ($counts[$issue->severity] ?? 0) + 1;
        }

        return $counts;
    }
}
