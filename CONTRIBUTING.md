# Contributing to CI-Guard

First off, **thank you** for considering contributing to CI-Guard! 🎉

CI-Guard is built by developers who have experienced production incidents and want to help others avoid them. Every contribution, no matter how small, helps make PHP applications more resilient.

## 🌟 Ways to Contribute

You don't need to be a PHP expert or write code to contribute. Here are many ways you can help:

### 🐛 Report Bugs or False Positives
Found a bug? CI-Guard detecting something incorrectly? Let us know!

### 💡 Suggest Features or New Detectors
Have an idea for a new resilience check? Share it!

### 📝 Improve Documentation
Fix typos, clarify explanations, add examples.

### 🔧 Submit Code
Fix bugs, add features, improve performance.

### 🧪 Write Tests
Help us catch bugs before they reach users.

### 💬 Help Others
Answer questions in issues and discussions.

### 📢 Spread the Word
Tweet, blog, or talk about CI-Guard.

## 🚀 Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Setup Development Environment

1. **Fork the repository** on GitHub

2. **Clone your fork**
````bash
git clone https://github.com/YOUR_USERNAME/ci-guard.git
cd ci-guard
````

3. **Add upstream remote**
````bash
git remote add upstream https://github.com/phpresilience/ci-guard.git
````

4. **Install dependencies**
````bash
composer install
````

5. **Run tests to verify setup**
````bash
composer test
````

You should see all tests passing ✅

### Development Workflow

1. **Create a feature branch**
````bash
git checkout -b feature/my-awesome-feature
# or
git checkout -b fix/issue-123
````

2. **Make your changes**
    - Write code
    - Add tests
    - Update documentation

3. **Run tests locally**
````bash
composer test
````

4. **Commit your changes**
````bash
git add .
git commit -m "feat: add awesome feature"
````

