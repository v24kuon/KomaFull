---
description: Laravel 12 / PHPUnit 前提でTDD（テスト観点表→RED→GREEN→REFACTOR）を強制します。脱Node/Vite。
argument-hint: [what to build]
---

# /tdd（Laravel / PHPUnit）

このコマンドは **tdd-guide**（Laravel向け）を前提に、テストファーストで実装を進めます。

## 前提
- 本プロジェクトは **脱Node/Vite**。`npm`/`vite`/Jest/Playwright 前提のフローは使わない。
- テストは **PHPUnit**（`php artisan test --compact`）を前提。
- プロジェクトルールにより、テスト作業開始時は **テスト観点表（Markdown）** と **Given/When/Then コメント**が必須。

## このコマンドがやること
1. **テスト観点表**を作る（正常系/異常系/境界値）
2. 先に **失敗するテスト（RED）** を書く（Feature/Unit）
3. `php artisan test --compact ...` で **失敗を確認**
4. 最小実装（GREEN）
5. 再実行して **成功を確認**
6. リファクタ（REFACTOR）＋再実行
7. （任意）`php artisan test --coverage` でカバレッジ確認

## 使いどころ
- 新機能追加（予約/決済/残高/サブスク付与など）
- バグ修正（再現テスト→修正）
- リファクタ（挙動維持をテストで担保）

## コマンド例（置き換え）
```bash
# テスト作成（Feature）
php artisan make:test --phpunit BookingFlowTest --no-interaction

# テスト作成（Unit）
php artisan make:test --phpunit BookingServiceTest --unit --no-interaction

# 最小スコープで実行
php artisan test --compact tests/Feature/BookingFlowTest.php
php artisan test --compact --filter=testName

# 変更にPHPが含まれる場合の整形
vendor/bin/pint --dirty
```

## チェック観点（Laravel）
- **認可**: Policy/Gate/authorize の有無（IDOR防止）
- **バリデーション**: Form Request で集約されているか
- **DB整合性**: 予約定員/残高/サブスク枠などの不変条件がトランザクション/制約で守られているか
- **失敗系**: 403/404/409、満席、締切、決済失敗、二重送信
- **境界値**: 0/最小/最大/±1/空/NULL（意味がある範囲で）

## 関連
- レビュー: `/code-review`
- カバレッジ: `/test-coverage`
