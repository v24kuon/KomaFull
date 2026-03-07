<?php

namespace Tests\Tooling;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ReviewFeedbackValidateTest extends TestCase
{
    private const SCRIPT = __DIR__.'/../../scripts/review-feedback-validate.php';

    /** @var list<string> */
    private array $temporaryRepositories = [];

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

    #[Test]
    public function auto_promoted_role_guard_detects_hardcoded_admin_in_staged_feature_test(): void
    {
        $repository = $this->createTemporaryGitRepository();
        $this->stageFile($repository, 'tests/Feature/Admin/DummyCrudTest.php', <<<'PHP'
<?php

return [
    'role' => 'admin',
];
PHP
        );

        $result = $this->runValidator($this->autoPromotedRoleLogEntries(), workingDirectory: $repository);

        $this->assertSame(1, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('AUTO-RULE', $result['output']);
        $this->assertStringContainsString('Use User::ROLE_ADMIN', $result['output']);
    }

    #[Test]
    public function auto_promoted_controller_phpdoc_guard_detects_staged_admin_controller_without_phpdoc(): void
    {
        $repository = $this->createTemporaryGitRepository();
        $this->stageFile($repository, 'app/Http/Controllers/Admin/DummyController.php', <<<'PHP'
<?php

class DummyController
{
    public function index(): void
    {
    }
}
PHP
        );

        $result = $this->runValidator($this->autoPromotedPhpDocLogEntries(), workingDirectory: $repository);

        $this->assertSame(1, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('AUTO-RULE', $result['output']);
        $this->assertStringContainsString('index() requires PHPDoc', $result['output']);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryRepositories as $repository) {
            $this->deleteDirectory($repository);
        }

        parent::tearDown();
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

        return $this->buildLogContent([$entry]);
    }

    private function autoPromotedRoleLogEntries(): string
    {
        return $this->buildLogContent([
            [
                'scope' => 'PR指摘対応（Category CRUDテストのロール指定を定数化）',
                'notes' => 'User::ROLE_ADMIN を使用',
            ],
            [
                'scope' => 'PR指摘対応（Location CRUDテストの管理者ロール指定を定数化）',
                'notes' => 'User::ROLE_ADMIN へ置換',
            ],
            [
                'scope' => 'PR指摘対応（ProgramType CRUDテストのロール指定を定数化）',
                'notes' => 'User::ROLE_MEMBER へ置換',
            ],
        ]);
    }

    private function autoPromotedPhpDocLogEntries(): string
    {
        return $this->buildLogContent([
            [
                'scope' => 'PR指摘対応（AdditionalItemController主要アクションへPHPDoc追加）',
                'notes' => '主要アクションにPHPDoc追加',
            ],
            [
                'scope' => 'PR指摘対応（ProgramControllerのフォーム取得共通化と主要アクションPHPDoc追加）',
                'notes' => 'エントリポイントへPHPDoc追加',
            ],
        ]);
    }

    /**
     * @param  list<array<string, string>>  $entries
     */
    private function buildLogContent(array $entries): string
    {
        $lines = ["## Entries\n", "\n"];

        foreach ($entries as $entry) {
            $merged = array_merge(
                [
                    'date' => '2026-02-23',
                    'branch' => 'feat/test',
                    'scope' => 'test scope',
                    'adopted' => 'yes',
                    'classification' => '汎用',
                    'targets' => 'app/Foo.php',
                    'notes' => 'test',
                ],
                $entry
            );

            $lines[] = "- date: {$merged['date']}\n";
            foreach (['branch', 'scope', 'adopted', 'classification', 'targets', 'notes'] as $key) {
                $lines[] = "  {$key}: {$merged[$key]}\n";
            }
            $lines[] = "\n";
        }

        return implode('', $lines);
    }

    private function createTemporaryGitRepository(): string
    {
        $repository = sys_get_temp_dir().'/review-feedback-validate-'.bin2hex(random_bytes(8));
        mkdir($repository, 0777, true);
        $this->temporaryRepositories[] = $repository;

        $this->runCommand('git init -q', $repository);

        return $repository;
    }

    private function stageFile(string $repository, string $relativePath, string $content): void
    {
        $path = $repository.'/'.$relativePath;
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $content);

        $this->runCommand(sprintf('git add %s', escapeshellarg($relativePath)), $repository);
    }

    private function runCommand(string $command, string $workingDirectory): string
    {
        $proc = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $workingDirectory
        );

        if (! is_resource($proc)) {
            $this->fail(sprintf('Failed to run command: %s', $command));
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($proc);
        $this->assertSame(0, $exitCode, sprintf("Command failed: %s\n%s", $command, $output));

        return $output;
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());

                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function runValidator(string $stdin, bool $skipRfp = false, ?string $workingDirectory = null): array
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
            $workingDirectory ?? getcwd()
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
