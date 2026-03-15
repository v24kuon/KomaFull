# 実装・検証ナレッジ

部長直属の部下D（実装参謀）が参照・更新する実装規約・検証ルールの記録。

## 実装規約

- PH6-2 のセッション生成は、管理画面から `program_repetition_rules` を 1 件ずつ手動実行する前提で実装する。
- 対応する `cycle_type` は `daily` / `weekly` のみとし、`weekly` は 1 設定 = 1 曜日で扱う。
- `program_repetition_rules.start_date` / `end_date` は繰り返し設定そのものの有効期間であり、生成時に別の終了日入力は持たない。
- `program_repetition_rules.end_date` は必須とし、未終了ルールの無制限生成を許可しない。
- 既存 `lesson_sessions` と重複する候補は `skip` し、既存セッションの更新・削除は行わない。
- 新規 `lesson_sessions` の作成時は、テンプレートから `capacity` / `trial_capacity` を反映し、`reservation_management` を初期化する。

## 検証ルール

- 管理画面ルートは管理者認可で保護され、会員ユーザーからセッション生成を実行できないことを確認する。
- セッション生成の代表ケースとして、`daily` 正常系、`weekly` 正常系、重複候補の `skip`、既存セッションが不変であることを検証する。
- 新規作成セッションごとに `reservation_management` が作成され、カウンタ初期値が 0 であることを確認する。
