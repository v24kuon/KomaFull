# Refactor Clean（Laravel / 脱Node）

安全にデッドコード（未使用のルート/コントローラ/ビュー/クラス等）を特定し、テストで担保しながら削除する。

## 前提
- 本プロジェクトは **脱Node/Vite**。knip/depcheck/ts-prune 等のNodeツールは使わない。
- 削除はリスクが高いので、**小さく・段階的に**行う。
- 削除作業は原則テストとセット（`php artisan test --compact`）。

## 手順（推奨）
1. 現状把握
   - `git status` / `git diff`（対象の変更範囲を明確化）
   - `php artisan route:list`（ルート表面の確認）

2. 候補の抽出（例）
   - **未使用ビュー**: `resources/views/*` のうち参照されていないもの
     - 参照例: `view('...')`, `@include(...)`, `@extends(...)`, `<x-...>`（Blade component）
   - **未使用コントローラ/アクション**: ルートに紐づかないクラス/メソッド
   - **未使用Form Request**: Controllerで型指定されていない request
   - **未使用サービス/ヘルパ**: 参照されないクラス/関数

3. 参照確認（削除前に必須）
   - 文字列参照も疑う（view名/route名/container make等）
   - `rg -n "ClassName|methodName|view\\('|route\\('name'\\)|dispatch\\(" .`

4. 削除バッチを小さく実行
   - まずは **SAFE**（未使用ビュー、未使用privateメソッド等）から
   - バッチごとにテスト
     - 事前: `php artisan test --compact`
     - 変更後: `php artisan test --compact`

5. 破綻時の復旧
   - `git revert` / `git checkout -- <file>` 等で即時ロールバック
   - 参照の見落とし（文字列参照/動的解決）を追加で探索

## チェック観点
- ルート名・view名が文字列で参照されていないか（grepだけでなくBladeも確認）
- 認可（Policy/Gate）やmiddlewareの参照が消えていないか
- migrations/DB制約に影響がないか（基本は削除しない）
- 削除後にテストが通るか（最低でも該当領域＋可能なら全体）

## 注意（Composer依存）
- 未使用Composer依存の削除は、影響範囲が大きいので **ユーザーが明示的に依頼した場合のみ**行う。
