<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/venture
 */

namespace Sassnowski\Venture\Persistence\Database;

use DateTimeImmutable;
use Sassnowski\Venture\DTO\WorkflowJob;
use stdClass;

final class WorkflowJobFactory
{
    public function hydrateWorkflowJob(stdClass $row): WorkflowJob
    {
        return new WorkflowJob(
            $row->uuid,
            \explode(',', $row->edges ?? ''),
            $row->failed_at ? new DateTimeImmutable($row->failed_at) : null,
        );
    }
}