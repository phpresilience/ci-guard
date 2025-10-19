<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Tests\Unit\Reporters;

use Phpresilience\CiGuard\Models\Issue;
use Phpresilience\CiGuard\Reporters\DefaultReporter;
use PHPUnit\Framework\TestCase;

class DefaultReporterTest extends TestCase
{
    private DefaultReporter $reporter;

    protected function setUp(): void
    {
        $this->reporter = new DefaultReporter();
    }

    public function testReportWithNoIssues(): void
    {
        ob_start();
        $this->reporter->report([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('No issues found', $output);
        $this->assertStringContainsString('✅', $output);
    }

    public function testReportWithSingleIssue(): void
    {
        $issue = new Issue(
            line: 42,
            type: 'missing_timeout',
            library: 'Guzzle',
            method: 'get',
            severity: 'high',
            message: 'Guzzle HTTP request without timeout configuration',
            suggestion: 'Add timeout...',
            file: '/path/to/file.php',
        );

        ob_start();
        $this->reporter->report([$issue]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Found 1 issue', $output);
        $this->assertStringContainsString('Line 42', $output);
        $this->assertStringContainsString('Guzzle', $output);
        $this->assertStringContainsString('file.php', $output);
    }

    public function testReportGroupsIssuesByFile(): void
    {
        $issues = [
            new Issue(
                line: 10,
                type: 'missing_timeout',
                library: 'Guzzle',
                method: 'get',
                severity: 'high',
                message: 'Issue 1',
                suggestion: '',
                file: '/path/to/file1.php',
            ),
            new Issue(
                line: 20,
                type: 'missing_timeout',
                library: 'Guzzle',
                method: 'post',
                severity: 'high',
                message: 'Issue 2',
                suggestion: '',
                file: '/path/to/file1.php',
            ),
            new Issue(
                line: 30,
                type: 'missing_timeout',
                library: 'Symfony HttpClient',
                method: 'request',
                severity: 'high',
                message: 'Issue 3',
                suggestion: '',
                file: '/path/to/file2.php',
            ),
        ];

        ob_start();
        $this->reporter->report($issues);
        $output = ob_get_clean();

        $this->assertStringContainsString('Found 3 issue', $output);
        $this->assertStringContainsString('file1.php', $output);
        $this->assertStringContainsString('file2.php', $output);
    }

    public function testReportShowsSummary(): void
    {
        $issues = [
            new Issue(
                line: 10,
                type: 'missing_timeout',
                library: 'Guzzle',
                method: 'get',
                severity: 'high',
                message: 'Issue 1',
                suggestion: '',
                file: '/path/to/file.php',
            ),
        ];

        ob_start();
        $this->reporter->report($issues);
        $output = ob_get_clean();

        $this->assertStringContainsString('Summary', $output);
        $this->assertStringContainsString('High: 1', $output);
    }
}