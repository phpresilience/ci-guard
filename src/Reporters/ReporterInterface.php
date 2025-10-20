<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Reporters;

use Phpresilience\CiGuard\Models\Issue;

interface ReporterInterface
{
    /**
     * @param array<Issue> $issues
     */
    public function report(array $issues): void;
}
