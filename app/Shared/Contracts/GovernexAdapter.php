<?php

namespace App\Shared\Contracts;

/**
 * Governex project/finance source — API or CSV via the same contract.
 */
interface GovernexAdapter
{
    public function name(): string;

    /**
     * @return list<array{external_ref: string, name: string, status: string, summary: ?string, rag: ?string, deep_link: ?string, metrics: array<string, mixed>, milestones: list<array{title: string, due_on: ?string, status: string}>}>
     */
    public function fetchProjects(): array;

    public function health(): array;
}
