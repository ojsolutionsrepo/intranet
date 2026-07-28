<?php

namespace App\Shared\Security;

use App\Shared\Contracts\VirusScanner;

/**
 * Local/test stand-in — always clean unless EICAR signature is present.
 */
final class NullVirusScanner implements VirusScanner
{
    public function name(): string
    {
        return 'null';
    }

    public function scan(string $path): array
    {
        $contents = '';
        if (is_file($path)) {
            $contents = (string) (@file_get_contents($path) ?: '');
        }

        $eicar = str_contains($contents, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE')
            || str_contains($contents, 'OJ-INTRANET-VIRUS-TEST-SIGNATURE');

        return [
            'clean' => ! $eicar,
            'engine' => $this->name(),
            'signature' => $eicar
                ? (str_contains($contents, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE') ? 'EICAR' : 'OJ-TEST')
                : null,
            'message' => $eicar ? 'Blocked malware test signature' : 'Scan skipped (null engine)',
        ];
    }

    public function health(): array
    {
        return [
            'ok' => true,
            'driver' => $this->name(),
            'message' => 'Null scanner (enable ClamAV via VIRUS_SCANNER=clamav)',
        ];
    }
}
