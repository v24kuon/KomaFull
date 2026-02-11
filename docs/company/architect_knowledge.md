# 技術アーキテクト知見 (Architect Knowledge)

このファイルは **技術参謀A (bucho-subordinate-architect)** が管理し、技術的に重要だと思った設計判断や規約を記録します。

## 重要な設計判断
- 予約確定・定員更新には `reservation_management` の行ロック（`lockForUpdate`）を必須とする。
- ロック順序の固定: `trial_applications` -> `reservation_management` -> `course_entitlements`。
- 「ビルドなし」環境下での `v_asset()` ヘルパーによるキャッシュバスティング。

## 技術的懸念・メモ
- 非同期ジョブの失敗隔離（DLQ）と再実行の仕組みが必要。
