#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Validates .cursor/review-feedback/log.md entries.
 *
 * Phase 1: Required keys, classification enum, adopted enum, date format, targets non-empty.
 * Phase 2: RFP-001 checker for staged app/ PHP files (->refresh() / ->fresh() after write).
 * Phase 3: RFP-008 checker for staged app/config PHP files (commented-out import, Supported inline comment).
 * Phase 4: RFP-009 checker for staged app/Jobs and app/Services PHP files (required PHPDoc on key methods).
 *
 * Usage: php scripts/review-feedback-validate.php [--log-path=PATH] [--skip-rfp]
 *   --log-path: Path to log.md (default: .cursor/review-feedback/log.md from repo root)
 *   --skip-rfp: Skip Phase 2+4 RFP code checks (for CI or legacy mode)
 */
$repoRoot = get_repo_root();
$logPath = $repoRoot.'/.cursor/review-feedback/log.md';
$skipRfp = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--log-path=')) {
        $logPath = substr($arg, strlen('--log-path='));
    } elseif ($arg === '--skip-rfp') {
        $skipRfp = true;
    }
}

if ($logPath === '-' || $logPath === 'stdin') {
    $logContent = stream_get_contents(STDIN);
    $logPath = null;
} else {
    $logContent = null;
}

$exitCode = 0;

/** Phase 1: Log content validation (required keys, classification, adopted, date, targets). */
$errors = $logContent !== null
    ? validate_log_content($logContent)
    : validate_log_entries($logPath);
if ($errors !== []) {
    $exitCode = 1;
    foreach ($errors as $err) {
        echo "[review-feedback-validate] ERROR: {$err}\n";
    }
}

/** Phase 2: RFP-001 checker (staged app/ PHP files: ->refresh() / ->fresh() after write). */
if (! $skipRfp && $exitCode === 0) {
    $rfpErrors = run_rfp001_check($repoRoot);
    if ($rfpErrors !== []) {
        $exitCode = 1;
        foreach ($rfpErrors as $err) {
            echo "[review-feedback-validate] RFP-001: {$err}\n";
        }
    }
}

/** Phase 3: RFP-008 checker (staged app/config PHP files: inline comment policy). */
if (! $skipRfp && $exitCode === 0) {
    $rfpErrors = run_rfp008_check($repoRoot);
    if ($rfpErrors !== []) {
        $exitCode = 1;
        foreach ($rfpErrors as $err) {
            echo "[review-feedback-validate] RFP-008: {$err}\n";
        }
    }
}

/** Phase 4: RFP-009 checker (staged app/Jobs and app/Services PHP files: required PHPDoc). */
if (! $skipRfp && $exitCode === 0) {
    $rfpErrors = run_rfp009_check($repoRoot);
    if ($rfpErrors !== []) {
        $exitCode = 1;
        foreach ($rfpErrors as $err) {
            echo "[review-feedback-validate] RFP-009: {$err}\n";
        }
    }
}

exit($exitCode);

function get_repo_root(): string
{
    $cwd = getcwd();
    $dir = $cwd;
    while ($dir !== '.' && $dir !== '/' && $dir !== '') {
        if (is_dir($dir.'/.git')) {
            return realpath($dir) ?: $dir;
        }
        $dir = dirname($dir);
    }

    return $cwd;
}

/**
 * @return list<string>
 */
function validate_log_entries(string $logPath): array
{
    if (! is_readable($logPath)) {
        return [sprintf('Log file not readable: %s', $logPath)];
    }

    return validate_log_content(file_get_contents($logPath));
}

/**
 * @return list<string>
 */
function validate_log_content(string $content): array
{
    $errors = [];
    $entries = parse_entries($content);

    $requiredKeys = ['date', 'branch', 'scope', 'adopted', 'classification', 'targets', 'notes'];
    $validClassifications = ['汎用', '機能固有', 'PR限定', 'none'];
    $validAdopted = ['yes', 'no'];

    foreach ($entries as $idx => $entry) {
        $prefix = sprintf('Entry #%d', $idx + 1);

        foreach ($requiredKeys as $key) {
            if (! isset($entry[$key]) || trim((string) $entry[$key]) === '') {
                $errors[] = "{$prefix}: Missing or empty required key: {$key}";
            }
        }

        if (isset($entry['classification']) && ! in_array(trim($entry['classification']), $validClassifications, true)) {
            $errors[] = sprintf(
                '%s: Invalid classification "%s". Must be one of: %s',
                $prefix,
                $entry['classification'],
                implode(', ', $validClassifications)
            );
        }

        if (isset($entry['adopted']) && ! in_array(trim($entry['adopted']), $validAdopted, true)) {
            $errors[] = sprintf('%s: Invalid adopted "%s". Must be one of: yes, no', $prefix, $entry['adopted']);
        }

        if (isset($entry['date']) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($entry['date']))) {
            $errors[] = sprintf('%s: Invalid date format "%s". Expected YYYY-MM-DD', $prefix, $entry['date']);
        }

        if (isset($entry['targets']) && trim($entry['targets']) === '') {
            $errors[] = "{$prefix}: targets must not be empty";
        }
    }

    return $errors;
}

