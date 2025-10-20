<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Tests\Unit\Detectors;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Phpresilience\CiGuard\Detectors\TimeoutGuard\SymfonyHttpDetector;
use Phpresilience\CiGuard\Models\Issue;
use PHPUnit\Framework\TestCase;

class SymfonyHttpDetectorTest extends TestCase
{
    private SymfonyHttpDetector $detector;

    private Parser $parser;

    protected function setUp(): void
    {
        $this->detector = new SymfonyHttpDetector();
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public function testDetectsSymfonyRequestWithoutTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use Symfony\Component\HttpClient\HttpClient;
        
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.example.com');
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(1, $issues);
        $this->assertEquals('missing_timeout', $issues[0]->type);
        $this->assertEquals('Symfony HttpClient', $issues[0]->library);
    }

    public function testDoesNotDetectSymfonyRequestWithTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use Symfony\Component\HttpClient\HttpClient;
        
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.example.com', [
            'timeout' => 10,
        ]);
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(0, $issues);
    }

    public function testDetectsPostRequest(): void
    {
        $code = <<<'PHP'
        <?php
        use Symfony\Component\HttpClient\HttpClient;
        
        $client = HttpClient::create();
        $response = $client->request('POST', 'https://api.example.com', [
            'json' => ['data' => 'value'],
        ]);
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(1, $issues);
    }

    public function testDoesNotDetectGuzzlePostMethod(): void
    {
        $code = <<<'PHP'
        <?php
        
        $client = new SomeClient();
        $response = $client->post('https://api.example.com');
        PHP;

        $issues = $this->analyzeCode($code);

        // Symfony HttpClient only has request() method, not post()
        $this->assertCount(0, $issues);
    }

    public function testDetectsVariousHttpMethods(): void
    {
        $code = <<<'PHP'
        <?php
        use Symfony\Component\HttpClient\HttpClient;
        
        $client = HttpClient::create();
        $client->request('GET', 'https://api.example.com');
        $client->request('POST', 'https://api.example.com');
        $client->request('PUT', 'https://api.example.com');
        $client->request('DELETE', 'https://api.example.com');
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(4, $issues);
    }

    /**
     * @return array<Issue>
     */
    private function analyzeCode(string $code): array
    {
        $ast = $this->parser->parse($code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this->detector);
        $traverser->traverse($ast);

        $issues = $this->detector->getIssues();
        $this->detector->reset();

        return $issues;
    }
}
