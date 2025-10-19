<?php

declare(strict_types=1);

namespace Phpresilience\CiGuard\Reporters;

interface ReporterInterface {
    public function report(array $issues): void;
}
