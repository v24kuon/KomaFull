# Test Coverage（Laravel / PHPUnit）

PHPUnitカバレッジを確認し、足りないテスト（特に分岐/失敗系/境界値）を補う。

## 前提
- 本プロジェクトは **脱Node/Vite**。`npm test --coverage` 等は使わない。
- `php artisan test --coverage` は **Xdebug/PCOV** 等のカバレッジドライバが有効な環境で動作する。
- プロジェクトルールにより、テスト追加時は **テスト観点表（Markdown）** と **Given/When/Then コメント**が必須。

## 手順
1. カバレッジ付きでテスト実行（環境が対応している場合）
   - `php artisan test --coverage`
   - （任意）最小スコープ: `php artisan test --coverage --filter=testName`

2. 出力（テキスト）または生成されたレポートを確認し、**80%未満**の対象を列挙

3. 80%未満の対象ごとに、未カバー箇所を分析
   - **認可**（401/403）
   - **バリデーション**（必須/形式/境界値/NULL/空）
   - **例外・エラーパス**（404/409、満席、締切、決済失敗など）
   - **DB整合性**（トランザクション/ユニーク制約/巻き戻し）
   - **N+1**（eager loadの有無）

4. テストを追加（Laravel）
   - Unit: `tests/Unit/*`（サービス/ドメインロジック）
   - Feature: `tests/Feature/*`（HTTP/DB統合、Form Request、Policy）
   - 外部依存はFake: `Http::fake()`, `Queue::fake()`, `Mail::fake()`, `Notification::fake()`, `Storage::fake()`

5. 追加テストを最小スコープで実行 → 緑化
   - `php artisan test --compact tests/Feature/SomeTest.php`

6. 可能なら再度 `php artisan test --coverage` を実行し、**before/after** を提示

## 置き換えコマンド例
```bash
php artisan test --coverage
php artisan test --compact
php artisan test --compact tests/Feature/SomeFeatureTest.php
```

## 目標
- 目標: **全体80%+**（達成困難なら、ビジネス影響の大きい分岐と主要エラーパスを優先して網羅）
