<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Detectors\TimeoutGuard;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Phpresilience\CiGuard\Models\Issue;

class GuzzleDetector extends NodeVisitorAbstract
{
    private array $issues = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\MethodCall) {
            if ($this->isGuzzleRequest($node)) {
                if (!$this->hasTimeout($node)) {
                    $this->issues[] = new Issue(
                        line: $node->getLine(),
                        type: 'missing_timeout',
                        library: 'Guzzle',
                        method: $node->name->toString(),
                        severity: 'high',
                        message: 'Guzzle HTTP request without timeout configuration',
                        suggestion: $this->generateSuggestion(),
                    );
                }
            }
        }

        return null;
    }

    private function isGuzzleRequest(Node\Expr\MethodCall $node): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $method = $node->name->toString();

        if (!in_array($method, ['request', 'get', 'post', 'put', 'delete', 'patch', 'head', 'options'])) {
            return false;
        }

        if ($method === 'request' && $this->hasHttpMethodAsFirstArg($node)) {
            if ($this->looksLikeSymfonyClient($node->var)) {
                return false; // C'est probablement Symfony
            }
        }

        if ($node->var instanceof Node\Expr\Variable) {
            $varName = $node->var->name;

            $httpClientNames = [
                'client', 'httpClient', 'guzzle', 'guzzleClient',
                'http', 'api', 'apiClient', 'restClient'
            ];

            if (in_array($varName, $httpClientNames)) {
                return true;
            }
        }

        return false;
    }

    private function hasHttpMethodAsFirstArg(Node\Expr\MethodCall $node): bool
    {
        if (empty($node->args)) {
            return false;
        }

        $firstArg = $node->args[0]->value;

        if (!$firstArg instanceof Node\Scalar\String_) {
            return false;
        }

        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        return in_array(strtoupper($firstArg->value), $httpMethods);
    }

    private function looksLikeSymfonyClient($var): bool
    {
        if ($var instanceof Node\Expr\Variable) {
            $varName = $var->name;
            return in_array($varName, ['symfonyClient', 'httpClient']);
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
    'timeout' => 10,         // Total request timeout
    'connect_timeout' => 3,  // Connection timeout
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
