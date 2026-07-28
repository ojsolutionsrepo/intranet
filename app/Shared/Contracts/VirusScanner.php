<?php

namespace App\Shared\Contracts;

/**
 * Virus scan for uploads — ClamAV in staging/prod; Null scanner locally/tests.
 */
interface VirusScanner
{
    public function name(): string;

    /**
     * @return array{clean: bool, engine: string, signature: string|null, message: string}
     */
    public function scan(string $path): array;

    public function health(): array;
}
