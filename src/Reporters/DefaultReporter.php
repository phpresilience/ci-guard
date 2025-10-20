<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Reporters;

class DefaultReporter implements ReporterInterface
{
    public function report(array $issues): void
    {
        if (empty($issues)) {
            echo "✅ No issues found! All HTTP calls have timeout configuration.\n";

            return;
        }

        echo sprintf("❌ Found %d issue(s):\n\n", count($issues));

        $groupedByFile = $this->groupByFile($issues);
        $displayedSuggestions = []; // ✅ Track what we've already shown

        foreach ($groupedByFile as $file => $fileIssues) {
            $relativeFile = $this->getRelativePath($file);
            echo "📄 {$relativeFile}\n";

            foreach ($fileIssues as $issue) {
                $emoji = $this->getSeverityEmoji($issue->severity);
                echo sprintf(
                    "  %s Line %d: %s (%s %s)\n",
                    $emoji,
                    $issue->line,
                    $issue->message,
                    $issue->library,
                    $issue->method,
                );

                $suggestionKey = $issue->library . '::' . $issue->type;

                if ($issue->suggestion && ! isset($displayedSuggestions[$suggestionKey])) {
                    echo "\n" . $this->indent($issue->suggestion, 4) . "\n";
                    $displayedSuggestions[$suggestionKey] = true;
                }

                echo "\n";
            }

            echo "\n";
        }

        $this->printSummary($issues);
    }

    private function groupByFile(array $issues): array
    {
        $grouped = [];

        foreach ($issues as $issue) {
            $grouped[$issue->file][] = $issue;
        }

        return $grouped;
    }

    private function getRelativePath(string $file): string
    {
        $cwd = getcwd();

        if (str_starts_with($file, $cwd)) {
            return '.' . substr($file, strlen($cwd));
        }

        return $file;
    }

    private function getSeverityEmoji(string $severity): string
    {
        return match($severity) {
            'critical' => '🔴',
            'high' => '⚠️',
            'medium' => '🟡',
            'low' => 'ℹ️',
            default => '•',
        };
    }

    private function indent(string $text, int $spaces): string
    {
        $indent = str_repeat(' ', $spaces);

        return $indent . str_replace("\n", "\n" . $indent, $text);
    }

    private function printSummary(array $issues): void
    {
        $bySeverity = [];

        foreach ($issues as $issue) {
            $bySeverity[$issue->severity] = ($bySeverity[$issue->severity] ?? 0) + 1;
        }

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Summary:\n";

        foreach ($bySeverity as $severity => $count) {
            $emoji = $this->getSeverityEmoji($severity);
            echo sprintf("  %s %s: %d\n", $emoji, ucfirst($severity), $count);
        }

        echo "\n💡 Tip: Add timeouts to prevent hanging requests that can block your application.\n";
    }
}
