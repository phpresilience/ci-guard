<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Detectors\TimeoutGuard;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Phpresilience\CiGuard\Detectors\DetectorInterface;
use Phpresilience\CiGuard\Models\Issue;

class CurlDetector extends NodeVisitorAbstract implements DetectorInterface
{
    /** @var array<Issue> */
    private array $issues = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall) {
            if ($this->isCurlExec($node)) {
                if (! $this->hasTimeoutInScope($node)) {
                    $this->issues[] = new Issue(
                        line: $node->getLine(),
                        type: 'missing_timeout',
                        library: 'cURL',
                        method: 'curl_exec',
                        severity: 'high',
                        message: 'cURL request without timeout configuration',
                        suggestion: $this->generateSuggestion(),
                    );
                }
            }
        }

        return null;
    }

    private function isCurlExec(Node\Expr\FuncCall $node): bool
    {
        if ($node->name instanceof Node\Name) {
            return $node->name->toString() === 'curl_exec';
        }

        return false;
    }

    private function hasTimeoutInScope(Node $node): bool
    {
        // Pour MVP : on considère qu'il n'y a pas de timeout
        // (détecter le scope parent est plus complexe, on fera v2)
        return false;
    }

    private function generateSuggestion(): string
    {
        return <<<'PHP'
// Add timeout configuration:
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);         // Total timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);  // Connection timeout
$response = curl_exec($ch);
curl_close($ch);
PHP;
    }

    /**
     * @return array<Issue>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    public function reset(): void
    {
        $this->issues = [];
    }
}
