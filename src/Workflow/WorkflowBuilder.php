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

namespace Sassnowski\Venture\Workflow;

use Illuminate\Container\Container;
use Sassnowski\Venture\Manager\WorkflowManagerInterface;
use Sassnowski\Venture\Models\Workflow;
use Sassnowski\Venture\WorkflowDefinition;

abstract class WorkflowBuilder
{
    public static function start(): Workflow
    {
        /** @psalm-suppress TooManyArguments, UnsafeInstantiation */
        return (new static(...\func_get_args()))->run();
    }

    abstract public function definition(): WorkflowDefinition;

    public function beforeCreate(Workflow $workflow): void
    {
    }

    public function beforeNesting(array $jobs): void
    {
    }

    private function run(): Workflow
    {
        /** @var WorkflowManagerInterface $manager */
        $manager = Container::getInstance()->make('venture.manager');

        return $manager->startWorkflow($this);
    }
}