Follow [Conventional Commits](https://www.conventionalcommits.org/) format:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `test:` - Adding or updating tests
- `refactor:` - Code refactoring
- `perf:` - Performance improvements
- `chore:` - Maintenance tasks

5. **Push to your fork**
````bash
git push origin feature/my-awesome-feature
````

6. **Create a Pull Request** on GitHub

## 📋 Pull Request Guidelines

### Before Submitting

- ✅ All tests pass (`composer test`)
- ✅ Code follows project style
- ✅ New features include tests
- ✅ Documentation is updated
- ✅ Commit messages follow conventions

### PR Description Template
````markdown
## Description
Brief description of what this PR does

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issue
Fixes #(issue number)

## Testing
How did you test this?

## Screenshots (if applicable)
Add screenshots or terminal output

## Checklist
- [ ] Tests pass locally
- [ ] Added tests for new features
- [ ] Updated documentation
- [ ] Followed code style guidelines
````

### Review Process

1. A maintainer will review your PR within 1-3 days
2. They may request changes or ask questions
3. Make requested changes and push new commits
4. Once approved, a maintainer will merge your PR

## 🏗️ Project Structure
````
ci-guard/
├── bin/
│   └── ci-guard              # CLI executable
├── src/
│   ├── Analyzer.php          # Main analyzer
│   ├── Detectors/            # All detectors
│   │   ├── TimeoutGuard/
│   │   │   ├── GuzzleDetector.php
│   │   │   ├── SymfonyHttpDetector.php
│   │   │   └── CurlDetector.php
│   │   └── DetectorInterface.php
│   ├── Models/
│   │   └── Issue.php         # Issue representation
│   └── Reporters/
│       ├── DefaultReporter.php
│       └── ReporterInterface.php
├── tests/
│   ├── Unit/                 # Unit tests
│   ├── Integration/          # Integration tests
│   └── Fixtures/             # Test fixtures
├── docs/                     # Documentation
└── composer.json
````

## 🔨 Development Guidelines

### Code Style

We follow PSR-12 coding standards:
````php
<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Detectors\TimeoutGuard;

class ExampleDetector implements DetectorInterface
{
    private array $issues = [];
    
    public function analyze(string $code): array
    {
        // Implementation
    }
}
````

**Key points:**
- Use strict types: `declare(strict_types=1);`
- Type hints on all parameters and return types
- Properties and constants should be typed
- 4 spaces for indentation
- Opening braces on same line

### Writing Tests

Every new feature or bug fix should include tests:
````php
<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Tests\Unit\Detectors;

use PHPUnit\Framework\TestCase;

class MyDetectorTest extends TestCase
{
    public function testDetectsSomething(): void
    {
        $code = <<<'PHP'
        <?php
        // Test code here
        PHP;

        $detector = new MyDetector();
        $issues = $detector->analyze($code);

        $this->assertCount(1, $issues);
    }
    
    public function testDoesNotDetectValidCode(): void
    {
        $code = <<<'PHP'
        <?php
        // Valid code here
        PHP;

        $detector = new MyDetector();
        $issues = $detector->analyze($code);

        $this->assertCount(0, $issues);
    }
}
````

**Test naming:**
- Use descriptive names: `testDetectsMissingTimeout()`
- One assertion per test when possible
- Test both positive and negative cases

### Adding a New Detector

1. **Create the detector class**
````php
// src/Detectors/YourGuard/YourDetector.php
<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Detectors\YourGuard;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Phpresilience\CiGuard\Models\Issue;

class YourDetector extends NodeVisitorAbstract
{
    private array $issues = [];
    
    public function enterNode(Node $node)
    {
        // Your detection logic here
        
        if ($this->detectsIssue($node)) {
            $this->issues[] = new Issue(
                line: $node->getLine(),
                type: 'your_issue_type',
                library: 'Library Name',
                method: 'methodName',
                severity: 'high',
                message: 'Description of the issue',
                suggestion: $this->generateSuggestion(),
            );
        }
        
        return null;
    }
    
    public function getIssues(): array
    {
        return $this->issues;
    }
    
    public function reset(): void
    {
        $this->issues = [];
    }
    
    private function detectsIssue(Node $node): bool
    {
        // Detection logic
    }
    
    private function generateSuggestion(): string
    {
        return <<<'PHP'
        // Suggested fix
        PHP;
    }
}
````

2. **Register in Analyzer**
````php
// src/Analyzer.php
private function loadDetectors(): array
{
    return [
        // Existing detectors...
        new YourGuard\YourDetector(),
    ];
}
````

3. **Write tests**
````php
// tests/Unit/Detectors/YourDetectorTest.php
````

4. **Add documentation**
````markdown
# docs/your-guard.md
````

5. **Update README**
   Add your detector to the features list and roadmap.

## 🎯 Areas Where We Need Help

### High Priority

- **cURL Detection** - Complete the CurlDetector implementation
- **Circuit Breaker Detection** - New detector for missing circuit breaker patterns
- **N+1 Query Detection** - Detect N+1 queries in Doctrine/Eloquent
- **JSON Reporter** - Alternative output format for CI integration

### Medium Priority

- **WordPress HTTP API Support** - Detect timeouts in WP HTTP calls
- **Laravel HTTP Client** - Support for Laravel's HTTP facade
- **Configuration File** - Support for `.ci-guard.yml`
- **Performance Optimization** - Make analysis faster for large codebases

### Documentation

- **Real-world Examples** - More code examples from production scenarios
- **Video Tutorials** - Screencasts showing CI-Guard in action
- **Blog Posts** - Write about resilience patterns
- **Translations** - Translate docs to other languages

### Testing

- **Integration Tests** - Test full analysis pipeline
- **Fixture Library** - More test fixtures for edge cases
- **Performance Benchmarks** - Measure analysis speed

## 🐛 Reporting Bugs

**Before reporting:**
1. Check if the bug is already reported in [Issues](https://github.com/phpresilience/ci-guard/issues)
2. Try the latest version from `main` branch
3. Collect information about your environment

**Bug Report Template:**
````markdown
## Bug Description
Clear description of the bug

## Steps to Reproduce
1. Run ci-guard on...
2. See error...

## Expected Behavior
What you expected to happen

## Actual Behavior
What actually happened

## Environment
- PHP Version: 8.3
- CI-Guard Version: 0.1.0
- OS: macOS 14.0

## Code Sample
```php
// Minimal code that reproduces the issue
```

## Additional Context
Any other relevant information
````

## 💡 Suggesting Features

**Feature Request Template:**
````markdown
## Feature Description
Clear description of the feature

## Problem It Solves
What problem does this feature solve?

## Proposed Solution
How should this work?

## Example Usage
```php
// Show how it would be used
```

## Alternatives Considered
Other ways to solve this problem

## Additional Context
Any other relevant information
````

## 🤝 Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inspiring community for everyone.

### Our Standards

**Positive behavior:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behavior:**
- Trolling, insulting/derogatory comments
- Public or private harassment
- Publishing others' private information
- Other conduct which could reasonably be considered inappropriate

### Enforcement

Project maintainers have the right and responsibility to remove, edit, or reject comments, commits, code, issues, and other contributions that don't align with this Code of Conduct.

### Reporting

Instances of abusive, harassing, or otherwise unacceptable behavior may be reported by contacting the project team at [conduct@phpresilience.dev](mailto:conduct@phpresilience.dev).

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT License.

## 🙏 Recognition

Contributors will be recognized in:
- `CONTRIBUTORS.md` file
- Release notes
- Project README (for significant contributions)

## 💬 Questions?

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Discord**: [Join our community](https://discord.gg/phpresilience)
- **Twitter**: [@phpresilience](https://twitter.com/phpresilience)

---

## 🎓 Learning Resources

New to PHP parsing or static analysis? Here are some resources:

### PHP Parser
- [nikic/PHP-Parser Documentation](https://github.com/nikic/PHP-Parser/tree/master/doc)
- [PHP AST Explorer](https://php-ast-explorer.com/)

### Testing
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

### Resilience Patterns
- [Reversed Chaos Engineering Paper](https://github.com/reversed-chaos-engineering/FOUNDATION)
- [Release It! by Michael Nygard](https://pragprog.com/titles/mnee2/release-it-second-edition/)
- [Site Reliability Engineering](https://sre.google/books/)

---

## 🚀 Your First Contribution

Not sure where to start? Look for issues labeled:
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention needed
- `documentation` - Documentation improvements

**Example first contributions:**
1. Fix a typo in documentation
2. Add a test case for an existing detector
3. Improve error messages
4. Add code examples to docs

Remember: **No contribution is too small**. We appreciate all efforts to improve CI-Guard!

---

**Thank you for contributing to CI-Guard!** 🎉

Together, we're making PHP applications more resilient, one check at a time.