# CI-Guard

[![Quality Gates](https://github.com/phpresilience/ci-guard/workflows/Quality%20Gates/badge.svg)](https://github.com/phpresilience/ci-guard/actions/workflows/quality-gates.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/phpresilience/ci-guard/quality-gates.yml?label=tests)](https://github.com/phpresilience/ci-guard/actions)
[![codecov](https://codecov.io/gh/phpresilience/ci-guard/branch/main/graph/badge.svg)](https://codecov.io/gh/phpresilience/ci-guard)
[![Latest Version](https://img.shields.io/packagist/v/phpresilience/ci-guard)](https://packagist.org/packages/phpresilience/ci-guard)

> **Prevent production incidents before they happen**  
> Static analysis and resilience checks for PHP applications

CI-Guard is a comprehensive static analysis tool designed to identify resilience anti-patterns in your PHP codebase. By catching potential production issues during development and CI/CD, it helps you build more reliable applications and avoid costly incidents.

## 🎯 Vision

Every production incident has a signature - a pattern in the code that makes it predictable. CI-Guard's mission is to detect these patterns **before** code reaches production, transforming reactive incident response into proactive prevention.

Inspired by [Reversed Chaos Engineering (RCE)](https://github.com/reversed-chaos-engineering/FOUNDATION) principles, CI-Guard learns from known failure patterns to protect your applications.

## 🚀 Why CI-Guard?

**The Problem:**  
Production incidents are expensive - in terms of revenue, user trust, and engineering time. Many incidents share common root causes:
- HTTP calls without timeouts → hanging requests, worker pool exhaustion
- Missing circuit breakers → cascading failures
- N+1 queries → database overload under load
- Memory leaks → OOM kills and service crashes

**The Solution:**  
CI-Guard detects these patterns through static analysis, giving you instant feedback in your development workflow and CI pipeline.

**Think of it as:**
- 🛡️ A safety net for your deployment pipeline
- 📊 A resilience linter for PHP
- 🎓 A learning tool that educates your team on reliability patterns
- 🔍 An automated code reviewer focused on production stability

## ✨ Current Features

### 🔌 TimeoutGuard - HTTP Timeout Detection

Detects HTTP calls without proper timeout configuration that can cause:
- Worker pool exhaustion
- Cascading failures
- Application-wide unresponsiveness

**Supported HTTP Clients:**
- ✅ Guzzle HTTP Client
- ✅ Symfony HttpClient
- 🚧 cURL (coming soon)
- 🚧 WordPress HTTP API (coming soon)

**Example:**
```bash
$ vendor/bin/ci-guard ./src

❌ Found 3 issue(s):

📄 ./src/Service/PaymentService.php
  ⚠️ Line 42: Guzzle HTTP request without timeout configuration (Guzzle post)
  
    // Add timeout configuration:
    $response = $client->request('POST', $url, [
        'timeout' => 10,         // Total request timeout
        'connect_timeout' => 3,  // Connection timeout
    ]);
```

[📖 Read full documentation on HTTP Timeouts](docs/timeout-guard.md)

## 🗺️ Roadmap

CI-Guard is evolving into a comprehensive resilience analysis platform:

### ✅ Phase 1: TimeoutGuard (Current)
- [x] Guzzle detection
- [x] Symfony HttpClient detection
- [x] CLI reporter
- [ ] cURL detection
- [x] JSON reporter
- [ ] Configurable rules

### 🔄 Phase 2: CircuitBreakerGuard (Q2 2025)
- [ ] Detect missing circuit breaker patterns
- [ ] Retry strategy validation
- [ ] Fallback behavior checks
- [ ] External dependency mapping

### 📊 Phase 3: QueryGuard (Q3 2025)
- [ ] N+1 query detection (Doctrine)
- [ ] N+1 query detection (Eloquent)
- [ ] Slow query patterns
- [ ] Missing database indexes

### 🧠 Phase 4: MemoryGuard (Q4 2025)
- [ ] Memory leak patterns
- [ ] Resource exhaustion risks
- [ ] Large dataset handling

### 🎯 Phase 5: Full Resilience Platform (2026)
- [ ] Custom detector plugins
- [ ] Baseline comparisons
- [ ] Performance regression detection
- [ ] GitHub App integration
- [ ] ML-powered pattern recognition

## 📦 Installation
```bash
composer require --dev phpresilience/ci-guard
```

## 🚀 Quick Start

### Command Line
```bash
# Analyze your source directory
vendor/bin/ci-guard ./src

# Analyze specific files
vendor/bin/ci-guard ./src/Service/PaymentService.php
```

### CI Integration

#### GitHub Actions
```yaml
name: Resilience Check

on: [pull_request]

jobs:
  ci-guard:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run CI-Guard
        run: vendor/bin/ci-guard ./src
```

#### GitLab CI
```yaml
ci-guard:
  stage: test
  script:
    - composer install
    - vendor/bin/ci-guard ./src
  only:
    - merge_requests
```

## 📖 Documentation

- [HTTP Timeout Detection](docs/timeout-guard.md) - Comprehensive guide on TimeoutGuard
- [Architecture](docs/architecture.md) - How CI-Guard works under the hood
- [Contributing](CONTRIBUTING.md) - How to contribute to the project

## 🎓 Understanding Resilience Patterns

### What are timeout configurations?

Timeouts are critical safeguards that prevent your application from hanging indefinitely when external services are slow or unresponsive.

**Without timeout:**
```php
// ❌ Dangerous - can hang for minutes
$response = $client->post('https://payment-api.com/charge', [
    'json' => ['amount' => 100],
]);
```

**What happens:**
1. Payment API is experiencing issues (30s response time)
2. PHP-FPM worker waits indefinitely
3. More requests arrive, more workers get blocked
4. Worker pool exhausts (all 50 workers blocked)
5. New requests start queueing → Application appears down
6. Users see timeouts, revenue is lost

**With timeout:**
```php
// ✅ Safe - fails fast after 10 seconds
$response = $client->post('https://payment-api.com/charge', [
    'json' => ['amount' => 100],
    'timeout' => 10,
    'connect_timeout' => 3,
]);
```

**What happens:**
1. Payment API is slow (30s response time)
2. Request times out after 10s (configured)
3. Worker is released immediately
4. Application shows error but stays responsive
5. Circuit breaker can kick in (if implemented)
6. Users see error message, can retry

[Learn more about resilience patterns →](docs/timeout-guard.md)

## 📊 Real-World Impact
```
Before CI-Guard:
├─ Incident: Payment API slowdown
├─ Impact: 15 minutes downtime
├─ Lost Revenue: $50,000
└─ Engineering Time: 4 hours debugging + postmortem

After CI-Guard:
├─ Detection: During code review (PR comment)
├─ Impact: None (caught before production)
├─ Lost Revenue: $0
└─ Engineering Time: 5 minutes to add timeout
```

## 🤝 Contributing

We welcome contributions! Whether you want to:

- 🐛 Report bugs
- 💡 Suggest new detectors
- 📝 Improve documentation
- 🔧 Submit code

Please check our [Contributing Guide](CONTRIBUTING.md).

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

**Built with:**
- [nikic/php-parser](https://github.com/nikic/PHP-Parser) - PHP parsing and analysis

**Inspired by:**
- [Reversed Chaos Engineering (RCE)](https://github.com/reversed-chaos-engineering/FOUNDATION) - Systematic incident analysis
- The PHP reliability and SRE communities

## 📬 Stay Updated

- ⭐ Star this repo to follow development
- 🐦 Follow updates on Twitter [@phpresilience](https://twitter.com/phpresilience) (WIP)
- 💬 Join our [Discord community](https://discord.gg/phpresilience) (WIP)

---

**Made with ❤️ for the PHP community**

*Building more resilient PHP applications, one check at a time.*