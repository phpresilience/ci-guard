<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Models;

class Issue
{
    public function __construct(
        public int $line,
        public string $type,
        public string $library,
        public string $method,
        public string $severity,
        public string $message,
        public string $suggestion,
        public ?string $file = null,
    ) {}
}