/**
 * @return list<array<string, string>>
 */
function parse_entries(string $content): array
{
    $entries = [];
    $current = null;
    $inEntries = false;

    foreach (explode("\n", $content) as $line) {
        if (str_starts_with($line, '## Entries')) {
            $inEntries = true;

            continue;
        }

        if (! $inEntries) {
            continue;
        }

        if (preg_match('/^-\s+date:\s*(.+)$/', $line, $m)) {
            if ($current !== null) {
                $entries[] = $current;
            }
            $current = ['date' => trim($m[1])];

            continue;
        }

        if ($current !== null && preg_match('/^\s{2}([a-z]+):\s*(.*)$/', $line, $m)) {
            $current[$m[1]] = trim($m[2]);
        }
    }

    if ($current !== null) {
        $entries[] = $current;
    }

    return $entries;
}

/**
 * RFP-001: Detect ->refresh() or ->fresh() after write (create/update) in app/ PHP files.
 *
 * @return list<string>
 */
function run_rfp001_check(string $repoRoot): array
{
    $errors = [];
    $stagedFiles = get_staged_php_files_in_app($repoRoot);

    foreach ($stagedFiles as $file) {
        $path = $repoRoot.'/'.$file;
        if (! is_readable($path)) {
            continue;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        foreach ($lines as $num => $line) {
            $lineNum = $num + 1;

            if (str_contains($line, '->refresh()')) {
                $errors[] = "{$file}:{$lineNum}: RFP-001 violation: ->refresh() after write is prohibited. Use the model instance directly (update() already updates in-memory attributes).";
            }

            if (preg_match('/->create\s*\([^)]*\)\s*->\s*fresh\s*\(/', $line)) {
                $errors[] = "{$file}:{$lineNum}: RFP-001 violation: ->fresh() after create() is prohibited. Use the create() return value directly.";
            }
        }
    }

    return $errors;
}

/**
 * RFP-008: Detect commented-out imports and "Supported:" inline comments in PHP files.
 *
 * @return list<string>
 */
function run_rfp008_check(string $repoRoot): array
{
    $errors = [];
    $stagedFiles = get_staged_php_files_in_app_and_config($repoRoot);

    foreach ($stagedFiles as $file) {
        $path = $repoRoot.'/'.$file;
        if (! is_readable($path)) {
            continue;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        foreach ($lines as $num => $line) {
            $lineNum = $num + 1;

            if (preg_match('/^\s*\/\/\s*use\s+[\w\\\\]+(\s+as\s+\w+)?\s*;/', $line)) {
                $errors[] = "{$file}:{$lineNum}: Commented-out use statement is prohibited. Remove the line or restore it as active code.";
            }

            if (preg_match('/\/\/\s*Supported:/', $line)) {
                $errors[] = "{$file}:{$lineNum}: Inline comment '// Supported:' is prohibited. Move this explanation into an appropriate PHPDoc block.";
            }
        }
    }

    return $errors;
}

/**
 * RFP-009: Ensure key Job/Service methods have PHPDoc explaining responsibility and side effects.
 *
 * @return list<string>
 */
function run_rfp009_check(string $repoRoot): array
{
    $errors = [];
    $stagedFiles = get_staged_php_files_in_jobs_and_services($repoRoot);

    foreach ($stagedFiles as $file) {
        $path = $repoRoot.'/'.$file;
        if (! is_readable($path)) {
            continue;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        foreach ($lines as $num => $line) {
            $lineNum = $num + 1;
            if (! preg_match('/^\s*(?:(?:final|abstract)\s+)*(public|protected|private)\s+(?:static\s+)?function\s+&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $line, $matches)) {
                continue;
            }

            $methodName = $matches[2];
            if (! is_rfp009_target_method($methodName)) {
                continue;
            }

            if (! has_adjacent_phpdoc($lines, $num)) {
                $errors[] = sprintf(
                    '%s:%d: %s() requires PHPDoc describing responsibility and side effects.',
                    $file,
                    $lineNum,
                    $methodName
                );
            }
        }
    }

    return $errors;
}

/**
 * @param  list<string>  $lines
 */
function has_adjacent_phpdoc(array $lines, int $methodLineIndex): bool
{
    $i = $methodLineIndex - 1;

    while ($i >= 0 && trim($lines[$i]) === '') {
        $i--;
    }

    $i = skip_attribute_blocks($lines, $i);

    if ($i < 0 || ! preg_match('/\*\/\s*$/', $lines[$i])) {
        return false;
    }

    while ($i >= 0) {
        if (preg_match('/^\s*\/\*\*/', $lines[$i])) {
            return true;
        }

        if (preg_match('/^\s*\*/', $lines[$i]) || preg_match('/^\s*\/\*/', $lines[$i])) {
            $i--;

            continue;
        }

        return false;
    }

    return false;
}

/**
 * Skip one or more PHP 8 attribute blocks above a method declaration.
 *
 * Supports both single-line attributes and multi-line attributes such as:
 * #[Attribute(value: 'x')]
 * #[Attribute(
 *     value: 'x'
 * )]
 *
 * @param  list<string>  $lines
 */
function skip_attribute_blocks(array $lines, int $lineIndex): int
{
    $current = $lineIndex;

    while ($current >= 0) {
        while ($current >= 0 && trim($lines[$current]) === '') {
            $current--;
        }

        if ($current < 0) {
            return $current;
        }

        $attributeStart = find_attribute_start_index($lines, $current);

        if ($attributeStart === null) {
            return $current;
        }

        $current = $attributeStart - 1;
    }

    return $current;
}

/**
 * Find the start line (`#[...`) of an attribute block ending at the given line.
 *
 * @param  list<string>  $lines
 * @return int|null line index of attribute start; null when not in attribute block
 */
function find_attribute_start_index(array $lines, int $fromIndex): ?int
{
    $lookbackLimit = 30;
    $minIndex = max(0, $fromIndex - $lookbackLimit);

    for ($i = $fromIndex; $i >= $minIndex; $i--) {
        $trimmed = trim($lines[$i]);

        if ($trimmed === '') {
            continue;
        }

        if (str_starts_with($trimmed, '#[')) {
            return $i;
        }

        if (preg_match('/\*\/\s*$/', $trimmed) === 1 || preg_match('/^\s*\/\*\*/', $trimmed) === 1) {
            return null;
        }

        if (preg_match('/^\s*(?:(?:final|abstract)\s+)*(public|protected|private)\s+(?:static\s+)?function\b/', $trimmed) === 1) {
            return null;
        }

        if (str_ends_with($trimmed, ';')) {
            return null;
        }
    }

    return null;
}

/**
 * Decide whether the method is a target of RFP-009 PHPDoc enforcement.
 *
 * Target methods:
 * - Entry points (`handle`, `failed`)
 * - State transition helpers (e.g. `markXxx`, `updateXxxStatus`, `setXxxState`)
 * - Idempotency / key generation helpers (e.g. `buildXxxKey`)
 */
function is_rfp009_target_method(string $methodName): bool
{
    $normalized = strtolower($methodName);

    if (in_array($methodName, ['handle', 'failed'], true)) {
        return true;
    }

    if (preg_match('/^mark[A-Z]/', $methodName) === 1) {
        return true;
    }

    if (preg_match('/^(update|set)[A-Z].*(Status|State)$/', $methodName) === 1) {
        return true;
    }

    if (preg_match('/^(build|generate|create|make)[A-Z].*Key$/', $methodName) === 1) {
        return true;
    }

    if (str_contains($normalized, 'idempotency')) {
        return true;
    }

    return false;
}

/**
 * @return list<string>
 */
function get_staged_php_files_in_app(string $repoRoot): array
{
    $output = [];
    $cmd = sprintf(
        'git -C %s diff --cached --name-only --diff-filter=ACMRTUXB -- app/',
        escapeshellarg($repoRoot)
    );
    exec($cmd, $output, $code);

    if ($code !== 0) {
        return [];
    }

    $files = [];
    foreach ($output as $line) {
        $line = trim($line);
        if ($line !== '' && str_ends_with($line, '.php')) {
            $files[] = $line;
        }
    }

    return $files;
}

/**
 * @return list<string>
 */
function get_staged_php_files_in_jobs_and_services(string $repoRoot): array
{
    $output = [];
    $cmd = sprintf(
        'git -C %s diff --cached --name-only --diff-filter=ACMRTUXB -- app/Jobs/ app/Services/',
        escapeshellarg($repoRoot)
    );
    exec($cmd, $output, $code);

    if ($code !== 0) {
        return [];
    }

    $files = [];
    foreach ($output as $line) {
        $line = trim($line);
        if ($line !== '' && str_ends_with($line, '.php')) {
            $files[] = $line;
        }
    }

    return $files;
}

/**
 * @return list<string>
 */
function get_staged_php_files_in_app_and_config(string $repoRoot): array
{
    $output = [];
    $cmd = sprintf(
        'git -C %s diff --cached --name-only --diff-filter=ACMRTUXB -- app/ config/',
        escapeshellarg($repoRoot)
    );
    exec($cmd, $output, $code);

    if ($code !== 0) {
        return [];
    }

    $files = [];
    foreach ($output as $line) {
        $line = trim($line);
        if ($line !== '' && str_ends_with($line, '.php')) {
            $files[] = $line;
        }
    }

    return $files;
}
