# 技術アーキテクト知見 (Architect Knowledge)

このファイルは **技術参謀A (bucho-subordinate-architect)** が管理し、技術的に重要だと思った設計判断や規約を記録します。

## 重要な設計判断
- 予約確定・定員更新には `reservation_management` の行ロック（`lockForUpdate`）を必須とする。
- ロック順序の固定: `trial_applications` -> `reservation_management` -> `course_entitlements`。
- 「ビルドなし」環境下での `v_asset()` ヘルパーによるキャッシュバスティング。
- PH6-2 のセッション生成は `program_repetition_rules` をテンプレートとして `lesson_sessions` を追加生成する非破壊処理とする。
- PH6-2 では `start_date` / `end_date` をルールの有効期間として扱い、生成実行時に別の終了日は受け取らない。
- PH6-2 の重複候補は `skip` とし、既存 `lesson_sessions` の更新や再計算は行わない。
- PH6-2 で新規生成した `lesson_sessions` には、テンプレートの `capacity` / `trial_capacity` をコピーし、対応する `reservation_management` を必ず初期化する。

## 技術的懸念・メモ
- 非同期ジョブの失敗隔離（DLQ）と再実行の仕組みが必要。
- セッション生成は有限期間のみを対象にし、`end_date` 必須で runaway generation を防ぐ。
