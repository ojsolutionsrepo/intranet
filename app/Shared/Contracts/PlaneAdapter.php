<?php

namespace App\Shared\Contracts;

/**
 * Plane.so project source.
 */
interface PlaneAdapter
{
    public function name(): string;

    /**
     * @return list<array{external_ref: string, name: string, status: string, summary: ?string, rag: ?string, deep_link: ?string, metrics: array<string, mixed>, milestones: list<array{title: string, due_on: ?string, status: string}>}>
     */
    public function fetchProjects(): array;

    /**
     * @return list<array{title: string, due_on: ?string, status: string}>
     */
    public function fetchMilestones(string $externalRef): array;

    public function deepLink(string $externalRef): ?string;

    public function health(): array;
}
