# TimeoutGuard - HTTP Timeout Detection

> Prevent hanging requests and worker pool exhaustion through proper timeout configuration

## Overview

TimeoutGuard is CI-Guard's first detector, focusing on HTTP client timeout configurations. It identifies HTTP calls that lack proper timeout settings, preventing one of the most common causes of production incidents in PHP applications.

## Table of Contents

- [Why Timeouts Matter](#why-timeouts-matter)
- [Supported Libraries](#supported-libraries)
- [Configuration Options](#configuration-options)
- [Best Practices](#best-practices)
- [Real-World Examples](#real-world-examples)

---

## Why Timeouts Matter

### The Problem

In PHP applications (especially with PHP-FPM), each request is handled by a worker from a limited pool. When an HTTP request hangs:

1. **Worker Blocking**: The PHP-FPM worker waits for the response
2. **Pool Exhaustion**: Multiple hanging requests exhaust the worker pool
3. **Cascading Failure**: New requests can't be processed (no available workers)
4. **Application Down**: Your entire application becomes unresponsive

### The Anatomy of a Timeout Incident
```
Timeline of a real incident:

14:23:45 - Payment API starts experiencing latency (response time: 25s)
14:23:47 - First checkout request arrives
14:23:48 - Worker #1 waiting for payment API response...
14:23:50 - Second checkout request arrives
14:23:51 - Worker #2 waiting for payment API response...
14:24:05 - 20 workers now blocked waiting for payment API
14:24:15 - Worker pool exhausted (50/50 workers blocked)
14:24:16 - New requests start queueing
14:24:30 - Users report "website is down"
14:24:45 - On-call engineer paged
14:25:00 - 300+ requests in queue
14:30:00 - Manual restart required
14:35:00 - Service restored

Impact:
- Downtime: 11 minutes
- Affected users: 2,500+
- Lost revenue: ~$30,000
- Engineering hours: 4h (investigation + postmortem)

Root cause: Missing 10-second timeout on payment API call
```

### How Timeouts Prevent This
```php
// ❌ Without timeout - incident scenario above
$client = new GuzzleHttp\Client();
$response = $client->post('https://payment-api.com/charge', [
    'json' => ['amount' => 100],
]);

// ✅ With timeout - fail fast, stay responsive
$client = new GuzzleHttp\Client();
try {
    $response = $client->post('https://payment-api.com/charge', [
        'json' => ['amount' => 100],
        'timeout' => 10,         // Total request timeout
        'connect_timeout' => 3,  // Connection timeout
    ]);
} catch (ConnectException $e) {
    // Connection failed within 3s
    // Handle gracefully, worker released immediately
} catch (RequestException $e) {
    // Request exceeded 10s
    // Handle gracefully, worker released immediately
}
```

**Result:**
- Request fails after 10s (not 60s+)
- Worker released immediately
- Application stays responsive
- Error logged for monitoring
- Circuit breaker can activate (if implemented)

---

## Supported Libraries

### Guzzle HTTP Client

**Detection capabilities:**
- ✅ `$client->get()`
- ✅ `$client->post()`
- ✅ `$client->put()`
- ✅ `$client->delete()`
- ✅ `$client->patch()`
- ✅ `$client->request()`

**Recognized timeout configurations:**
```php
// ✅ Valid - both timeouts configured
$client->get('https://api.example.com', [
    'timeout' => 10,
    'connect_timeout' => 3,
]);

// ✅ Valid - timeout configured
$client->get('https://api.example.com', [
    'timeout' => 10,
]);

// ⚠️ WARNING - only connect_timeout (medium severity)
$client->get('https://api.example.com', [
    'connect_timeout' => 3,
]);

// ❌ ERROR - no timeout (high severity)
$client->get('https://api.example.com');
```

### Symfony HttpClient

**Detection capabilities:**
- ✅ `$client->request('GET', ...)`
- ✅ `$client->request('POST', ...)`
- ✅ All HTTP methods via `request()`

**Recognized timeout configurations:**
```php
// ✅ Valid
$client->request('GET', 'https://api.example.com', [
    'timeout' => 10,
]);

// ❌ ERROR - no timeout
$client->request('GET', 'https://api.example.com');
```

### cURL (Coming Soon)

**Planned detection:**
```php
// ❌ Will be detected
$ch = curl_init('https://api.example.com');
curl_exec($ch);

// ✅ Will be valid
$ch = curl_init('https://api.example.com');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_exec($ch);
```

---

## Configuration Options

### Command-Line Options
```bash
# Basic usage
vendor/bin/ci-guard ./src

# Specific directory
vendor/bin/ci-guard ./src/Service

# Specific file
vendor/bin/ci-guard ./src/Service/PaymentService.php

# Multiple paths
vendor/bin/ci-guard ./src ./app
```

### Configuration File (Coming Soon)

Create `.ci-guard.yml` in your project root:
```yaml
timeout_guard:
  enabled: true
  
  severity_levels:
    no_timeout: high
    only_connect_timeout: medium
  
  recommended_timeouts:
    default: 10
    payment_apis: 30
    internal_services: 5
  
  exclude_patterns:
    - tests/**
    - vendor/**
  
  custom_http_clients:
    - MyCustomHttpClient
```

---

## Best Practices

### 1. Choose Appropriate Timeout Values
```php
// ❌ Too short - may cause false failures
'timeout' => 1,  // 1 second might be too aggressive

// ✅ Good - reasonable for most APIs
'timeout' => 10,  // 10 seconds for external APIs

// ✅ Good - longer for specific cases
'timeout' => 30,  // 30 seconds for payment processing

// ❌ Too long - defeats the purpose
'timeout' => 300,  // 5 minutes is essentially no timeout
```

**Guidelines:**
- **Internal services**: 3-5 seconds
- **External APIs**: 10-15 seconds
- **Payment gateways**: 20-30 seconds
- **Webhooks**: 5-10 seconds
- **Background jobs**: Longer (30-60s), but still set a limit

### 2. Use Both Timeout Types
```php
// ✅ Best practice
$client->post($url, [
    'connect_timeout' => 3,   // Connection establishment
    'timeout' => 10,          // Total request duration
]);
```

**Why both?**
- `connect_timeout`: Protects against network issues (DNS, routing)
- `timeout`: Protects against slow responses from the server

### 3. Handle Timeout Exceptions Gracefully
```php
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

try {
    $response = $client->post($url, [
        'json' => $data,
        'timeout' => 10,
        'connect_timeout' => 3,
    ]);
} catch (ConnectException $e) {
    // Connection failed - network issue
    Log::error('API connection failed', [
        'url' => $url,
        'error' => $e->getMessage(),
    ]);
    
    // Return fallback or throw custom exception
    throw new ApiConnectionException('Payment gateway unavailable', 0, $e);
    
} catch (RequestException $e) {
    // Request timeout or HTTP error
    if ($e->hasResponse()) {
        $statusCode = $e->getResponse()->getStatusCode();
        Log::error('API request failed', [
            'url' => $url,
            'status' => $statusCode,
        ]);
    } else {
        // Timeout occurred
        Log::error('API request timeout', [
            'url' => $url,
            'timeout' => 10,
        ]);
    }
    
    throw new ApiTimeoutException('Payment processing timeout', 0, $e);
}
```

### 4. Combine with Circuit Breakers
```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;

// Create retry strategy (circuit breaker pattern)
$retryStrategy = new GenericRetryStrategy(
    statusCodes: [500, 502, 503, 504],
    delayMs: 1000,
    multiplier: 2,
    maxDelayMs: 10000,
);

$httpClient = HttpClient::create([
    'timeout' => 10,
]);

$retryableClient = new RetryableHttpClient(
    $httpClient,
    $retryStrategy,
    maxRetries: 3,
);

// Now your requests have both timeout AND retry logic
$response = $retryableClient->request('GET', $url);
```

### 5. Monitor Timeout Rates
```php
use Prometheus\CollectorRegistry;

$registry = new CollectorRegistry(...);
$counter = $registry->getOrRegisterCounter(
    'app',
    'http_timeouts_total',
    'HTTP request timeouts',
    ['service', 'endpoint']
);

try {
    $response = $client->post($url, ['timeout' => 10]);
} catch (RequestException $e) {
    if ($this->isTimeout($e)) {
        $counter->inc(['payment_api', '/charge']);
    }
    throw $e;
}
```

---

## Real-World Examples

### Example 1: E-commerce Checkout

**Scenario:** Payment processing during checkout
```php
class PaymentService
{
    private Client $httpClient;
    
    public function charge(Order $order): PaymentResult
    {
        try {
            $response = $this->httpClient->post(
                'https://payment-gateway.com/api/v1/charges',
                [
                    'json' => [
                        'amount' => $order->total,
                        'currency' => 'USD',
                        'order_id' => $order->id,
                    ],
                    'timeout' => 30,           // Payment APIs can be slow
                    'connect_timeout' => 5,    // But connection should be fast
                ]
            );
            
            return PaymentResult::success($response->toArray());
            
        } catch (ConnectException $e) {
            // Network issue - payment gateway unreachable
            Log::error('Payment gateway unreachable', [
                'order_id' => $order->id,
                'exception' => $e,
            ]);
            
            return PaymentResult::failure(
                'Payment service temporarily unavailable. Please try again.'
            );
            
        } catch (RequestException $e) {
            if ($this->isTimeout($e)) {
                // Timeout - we don't know if charge succeeded
                // IMPORTANT: Must check payment status separately!
                Log::warning('Payment charge timeout', [
                    'order_id' => $order->id,
                ]);
                
                // Queue background job to check payment status
                CheckPaymentStatus::dispatch($order->id)->delay(60);
                
                return PaymentResult::pending(
                    'Payment is being processed. You will receive confirmation shortly.'
                );
            }
            
            // Other HTTP error
            throw $e;
        }
    }
}
```

### Example 2: Webhook Delivery

**Scenario:** Sending webhooks to customer endpoints
```php
class WebhookService
{
    private Client $httpClient;
    
    public function notify(Webhook $webhook, Event $event): void
    {
        $attempt = 0;
        $maxAttempts = 3;
        
        while ($attempt < $maxAttempts) {
            try {
                $response = $this->httpClient->post(
                    $webhook->url,
                    [
                        'json' => $event->toArray(),
                        'timeout' => 10,        // Webhooks should respond quickly
                        'connect_timeout' => 3,
                        'headers' => [
                            'X-Webhook-Signature' => $this->sign($event),
                        ],
                    ]
                );
                
                // Success
                $webhook->recordSuccess();
                return;
                
            } catch (ConnectException | RequestException $e) {
                $attempt++;
                
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                
                if ($attempt >= $maxAttempts) {
                    $webhook->recordFailure();
                    throw new WebhookDeliveryException(
                        "Failed to deliver webhook after {$maxAttempts} attempts",
                        0,
                        $e
                    );
                }
                
                // Exponential backoff
                sleep(2 ** $attempt);
            }
        }
    }
}
```

### Example 3: Microservices Communication

**Scenario:** Service-to-service communication
```php
class UserService
{
    private HttpClientInterface $httpClient;
    
    public function getUserWithOrders(int $userId): array
    {
        // Call internal services with shorter timeouts
        try {
            // User data from user-service
            $userResponse = $this->httpClient->request(
                'GET',
                "http://user-service/api/users/{$userId}",
                ['timeout' => 3]  // Internal service - should be fast
            );
            
            $user = $userResponse->toArray();
            
            // Orders from order-service
            $ordersResponse = $this->httpClient->request(
                'GET',
                "http://order-service/api/users/{$userId}/orders",
                ['timeout' => 5]  // Might need DB query
            );
            
            $orders = $ordersResponse->toArray();
            
            return [
                'user' => $user,
                'orders' => $orders,
            ];
            
        } catch (TransportExceptionInterface $e) {
            // Service unavailable or timeout
            // Return degraded response
            Log::warning('Microservice call failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            // Return partial data with error flag
            return [
                'user' => $user ?? null,
                'orders' => [],
                'error' => 'Could not fetch complete user data',
            ];
        }
    }
}
```

---

## Troubleshooting

### False Positives

**Issue:** TimeoutGuard reports a timeout issue, but you believe it's configured correctly.

**Solution:**
1. Check that the timeout is in the correct argument position
2. Ensure the key is exactly `'timeout'` (not `'request_timeout'` or similar)
3. Verify the HTTP client variable name matches detection patterns

### False Negatives

**Issue:** TimeoutGuard doesn't detect an actual missing timeout.

**Solution:**
1. Check if the HTTP client is supported (Guzzle, Symfony HttpClient)
2. Verify the method call pattern matches detection rules
3. Report the issue with code example

### Performance

**Issue:** Analysis is slow on large codebases.

**Solution:**
1. Exclude `vendor/` and `tests/` directories
2. Run only on changed files in CI
3. Use `.ci-guard.yml` to configure exclusions

---

## Contributing

Want to improve TimeoutGuard? See our [Contributing Guide](../CONTRIBUTING.md).

**Ideas for contributions:**
- Support for additional HTTP clients (Pest HTTP, WordPress HTTP API)
- Configurable timeout thresholds
- Auto-fix capabilities
- Performance optimizations

---

## Learn More

- [Back to main README](../README.md)
- [CI-Guard Architecture](architecture.md)
- [Reversed Chaos Engineering](https://github.com/reversed-chaos-engineering/FOUNDATION)
