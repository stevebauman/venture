<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/venture
 */

namespace Sassnowski\Venture\Testing;

use Closure;
use PHPUnit\Framework\Assert;
use Sassnowski\Venture\AbstractWorkflow;
use Sassnowski\Venture\Graph\Node;
use Sassnowski\Venture\Models\Workflow;
use Sassnowski\Venture\WorkflowDefinition;
use Sassnowski\Venture\WorkflowStepInterface;
use Throwable;

final class WorkflowTester
{
    private WorkflowDefinition $definition;

    public function __construct(private AbstractWorkflow $workflow)
    {
        $this->definition = $this->workflow->definition();
    }

    /**
     * @param null|Closure(WorkflowStepInterface): bool $callback
     */
    public function assertJobExists(string $jobID, ?Closure $callback = null): void
    {
        $node = $this->mustFindNode($jobID);

        if (null !== $callback) {
            Assert::assertTrue(
                $callback($node->getJob()),
                "Workflow contains expected job {$jobID} but callback returned false",
            );
        }
    }

    /**
     * @param array<int, string> $dependencies
     */
    public function assertJobExistsWithDependencies(string $jobID, array $dependencies): void
    {
        $node = $this->mustFindNode($jobID);

        $this->assertDependenciesMatch(
            $node,
            $dependencies,
            "Workflow contains job {$jobID} but with incorrect dependencies.",
        );
    }

    public function assertJobExistsOnConnection(string $jobID, string $connection): void
    {
        $node = $this->mustFindNode($jobID);

        Assert::assertTrue(
            $node->getJob()->getConnection() === $connection,
            $this->formatAssertErrorMessage(
                "Workflow contains job {$jobID} but on different connection",
                $connection,
                $node->getJob()->getConnection(),
            ),
        );
    }

    /**
     * @param null|Closure(WorkflowStepInterface): bool $callback
     */
    public function assertJobMissing(string $jobID, ?Closure $callback = null): void
    {
        $node = $this->getNode($jobID);
        $message = "Workflow contains unexpected job {$jobID}";

        if (null === $callback) {
            Assert::assertNull($node, $message);
        } else {
            Assert::assertTrue(
                null === $node || !$callback($node->getJob()),
                $message,
            );
        }
    }

    /**
     * @param null|array<int, string> $dependencies
     */
    public function assertGatedJobExists(string $jobID, ?array $dependencies = null): void
    {
        $node = $this->mustFindNode($jobID);

        Assert::assertTrue(
            $node->getJob()->isGated(),
            "Workflow contains unexpected non-gated job with id {$jobID}. Expected it to be gated",
        );

        if (null !== $dependencies) {
            $this->assertDependenciesMatch(
                $node,
                $dependencies,
                "Workflow contains expected gated job {$jobID} but with incorrect dependencies",
            );
        }
    }

    /**
     * @param null|Closure(Workflow): void $configureWorkflowCallback
     */
    public function runThenCallback(?Closure $configureWorkflowCallback = null): void
    {
        $this->getWorkflow($configureWorkflowCallback)
            ->runThenCallback();
    }

    /**
     * @param null|Closure(Workflow): void $configureWorkflowCallback
     */
    public function runCatchCallback(
        WorkflowStepInterface $failedJob,
        Throwable $exception,
        ?Closure $configureWorkflowCallback = null,
    ): void {
        $this->getWorkflow($configureWorkflowCallback)
            ->runCatchCallback($failedJob, $exception);
    }

    private function getWorkflow(?Closure $callback = null): Workflow
    {
        $definition = $this->definition;

        [$workflow, $_] = $definition->build();

        if (null !== $callback) {
            $callback($workflow);
        }

        return $workflow;
    }

    private function mustFindNode(string $jobID): Node
    {
        $node = $this->getNode($jobID);

        Assert::assertNotNull(
            $node,
            "Workflow does not contain expected job {$jobID}",
        );

        /** @var Node $node */
        return $node;
    }

    private function getNode(string $jobID): ?Node
    {
        $this->buildGraph();

        $graph = $this->definition->graph();

        return $graph->get($jobID);
    }

    private function buildGraph(): void
    {
        $graph = $this->definition->graph();

        foreach ($this->definition->jobs() as $jobID => $job) {
            $job
                ->withDependantJobs($graph->getDependantJobs($jobID))
                ->withDependencies($graph->getDependencies($jobID));
        }
    }

    /**
     * @param array<int, string> $expectedDependencies
     */
    private function assertDependenciesMatch(
        Node $node,
        array $expectedDependencies,
        string $message,
    ): void {
        $nodeDependencies = $node->getDependencyIDs();

        \sort($nodeDependencies);
        \sort($expectedDependencies);

        Assert::assertEquals(
            $expectedDependencies,
            $nodeDependencies,
            $this->formatAssertErrorMessage(
                $message,
                $expectedDependencies,
                $nodeDependencies,
            ),
        );
    }

    private function formatAssertErrorMessage(string $message, mixed $expected, mixed $actual): string
    {
        $template = <<<'TEXT'
%s

Expected
%s

Actual
%s

TEXT;

        return \sprintf(
            $template,
            $message,
            \json_encode($expected, \JSON_PRETTY_PRINT),
            \json_encode($actual, \JSON_PRETTY_PRINT),
        );
    }
}