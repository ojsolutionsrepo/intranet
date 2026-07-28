<?php

namespace App\Shared\Security;

use App\Shared\Contracts\VirusScanner;
use App\Shared\Services\CircuitBreaker;

/**
 * ClamAV daemon (clamd) via TCP — fails closed when unreachable and circuit opens.
 */
final class ClamAvScanner implements VirusScanner
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 3310,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('clamav'),
    ) {}

    public function name(): string
    {
        return 'clamav';
    }

    public function scan(string $path): array
    {
        return $this->breaker->call(function () use ($path) {
            if (! is_file($path)) {
                throw new \RuntimeException('File missing for virus scan.');
            }

            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
            if ($socket === false) {
                throw new \RuntimeException("ClamAV unreachable: {$errstr} ({$errno})");
            }

            $size = filesize($path);
            fwrite($socket, "zINSTREAM\0");
            $handle = fopen($path, 'rb');
            while (! feof($handle)) {
                $chunk = fread($handle, 2048);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }
            fclose($handle);
            fwrite($socket, pack('N', 0));

            $response = stream_get_contents($socket) ?: '';
            fclose($socket);

            $clean = str_contains($response, 'OK') && ! str_contains($response, 'FOUND');
            $signature = null;
            if (preg_match('/:\s*(.+)\s+FOUND/', $response, $m)) {
                $signature = trim($m[1]);
            }

            return [
                'clean' => $clean,
                'engine' => $this->name(),
                'signature' => $signature,
                'message' => trim($response) ?: 'No response',
            ];
        }, $this->name());
    }

    public function health(): array
    {
        try {
            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 2);
            if ($socket === false) {
                return ['ok' => false, 'driver' => $this->name(), 'message' => "ClamAV down: {$errstr}"];
            }
            fwrite($socket, "zPING\0");
            $pong = stream_get_contents($socket) ?: '';
            fclose($socket);

            return [
                'ok' => str_contains($pong, 'PONG'),
                'driver' => $this->name(),
                'message' => str_contains($pong, 'PONG') ? 'ClamAV PONG' : 'Unexpected ClamAV response',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'driver' => $this->name(), 'message' => $e->getMessage()];
        }
    }
}
