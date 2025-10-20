<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Detectors;

use Phpresilience\CiGuard\Models\Issue;

interface DetectorInterface
{
    /**
     * @return array<Issue>
     */
    public function getIssues(): array;

    public function reset(): void;
}
