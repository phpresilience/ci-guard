<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Detectors\TimeoutGuard;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Phpresilience\CiGuard\Models\Issue;

class SymfonyHttpDetector extends NodeVisitorAbstract
{
    private array $issues = [];

    public function enterNode(Node $node)
    {
        // Détecte : $client->request(...)
        if ($node instanceof Node\Expr\MethodCall) {
            if ($this->isSymfonyHttpRequest($node)) {
                if (!$this->hasTimeout($node)) {
                    $this->issues[] = new Issue(
                        line: $node->getLine(),
                        type: 'missing_timeout',
                        library: 'Symfony HttpClient',
                        method: $node->name->toString(),
                        severity: 'high',
                        message: 'Symfony HttpClient request without timeout configuration',
                        suggestion: $this->generateSuggestion(),
                    );
                }
            }
        }

        if ($node instanceof Node\Expr\MethodCall) {
            if ($this->isHttpClientStaticCall($node)) {
                if (!$this->hasTimeout($node)) {
                    $this->issues[] = new Issue(
                        line: $node->getLine(),
                        type: 'missing_timeout',
                        library: 'Symfony HttpClient',
                        method: $node->name->toString(),
                        severity: 'high',
                        message: 'Symfony HttpClient request without timeout configuration',
                        suggestion: $this->generateSuggestion(),
                    );
                }
            }
        }

        return null;
    }

    private function isSymfonyHttpRequest(Node\Expr\MethodCall $node): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $method = $node->name->toString();

        if ($method !== 'request') {
            return false;
        }

        if (empty($node->args)) {
            return false;
        }

        $firstArg = $node->args[0]->value;

        if (!$firstArg instanceof Node\Scalar\String_) {
            return false;
        }

        $httpMethod = strtoupper($firstArg->value);
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

        return in_array($httpMethod, $validMethods);

        // Todo : v2 check if $client variable comes from HttpClientInterface type hinting

        return false;
    }

    private function isHttpClientStaticCall(Node\Expr\MethodCall $node): bool
    {
        // Détecte : HttpClient::create()->request(...)
        if ($node->var instanceof Node\Expr\StaticCall) {
            $staticCall = $node->var;

            if ($staticCall->class instanceof Node\Name) {
                $className = $staticCall->class->toString();

                // Vérifie si c'est HttpClient
                if (str_contains($className, 'HttpClient')) {
                    return $node->name instanceof Node\Identifier
                        && $node->name->toString() === 'request';
                }
            }
        }

        return false;
    }

    private function hasTimeout(Node\Expr\MethodCall $node): bool
    {
        foreach ($node->args as $arg) {
            if ($arg->value instanceof Node\Expr\Array_) {
                foreach ($arg->value->items as $item) {
                    if ($item->key instanceof Node\Scalar\String_) {
                        if ($item->key->value === 'timeout') {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function generateSuggestion(): string
    {
        return <<<'PHP'
// Add timeout configuration:
$response = $client->request('GET', $url, [
    'timeout' => 10,  // Request timeout in seconds
]);
PHP;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    public function reset(): void
    {
        $this->issues = [];
    }
}