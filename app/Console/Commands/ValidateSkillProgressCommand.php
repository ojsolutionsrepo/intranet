<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ValidateSkillProgressCommand extends Command
{
    protected $signature = 'intranet:progress
                            {--phase= : Only report a single phase number (0-5)}
                            {--write : Write docs/IMPLEMENTATION_PROGRESS.md}
                            {--json : Output machine-readable JSON}
                            {--strict : Exit 1 if any non-manual check fails}';

    protected $description = 'Validate oj-intranet SKILL.md integrity and report phase implementation progress';

    public function handle(): int
    {
        $base = base_path();
        $checksPath = $base.DIRECTORY_SEPARATOR.'.cursor'.DIRECTORY_SEPARATOR.'skills'.DIRECTORY_SEPARATOR.'oj-intranet'.DIRECTORY_SEPARATOR.'progress-checks.json';

        if (! is_file($checksPath)) {
            $this->error('Missing progress-checks.json at '.$checksPath);

            return self::FAILURE;
        }

        /** @var array<string, mixed> $config */
        $config = json_decode((string) file_get_contents($checksPath), true, 512, JSON_THROW_ON_ERROR);

        $skillReport = $this->validateSkill($base, $config);
        $phaseReports = $this->evaluatePhases($base, $config);

        $filter = $this->option('phase');
        if ($filter !== null && $filter !== '') {
            $phaseReports = array_values(array_filter(
                $phaseReports,
                fn (array $p) => (string) $p['id'] === (string) $filter,
            ));
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'skill' => $skillReport,
            'phases' => $phaseReports,
            'summary' => $this->summarise($skillReport, $phaseReports),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderConsole($payload);
        }

        if ($this->option('write')) {
            $path = $this->writeMarkdown($payload);
            $this->info('Wrote '.$path);
        }

        if ($this->option('strict')) {
            $failed = ! $skillReport['ok'] || collect($phaseReports)
                ->flatMap(fn (array $p) => $p['checks'])
                ->contains(fn (array $c) => $c['status'] === 'fail');

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{ok: bool, path: string, missing_sections: list<string>, missing_references: list<string>, notes: list<string>}
     */
    private function validateSkill(string $base, array $config): array
    {
        $skillRoot = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, (string) $config['skill_root']);
        $skillFile = $skillRoot.DIRECTORY_SEPARATOR.(string) $config['skill_file'];
        $notes = [];
        $missingSections = [];
        $missingRefs = [];

        if (! is_file($skillFile)) {
            return [
                'ok' => false,
                'path' => $skillFile,
                'missing_sections' => ['(SKILL.md missing)'],
                'missing_references' => [],
                'notes' => ['Skill file not found'],
            ];
        }

        $contents = (string) file_get_contents($skillFile);

        foreach ($config['required_skill_sections'] as $section) {
            if (! str_contains($contents, $section)) {
                $missingSections[] = $section;
            }
        }

        foreach ($config['required_references'] as $ref) {
            $path = $skillRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ref);
            if (! is_file($path)) {
                $missingRefs[] = $ref;
            }
        }

        if (! is_file($skillRoot.DIRECTORY_SEPARATOR.'progress-checks.json')) {
            $notes[] = 'progress-checks.json should live beside SKILL.md';
        }

        $ok = $missingSections === [] && $missingRefs === [];

        return [
            'ok' => $ok,
            'path' => $skillFile,
            'missing_sections' => $missingSections,
            'missing_references' => $missingRefs,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    private function evaluatePhases(string $base, array $config): array
    {
        $reports = [];

        foreach ($config['phases'] as $phase) {
            $checks = [];
            foreach ($phase['checks'] as $check) {
                $checks[] = $this->evaluateCheck($base, $check);
            }

            $done = count(array_filter($checks, fn (array $c) => $c['status'] === 'pass'));
            $manual = count(array_filter($checks, fn (array $c) => $c['status'] === 'manual'));
            $fail = count(array_filter($checks, fn (array $c) => $c['status'] === 'fail'));
            $total = count($checks);
            $autoTotal = max(1, $total - $manual);
            $pct = (int) round(100 * $done / $autoTotal);

            $status = match (true) {
                $fail === 0 && $done === $autoTotal && $manual === 0 => 'complete',
                $fail === 0 && $done === $autoTotal => 'complete_pending_manual',
                $done === 0 => 'not_started',
                default => 'in_progress',
            };

            $reports[] = [
                'id' => $phase['id'],
                'name' => $phase['name'],
                'gate' => $phase['gate'],
                'status' => $status,
                'done' => $done,
                'failed' => $fail,
                'manual' => $manual,
                'total' => $total,
                'percent' => min(100, $pct),
                'checks' => $checks,
            ];
        }

        return $reports;
    }

    /**
     * @param  array<string, mixed>  $check
     * @return array{id: string, label: string, status: string, detail: string}
     */
    private function evaluateCheck(string $base, array $check): array
    {
        if (! empty($check['manual'])) {
            return [
                'id' => (string) $check['id'],
                'label' => (string) $check['label'],
                'status' => 'manual',
                'detail' => (string) ($check['note'] ?? 'Manual / ops verification'),
            ];
        }

        $groups = [];
        if (isset($check['all'])) {
            $groups[] = ['mode' => 'all', 'items' => $check['all']];
        }
        if (isset($check['any'])) {
            $groups[] = ['mode' => 'any', 'items' => $check['any']];
        }

        if ($groups === []) {
            return [
                'id' => (string) $check['id'],
                'label' => (string) $check['label'],
                'status' => 'fail',
                'detail' => 'Check definition has no all/any assertions',
            ];
        }

        $details = [];
        $groupResults = [];

        foreach ($groups as $group) {
            $results = [];
            foreach ($group['items'] as $assertion) {
                $result = $this->assertOne($base, $assertion);
                $results[] = $result;
                $details[] = ($result['ok'] ? '✓' : '✗').' '.$result['detail'];
            }

            $oks = array_column($results, 'ok');
            $groupResults[] = $group['mode'] === 'all'
                ? ! in_array(false, $oks, true)
                : in_array(true, $oks, true);
        }

        $pass = ! in_array(false, $groupResults, true);

        return [
            'id' => (string) $check['id'],
            'label' => (string) $check['label'],
            'status' => $pass ? 'pass' : 'fail',
            'detail' => implode('; ', $details),
        ];
    }

    /**
     * @param  array<string, mixed>  $assertion
     * @return array{ok: bool, detail: string}
     */
    private function assertOne(string $base, array $assertion): array
    {
        $type = (string) ($assertion['type'] ?? '');

        return match ($type) {
            'file' => $this->assertFile($base, (string) $assertion['path']),
            'file_contains' => $this->assertFileContains(
                $base,
                (string) $assertion['path'],
                (string) $assertion['needle'],
            ),
            'class' => $this->assertClass((string) $assertion['name']),
            'route' => $this->assertRoute((string) $assertion['name']),
            'test' => $this->assertFileContains(
                $base,
                (string) $assertion['path'],
                (string) ($assertion['contains'] ?? ''),
            ),
            default => ['ok' => false, 'detail' => "Unknown assertion type [{$type}]"],
        };
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function assertFile(string $base, string $relative): array
    {
        $path = $base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        $ok = is_file($path) || is_dir($path);

        return ['ok' => $ok, 'detail' => $relative.($ok ? '' : ' missing')];
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function assertFileContains(string $base, string $relative, string $needle): array
    {
        $path = $base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

        if (is_dir($path)) {
            $found = false;
            foreach (File::allFiles($path) as $file) {
                if ($needle === '' || str_contains($file->getContents(), $needle)) {
                    $found = true;
                    break;
                }
            }

            return ['ok' => $found, 'detail' => $relative.( $found ? " contains {$needle}" : " missing {$needle}")];
        }

        if (! is_file($path)) {
            return ['ok' => false, 'detail' => "{$relative} missing"];
        }

        if ($needle === '') {
            return ['ok' => true, 'detail' => $relative];
        }

        $ok = str_contains((string) file_get_contents($path), $needle);

        return ['ok' => $ok, 'detail' => $relative.($ok ? " contains {$needle}" : " missing {$needle}")];
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function assertClass(string $class): array
    {
        $ok = class_exists($class) || interface_exists($class) || trait_exists($class);

        return ['ok' => $ok, 'detail' => $class.($ok ? '' : ' missing')];
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function assertRoute(string $name): array
    {
        $ok = Route::has($name);

        return ['ok' => $ok, 'detail' => "route:{$name}".($ok ? '' : ' missing')];
    }

    /**
     * @param  array{ok: bool, missing_sections: list<string>, missing_references: list<string>}  $skill
     * @param  list<array<string, mixed>>  $phases
     * @return array<string, mixed>
     */
    private function summarise(array $skill, array $phases): array
    {
        $complete = collect($phases)->whereIn('status', ['complete', 'complete_pending_manual'])->pluck('id')->all();
        $current = collect($phases)->first(fn (array $p) => in_array($p['status'], ['in_progress', 'not_started'], true));

        return [
            'skill_ok' => $skill['ok'],
            'phases_complete' => $complete,
            'current_phase' => $current['id'] ?? null,
            'current_phase_name' => $current['name'] ?? null,
            'overall_percent' => (int) round(collect($phases)->avg('percent') ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderConsole(array $payload): void
    {
        $skill = $payload['skill'];
        $this->newLine();
        $this->info('OJ Intranet · skill + phase progress');
        $this->line('Generated '.$payload['generated_at']);
        $this->newLine();

        if ($skill['ok']) {
            $this->components->info('SKILL.md integrity OK');
        } else {
            $this->components->error('SKILL.md integrity issues');
            foreach ($skill['missing_sections'] as $section) {
                $this->line('  - missing section: '.$section);
            }
            foreach ($skill['missing_references'] as $ref) {
                $this->line('  - missing reference: '.$ref);
            }
        }

        $this->newLine();
        $rows = [];
        foreach ($payload['phases'] as $phase) {
            $rows[] = [
                'P'.$phase['id'],
                $phase['name'],
                $phase['gate'],
                strtoupper((string) $phase['status']),
                $phase['done'].'/'.($phase['total'] - $phase['manual']),
                $phase['percent'].'%',
            ];
        }
        $this->table(['Phase', 'Name', 'Gate', 'Status', 'Auto', '%'], $rows);

        $summary = $payload['summary'];
        $this->newLine();
        $this->line('Overall auto-check progress: <fg=cyan>'.$summary['overall_percent'].'%</>');
        if ($summary['current_phase'] !== null) {
            $this->line('Current focus: <fg=yellow>Phase '.$summary['current_phase'].' — '.$summary['current_phase_name'].'</>');
        }

        foreach ($payload['phases'] as $phase) {
            if (! in_array($phase['status'], ['in_progress', 'not_started', 'complete_pending_manual'], true) && $phase['failed'] === 0) {
                continue;
            }

            $this->newLine();
            $this->components->twoColumnDetail('Phase '.$phase['id'].' · '.$phase['name'], strtoupper((string) $phase['status']));
            foreach ($phase['checks'] as $check) {
                $icon = match ($check['status']) {
                    'pass' => '<fg=green>✓</>',
                    'manual' => '<fg=blue>○</>',
                    default => '<fg=red>✗</>',
                };
                $this->line("  {$icon} [{$check['id']}] {$check['label']}");
                if ($check['status'] !== 'pass' && $this->output->isVerbose()) {
                    $this->line('      '.$check['detail']);
                }
            }
        }

        $this->newLine();
        $this->comment('Tip: php artisan intranet:progress --write   # refresh docs/IMPLEMENTATION_PROGRESS.md');
        $this->comment('     php artisan intranet:progress -v         # show assertion detail');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeMarkdown(array $payload): string
    {
        $lines = [];
        $lines[] = '# Implementation progress';
        $lines[] = '';
        $lines[] = '_Auto-generated by `php artisan intranet:progress --write`._';
        $lines[] = '';
        $lines[] = 'Generated: `'.$payload['generated_at'].'`';
        $lines[] = '';
        $lines[] = 'Source of truth: [`.cursor/skills/oj-intranet/SKILL.md`](../.cursor/skills/oj-intranet/SKILL.md) · checks: [`progress-checks.json`](../.cursor/skills/oj-intranet/progress-checks.json)';
        $lines[] = '';
        $lines[] = '## Skill integrity';
        $lines[] = '';
        $lines[] = $payload['skill']['ok'] ? '- **Pass** — required sections and reference files present' : '- **Fail** — see missing sections/references below';
        foreach ($payload['skill']['missing_sections'] as $section) {
            $lines[] = '- Missing section: `'.$section.'`';
        }
        foreach ($payload['skill']['missing_references'] as $ref) {
            $lines[] = '- Missing reference: `'.$ref.'`';
        }
        $lines[] = '';
        $lines[] = '## Phase summary';
        $lines[] = '';
        $lines[] = '| Phase | Name | Gate | Status | Auto checks | Progress |';
        $lines[] = '|------:|------|------|--------|-------------|----------|';
        foreach ($payload['phases'] as $phase) {
            $auto = ($phase['total'] - $phase['manual']);
            $lines[] = '| '.$phase['id'].' | '.$phase['name'].' | '.$phase['gate'].' | `'.$phase['status'].'` | '.$phase['done'].'/'.$auto.' | '.$phase['percent'].'% |';
        }
        $lines[] = '';
        $lines[] = '**Overall:** '.$payload['summary']['overall_percent'].'% · **Current focus:** Phase '.($payload['summary']['current_phase'] ?? '—').' '.($payload['summary']['current_phase_name'] ?? '');
        $lines[] = '';

        foreach ($payload['phases'] as $phase) {
            $lines[] = '## Phase '.$phase['id'].' — '.$phase['name'];
            $lines[] = '';
            $lines[] = 'Gate: **'.$phase['gate'].'** · Status: `'.$phase['status'].'`';
            $lines[] = '';
            $lines[] = '| ID | Check | Status |';
            $lines[] = '|----|-------|--------|';
            foreach ($phase['checks'] as $check) {
                $mark = match ($check['status']) {
                    'pass' => '✅ pass',
                    'manual' => '🔵 manual',
                    default => '❌ fail',
                };
                $lines[] = '| '.$check['id'].' | '.$check['label'].' | '.$mark.' |';
            }
            $lines[] = '';
        }

        $lines[] = '## Legend';
        $lines[] = '';
        $lines[] = '- **pass** — code/artefact detected in the repo';
        $lines[] = '- **fail** — expected artefact missing';
        $lines[] = '- **manual** — ops/UAT/staging item; not auto-verified';
        $lines[] = '';
        $lines[] = 'Re-run: `php artisan intranet:progress --write`';
        $lines[] = '';

        $dir = base_path('docs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.'IMPLEMENTATION_PROGRESS.md';
        file_put_contents($path, implode("\n", $lines));

        return $path;
    }
}
