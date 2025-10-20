<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Tests\Unit\Detectors;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Phpresilience\CiGuard\Detectors\TimeoutGuard\GuzzleDetector;
use Phpresilience\CiGuard\Models\Issue;
use PHPUnit\Framework\TestCase;

class GuzzleDetectorTest extends TestCase
{
    private GuzzleDetector $detector;

    private Parser $parser;

    protected function setUp(): void
    {
        $this->detector = new GuzzleDetector();
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public function testDetectsGuzzleGetWithoutTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response = $client->get('https://api.example.com');
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(1, $issues);
        $this->assertEquals('missing_timeout', $issues[0]->type);
        $this->assertEquals('Guzzle', $issues[0]->library);
        $this->assertEquals('get', $issues[0]->method);
    }

    public function testDoesNotDetectGuzzleGetWithTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response = $client->get('https://api.example.com', [
            'timeout' => 10,
        ]);
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(0, $issues);
    }

    public function testDetectsGuzzlePostWithoutTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response = $client->post('https://api.example.com', [
            'json' => ['key' => 'value'],
        ]);
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(1, $issues);
        $this->assertEquals('post', $issues[0]->method);
    }

    public function testDetectsGuzzleRequestWithoutTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response = $client->request('GET', 'https://api.example.com');
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(1, $issues);
    }

    public function testDoesNotDetectNonHttpClientMethods(): void
    {
        $code = <<<'PHP'
        <?php
        
        $request = new Request();
        $data = $request->get('param');
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(0, $issues);
    }

    public function testDetectsMultipleIssuesInSameFile(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response1 = $client->get('https://api.example.com');
        $response2 = $client->post('https://api.example.com');
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(2, $issues);
    }

    public function testDetectsConnectTimeoutButMissingTotalTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response = $client->get('https://api.example.com', [
            'connect_timeout' => 3,
        ]);
        PHP;

        $issues = $this->analyzeCode($code);

        // Should still detect because 'timeout' is missing
        $this->assertCount(1, $issues);
    }

    public function testDoesNotDetectWithConnectTimeoutAndTimeout(): void
    {
        $code = <<<'PHP'
        <?php
        use GuzzleHttp\Client;
        
        $client = new Client();
        $response = $client->get('https://api.example.com', [
            'timeout' => 10,
            'connect_timeout' => 3,
        ]);
        PHP;

        $issues = $this->analyzeCode($code);

        $this->assertCount(0, $issues);
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
