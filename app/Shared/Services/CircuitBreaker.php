<?php

namespace App\Shared\Services;

use App\Shared\Models\IntegrationHealth;
use Carbon\Carbon;
use Throwable;

/**
 * Resilience wrapper: timeout bookkeeping, retry, circuit breaker, health rows.
 */
final class CircuitBreaker
{
    public function __construct(
        private readonly string $name,
        private readonly int $failureThreshold = 5,
        private readonly int $openSeconds = 60,
        private readonly int $maxAttempts = 3,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function call(callable $callback, string $driver = 'unknown'): mixed
    {
        $health = $this->healthRow($driver);

        if ($health->circuit === 'open') {
            $opened = $health->opened_at ? Carbon::parse($health->opened_at) : null;
            if ($opened && $opened->diffInSeconds(now()) < $this->openSeconds) {
                throw new \RuntimeException("Circuit open for {$this->name}: {$health->message}");
            }
            $health->update(['circuit' => 'half_open']);
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxAttempts) {
            $attempt++;
            try {
                $result = $callback();
                $health->update([
                    'status' => 'ok',
                    'circuit' => 'closed',
                    'failure_count' => 0,
                    'opened_at' => null,
                    'last_success_at' => now(),
                    'message' => 'OK',
                    'driver' => $driver,
                ]);

                return $result;
            } catch (Throwable $e) {
                $lastError = $e;
                usleep((int) (100000 * (2 ** ($attempt - 1)))); // exponential backoff
            }
        }

        $failures = $health->failure_count + 1;
        $open = $failures >= $this->failureThreshold;

        $health->update([
            'status' => $open ? 'down' : 'degraded',
            'circuit' => $open ? 'open' : 'half_open',
            'failure_count' => $failures,
            'opened_at' => $open ? now() : $health->opened_at,
            'last_failure_at' => now(),
            'message' => $lastError?->getMessage() ?? 'Unknown failure',
            'driver' => $driver,
        ]);

        throw $lastError ?? new \RuntimeException("{$this->name} failed");
    }

    public function recordSync(string $driver, string $message = 'Synced'): void
    {
        $this->healthRow($driver)->update([
            'last_sync_at' => now(),
            'last_success_at' => now(),
            'status' => 'ok',
            'circuit' => 'closed',
            'failure_count' => 0,
            'message' => $message,
            'driver' => $driver,
        ]);
    }

    public function markDown(string $driver, string $message): void
    {
        $this->healthRow($driver)->update([
            'status' => 'down',
            'circuit' => 'open',
            'opened_at' => now(),
            'last_failure_at' => now(),
            'message' => $message,
            'driver' => $driver,
            'failure_count' => $this->failureThreshold,
        ]);
    }

    private function healthRow(string $driver): IntegrationHealth
    {
        return IntegrationHealth::query()->firstOrCreate(
            ['name' => $this->name],
            [
                'driver' => $driver,
                'status' => 'unknown',
                'circuit' => 'closed',
                'failure_count' => 0,
            ],
        );
    }
}
