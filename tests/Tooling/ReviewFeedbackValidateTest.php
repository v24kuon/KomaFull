<?php

namespace Tests\Tooling;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReviewFeedbackValidateTest extends TestCase
{
    private const SCRIPT = __DIR__.'/../../scripts/review-feedback-validate.php';

    #[Test]
    public function valid_log_entry_passes_phase1(): void
    {
        $input = $this->validLogEntry();

        $result = $this->runValidator($input, skipRfp: true);

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertStringNotContainsString('ERROR', $result['output']);
    }

    #[Test]
    public function invalid_adopted_fails_phase1(): void
    {
        $input = $this->validLogEntry(adopted: 'invalid');

        $result = $this->runValidator($input, skipRfp: true);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid adopted', $result['output']);
    }

    #[Test]
    public function invalid_classification_fails_phase1(): void
    {
        $input = $this->validLogEntry(overrides: ['classification' => 'invalid']);

        $result = $this->runValidator($input, skipRfp: true);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid classification', $result['output']);
    }

    #[Test]
    public function invalid_date_format_fails_phase1(): void
    {
        $input = $this->validLogEntry(overrides: ['date' => '2026/02/23']);

        $result = $this->runValidator($input, skipRfp: true);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('Invalid date format', $result['output']);
    }

    #[Test]
    public function missing_required_key_fails_phase1(): void
    {
        $input = "## Entries\n\n- date: 2026-02-23\n  branch: test\n  scope: test\n  adopted: yes\n  classification: none\n  targets: foo.php\n";

        $result = $this->runValidator($input, skipRfp: true);

        $this->assertSame(1, $result['exitCode']);
        $this->assertStringContainsString('notes', $result['output']);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function validLogEntry(
        string $date = '2026-02-23',
        string $branch = 'feat/test',
        string $scope = 'test scope',
        string $adopted = 'no',
        string $classification = 'none',
        string $targets = 'app/Foo.php',
        string $notes = 'test',
        array $overrides = []
    ): string {
        $entry = array_merge(
            [
                'date' => $date,
                'branch' => $branch,
                'scope' => $scope,
                'adopted' => $adopted,
                'classification' => $classification,
                'targets' => $targets,
                'notes' => $notes,
            ],
            $overrides
        );

        $lines = ["## Entries\n", "\n"];
        $lines[] = "- date: {$entry['date']}\n";
        foreach (['branch', 'scope', 'adopted', 'classification', 'targets', 'notes'] as $key) {
            $lines[] = "  {$key}: {$entry[$key]}\n";
        }

        return implode('', $lines);
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function runValidator(string $stdin, bool $skipRfp = false): array
    {
        $cmd = sprintf(
            'php %s --log-path=- %s 2>&1',
            escapeshellarg(self::SCRIPT),
            $skipRfp ? '--skip-rfp' : ''
        );

        $proc = proc_open(
            $cmd,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            getcwd()
        );

        if (! is_resource($proc)) {
            $this->fail('Failed to run validator');
        }

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($proc);

        return [
            'exitCode' => $exitCode,
            'output' => $output,
        ];
    }
}
