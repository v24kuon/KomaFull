# Review Feedback Log

目的: レビュー指摘の採用有無と分類結果をコミット単位で必ず記録し、蓄積漏れを防ぐ。

## 記録フォーマット

```text
- date: YYYY-MM-DD
  branch: <branch>
  scope: <対象差分>
  adopted: yes|no
  classification: 汎用|機能固有|PR限定|none
  targets: <更新先ファイル>
  notes: <判定理由・補足>
```

## Entries

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（weekly の day_of_week 非数値文字列をモデル側で拒否）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。weekly 分岐は raw の `''` だけを必須チェックした後、cast 後の `$this->day_of_week` を 0-6 判定に使っていたため、`abc` や空白文字列はモデル層で 0 として扱われてしまい、非数値入力を早期拒否できていなかった。raw 値に対して空白・整数形式を先に検証し、非数値文字列を InvalidArgumentException で拒否するよう補強した。あわせて `abc`・空白文字列の失敗系と、数値文字列 `0` の正常系を feature test に追加した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（旧 MySQL での CHECK 制約 migration を fail fast 化）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php, tests/Unit/ProgramRepetitionRuleFoundationMigrationTest.php
  notes: 指摘は一部有効。現行 migration は non-sqlite で `ALTER TABLE ... ADD CONSTRAINT ... CHECK` を無条件に実行しており、modern MySQL 相当の CHECK 制約サポートを暗黙前提にしていた。一方で MySQL 8.0.16 未満が必ず構文エラーで migration failure になるという指摘文は強すぎ、公式資料では旧版 MySQL の CHECK 制約定義は parse されても無視されるとされている。DB変更を半端に進めないよう、`up()` 冒頭で MySQL バージョンを確認し、8.0.16 未満では明示例外で fail fast するよう補強し、unit test を追加した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（weekly の day_of_week 空文字をモデル側で先に拒否）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は一部有効。`day_of_week=''` は accessor 参照時に integer cast で 0 として読めるため、weekly のモデル検証は空文字を必須チェックで弾けていなかった。一方で今回の SQLite 実行では保存時の raw 値は空文字のままで、最終的には日曜として保存成功するのではなく DB の CHECK 制約で QueryException になっていた。weekly 分岐で raw attribute の空文字を先に検知して InvalidArgumentException を投げるよう補強し、回帰テストを追加した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（採用済み review-feedback entry の分類を PR限定へ補正）
  adopted: yes
  classification: PR限定
  targets: .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`classification: none` 自体は許容値だが、`adopted: yes` の entry に使うと蓄積・再利用対象から外れて意味が弱くなる。`addScheduleCheckConstraint` の採用済み entry はこのPR固有の是正内容だったため、分類を PR限定 へ補正した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: cycle_type の許容値外入力をモデル側でも拒否（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。従来の ensureSupportedScheduleConfiguration() は daily の day_of_week 条件だけを確認した後、weekly 以外を早期 return していたため、monthly・空文字・null の cycle_type はモデル層を素通りして DB 制約へ依存していた。cycle_type の strict な許容値チェックを先頭に追加し、通常保存では InvalidArgumentException、withoutEvents() では引き続き QueryException になることを feature test で確認した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: ProgramRepetitionRuleFoundationTest の作成ヘルパーを Factory ベースへ統一（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。query()->create() 直書きでは ProgramRepetitionRuleFactory の生成経路から外れており、将来の defaults や state 拡張との整合が弱かった。テスト本文は変えず、createRule() / createRuleWithoutEvents() の内部だけを factory()->createOne() ベースに置き換えて Factory 経由へ統一した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: DROP 制約処理の対応ドライバー範囲を PHPDoc で明文化（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php
  notes: 指摘は有効。DROP 構文の分岐自体は正しかったが、`sqlite` はテーブル再構築、`mysql` は `DROP CHECK`、`pgsql` / `sqlsrv` は `DROP CONSTRAINT` を使う前提がメソッド宣言だけでは伝わりにくかった。挙動は変更せず、対応ドライバー範囲と `sqlite` の扱いを PHPDoc で明文化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: booted() のライフサイクル責務を PHPDoc で明文化（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php
  notes: 指摘は有効。booted() は saving フックを登録して永続化前にスケジュール制約を検証するエントリーポイントだが、メソッド宣言だけでは責務が伝わりにくかった。挙動は変更せず、ライフサイクルフックの目的を PHPDoc で簡潔に明文化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: end_date 必須をモデル側でも検証して DB 依存を解消（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。end_date 必須は migration と業務要件で担保されていたが、モデルの saving 検証では未確認だったため、通常保存時は QueryException まで到達していた。ensureSupportedScheduleConfiguration() に end_date の null ガードを追加し、通常保存は InvalidArgumentException、withoutEvents() は引き続き QueryException になることを feature test で確認した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: ensureSupportedScheduleConfiguration の責務を PHPDoc で明文化（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php
  notes: 指摘は有効。検証ロジック自体は妥当だったが、private helper の責務と PH6-2-1 制約を PHPDoc で明示すると保守性が上がる。挙動は変更せず、week_of_month 非対応・daily の day_of_week 禁止・weekly の day_of_week 必須かつ 0-6 制約をメソッド直上に文書化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: ProgramRepetitionRuleModelTest の到達不能ガード削除（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: tests/Unit/ProgramRepetitionRuleModelTest.php
  notes: 指摘は有効。assertTrue(method_exists(...)) の直後に同一条件の否定分岐を置いており、失敗時はそこでテストが終了するため return 分岐は到達不能だった。Program / Location / Staff の3テストから冗長なガードを削除し、意図を保ったままテストを簡潔化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: addScheduleCheckConstraint 内の重複コード統合（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php
  notes: 指摘は有効。MySQL / pgsql / sqlsrv の ADD CONSTRAINT CHECK 構文が同一のため、条件分岐を統合し重複を削除した。addScheduleCheckConstraint は up() で sqlite 以外のときのみ呼ばれるため、ドライバーチェック自体も不要と判断し単一の DB::statement に簡略化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PH6-2-1 ProgramRepetitionRule 基盤整備（daily/weekly限定、weekly=1曜日、end_date必須）
  adopted: no
  classification: none
  targets: app/Models/ProgramRepetitionRule.php, app/Models/Program.php, app/Models/Location.php, app/Models/Staff.php, database/factories/ProgramRepetitionRuleFactory.php, database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php, tests/Unit/ProgramRepetitionRuleModelTest.php
  notes: ProgramRepetitionRule モデル・Factory・migration・テスト追加。cycle_type daily/weekly 限定、end_date 必須、weekly 時 day_of_week 必須、week_of_month 禁止を DB 制約とモデルガードで担保。親モデルに programRepetitionRules() リレーション追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings ログの unique 衝突表現を緩和）
  adopted: yes
  classification: PR限定
  targets: .cursor/review-feedback/log.md
  notes: 指摘は有効で、既存 entry の notes は unique 制約衝突を feature test で直接確認したように読め、実際の検証内容である「既存 singleton 行の再利用と件数維持」より強い表現だった。該当 notes を singleton 再利用経路の確認へ言い換え、ログの説明強度を実測事実に揃えた。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-fix の箇条書きインデント不整合を修正）
  adopted: yes
  classification: PR限定
  targets: .cursor/commands/review-fix.md
  notes: 指摘は一部有効で、問題の本質は markdownlint 方針そのものよりも同階層の箇条書きインデントが1行だけ他行と不一致だった点にあった。該当行を周囲の箇条書きと同じインデントへ揃え、文書全体の一貫性を回復した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（RFP-011 の分類根拠から機能固有名を除去）
  adopted: yes
  classification: PR限定
  targets: .cursor/rules/review-feedback-prevention.mdc
  notes: 指摘は有効で、RFP-011 の分類根拠に Category / ProgramType / Program / Location / Staff といった具体的な画面・モデル名が含まれており、本ファイル先頭の「汎用ルールのみ」という方針と緊張関係があった。分類根拠を「外部キー制約で削除失敗し得る CRUD 全般」に一般化し、スコープ定義と整合する表現へ調整した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-fix コマンドの章番号依存参照を解消）
  adopted: yes
  classification: PR限定
  targets: .cursor/commands/review-fix.md
  notes: 指摘は有効で、`.cursor/rules/review-feedback-prevention.mdc` の「6) 強制運用ゲート」のような章番号付き参照は、将来の見出し再編で古くなる余地があった。意味は変えずに「強制運用ゲート」セクション参照へ置き換え、構成変更に強い文面へ調整した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストに store 側 unique 回帰を追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: 指摘は有効で、`StoreAdditionalItemRequest` の `unique:additional_items,code` を直接通す失敗系が未カバーだった。兄弟 CRUD に合わせて store 側の重複 code 投稿を拒否する回帰テストを追加し、store / update で FormRequest を分離する CRUD は経路ごとの unique 失敗系を当該経路で直接持つよう RFP-016 を拡張した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストに update 側 validation 回帰を追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: 指摘は一部有効で、`UpdateAdditionalItemRequest` の `input_type` 失敗系は未カバーだったため update 側の invalid payload 回帰テストを追加した。一方で `code` の unique は既存の `test_update_rejects_duplicate_code` と同一 code 維持の update 成功ケースですでに検知可能だったため追加修正は見送った。store / update で FormRequest を分離する CRUD は代表的な失敗系を両経路で持つ汎用ルールを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-feedback validator の auto-promoted guard 誤昇格を防止）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php, tests/Tooling/ReviewFeedbackValidateTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: auto-promoted guard のテーマ判定が `User::ROLE_*` や `PHPDoc追加` の単独マッチで広すぎ、`adopted: no` / `classification: none` のログまで閾値集計していたため、対象パスと文脈語の両方でテーマを絞り込み、採用済みログだけを集計対象へ修正した。tooling test には別テーマの類似トークンでは昇格しないケースと、未採用・none ログが閾値へ加算されないケースを追加した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Update*Request の unique 除外を Rule::unique()->ignore() へ統一）
  adopted: yes
  classification: 汎用
  targets: app/Http/Requests/Admin/UpdateCategoryRequest.php, app/Http/Requests/Admin/UpdateProgramTypeRequest.php, app/Http/Requests/Admin/UpdateProgramRequest.php, app/Http/Requests/Admin/UpdateLocationRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateStaffRequest.php, tests/Feature/Admin/AdminCategoryCrudTest.php, tests/Feature/Admin/AdminProgramTypeCrudTest.php, tests/Feature/Admin/AdminProgramCrudTest.php, tests/Feature/Admin/AdminLocationCrudTest.php, tests/Feature/Admin/AdminAdditionalItemCrudTest.php, tests/Feature/Admin/AdminStaffCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: `Staff` だけを局所修正すると同系 Update FormRequest 間で記法が分岐するため、admin 配下の更新系 `unique` ルールを一括で `Rule::unique(...)->ignore($this->route(...))` へ統一した。Laravel docs の `ignore($model)` 推奨と route model binding 前提に合わせ、文字列連結より主キー名変更や条件拡張に追従しやすい形へ寄せた。あわせて 6 CRUD スイートへ「自分自身の code は更新可 / 他レコードの code は更新不可」の回帰テストを追加し、同一値除外と重複拒否の両方を明示した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストの status 定数化と alert 検証を保守しやすく調整）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php
  notes: status 直書き `'active'` / `'inactive'` は AdditionalItem::STATUS_ACTIVE / STATUS_INACTIVE へ寄せてモデル定義との追従性を改善。additional_item_type の alert 回帰テストは full HTML 固定ではなく、エラーメッセージ表示と `role=\"alert\"` の契約に絞った分割アサートへ変更し、view 整形差分だけで壊れにくくした。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest の一時Git/外部プロセス helper に責務PHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: createTemporaryGitRepository / stageFile / runCommand / deleteDirectory / runValidator は一時Gitリポジトリ作成、相対パス前提、外部プロセス実行、再帰削除など副作用が強く、既存の PHPDoc 方針に沿って責務・前提・更新方針を追記した。既存ルールでカバー済みのため再発防止ルールファイルの追加更新は不要。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-fix コマンドの log 更新条件を強制運用ゲートへ明示化）
  adopted: yes
  classification: PR限定
  targets: .cursor/commands/review-fix.md
  notes: `必要条件を満たす場合` だけでは `.cursor/review-feedback/log.md` 更新トリガーが不明確だったため、`.cursor/rules/review-feedback-prevention.mdc` の「6) 強制運用ゲート」を参照しつつ、`app/` `tests/` `database/` `routes/` `config/` `bootstrap/` `resources/` を含む場合と `classification: none` 記録義務を明示した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest の ROLE_MEMBER 正常系を単独ケース化し、review-fix 判定根拠を明文化）
  adopted: yes
  classification: PR限定
  targets: tests/Tooling/ReviewFeedbackValidateTest.php, .cursor/commands/review-fix.md
  notes: 指摘の問題意識は一部妥当で、mixed fixture 自体よりも guard が実際に見る `'role' => ...` 形を happy path が再現していない点が弱かったため、`User::ROLE_ADMIN` / `User::ROLE_MEMBER` をそれぞれ単独ケース化。あわせて `/review-fix` コマンドへ「有効 / 一部有効 / 無効」の根拠付き判定を必須化し、`妥当そう` のような推測表現を禁止した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest に auto-promoted guard の正常系を追加）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: auto-promoted role guard と controller PHPDoc guard は違反検知だけでなく false positive 防止の正常系も必要なため、`User::ROLE_ADMIN` / `User::ROLE_MEMBER` を使う staged feature test と、隣接 PHPDoc を持つ staged admin controller が通過する happy path を追加。既存の失敗系と合わせて tooling validator の回帰網羅を補強。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff の gender 許容値をフォーム選択肢へ整合）
  adopted: yes
  classification: 汎用
  targets: app/Http/Requests/Admin/StoreStaffRequest.php, app/Http/Requests/Admin/UpdateStaffRequest.php, tests/Feature/Admin/AdminStaffCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: Staff フォームの gender は male / female / other の3択だが、store / update の FormRequest が広い string 許容になっていたため、両方を `in:male,female,other` へ統一。細工した未定義値の store / update を feature test で拒否し、固定選択 UI とサーバー側許容値を揃える汎用ルールを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem の hidden 項目エラー表示へ role="alert" を追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/additional-items/_form.blade.php, tests/Feature/Admin/AdminAdditionalItemCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: fixed hidden 値で表示している additional_item_type のエラー表示だけ `role="alert"` が漏れていたため追加。対象フィールド単位で alert role を確認する feature test を追加し、custom error markup でも即時通知アクセシビリティを揃える汎用ルールを追記。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Locationフォームのstatus値をモデル定数参照へ統一）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/locations/_form.blade.php
  notes: Location フォームの status 選択肢で `'active'` / `'inactive'` を直書きしていたため、Location::STATUS_ACTIVE / STATUS_INACTIVE に置換。UI と FormRequest / モデル定義の single source of truth を揃え、将来の定数変更時の不整合を防止。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest のログ entry PHPDoc を配列形状型へ厳密化）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: validLogEntry() の overrides と buildLogContent() の entries を固定キーの配列形状型へ更新し、defaults マージ後の entry / merged も required key shape として注釈。RFP-002 に沿って typo やキー欠落を静的に拾いやすくした。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/ProgramTypeController.php, resources/views/admin/program-types/_delete_error_row.blade.php, tests/Feature/Admin/AdminProgramTypeCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: ProgramType削除でFK制約違反（programs.program_type_id）を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連Programあり時の削除失敗をFeatureテストで追加検証し、マスタ系CRUDの削除失敗パターンを汎用ルールへ昇格。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（HTMX部分描画テストのDOCTYPE検証 false positive を修正）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminCategoryCrudTest.php, tests/Feature/Admin/AdminProgramTypeCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: `assertDontSee()` の既定値では検索文字列がHTMLエスケープされるため、`<!DOCTYPE html>` の不在確認が false positive になっていた。Category / ProgramType の HTMX 部分描画テストを `assertDontSee('<!DOCTYPE html>', false)` へ修正し、再発防止ルールを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program CRUD のアクセス制御テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramCrudTest.php
  notes: admin.programs.index に対する guest / non-admin のアクセス制御を Program CRUD テストへ追加し、Category / ProgramType と同水準の認可回帰を確保。HTMXヘッダー指定の指摘は、このファイル自体は withHeader() を使用済みのため見送り。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（LocationController の削除制約違反 helper に責務PHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/LocationController.php
  notes: respondDeleteConstraintViolation() に責務・前提・更新方針を示す PHPDoc を追加し、HTMX と通常リクエストで応答を分岐する意図を読み取りやすくした。挙動変更はなし。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StaffController の削除制約違反 helper に責務PHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StaffController.php
  notes: respondDeleteConstraintViolation() に責務・前提・更新方針を示す PHPDoc を追加し、HTMX と通常リクエストで応答を分岐する意図を読み取りやすくした。挙動変更はなし。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest の helper デフォルト意図を PHPDoc で明示）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: validLogEntry は phase-1 検証向けに adopted=no / classification=none を既定値とし、buildLogContent は auto-promoted guard 用に adopted=yes / classification=汎用 を既定値とする意図を PHPDoc で明示。両 helper の責務差をコメントで読み取りやすくした。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-feedback validator の閾値定数化と staged PHP helper の PHPDoc 補強）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php
  notes: auto-promoted guard の threshold を意図が分かる定数へ置換し、再発回数の意味をコメントで明示。get_staged_php_files() の PHPDoc に $repoRoot / $paths / $suffix の役割を追記し、suffix フィルタの意図を文書化。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program削除失敗パーシャルへ role="alert" を追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/programs/_delete_error_row.blade.php, tests/Feature/Admin/AdminProgramCrudTest.php
  notes: HTMX で動的挿入される Program 削除失敗メッセージに role="alert" を追加し、スクリーンリーダーへの即時通知を改善。削除失敗時のエラー行に role 属性が含まれることを feature test で確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff の重複code投稿時の回帰テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStaffCrudTest.php
  notes: staffs.code の unique 制約衝突時に code フィールドへ専用メッセージが返り、重複レコードが作成されないことを feature test で明示。既存の StoreStaffRequest の unique ルールに対する回帰防止を追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Location の重複code投稿時の回帰テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminLocationCrudTest.php
  notes: locations.code の unique 制約衝突時に code エラーとなることを feature test で明示。既存の StoreLocationRequest の unique ルールに対する回帰防止を追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff削除制約エラーメッセージを包括表現へ調整）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StaffController.php, tests/Feature/Admin/AdminStaffCrudTest.php
  notes: Staff の FK 制約違反メッセージは実データ参照先を狭めず、関連データ全体を含意する包括表現へ変更。通常削除と HTMX 削除の両方で新文言が返ることを feature test で確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettingsFactory definition のPHPDocを配列形状型へ更新）
  adopted: yes
  classification: 汎用
  targets: database/factories/StoreSettingsFactory.php
  notes: StoreSettingsFactory の definition() は固定キーの設定配列を返すため、array<string, mixed> から array{singleton_key, ...} の配列形状型へ更新。項目追加・削除時の差分検知と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings の resolveSettings に責務PHPDocを追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StoreSettingsController.php
  notes: StoreSettings の主要 private helper である resolveSettings() に、責務・前提・更新方針を示す PHPDoc を追加。singleton 行の確保メソッドである意図を明示し、レビュー時の解釈ずれを防止。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings の unique衝突経路を回帰テストで明示）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: createOrFirst 前提の既存 singleton 行再利用を明示するため、update 実行後も件数が1件のままで既存IDが再利用されることを検証する回帰テストを追加。singleton 再利用経路を feature test で確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（FK制約違反の判定ロジックをControllerへ共通化）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Controller.php, app/Http/Controllers/Admin/LocationController.php, app/Http/Controllers/Admin/StaffController.php, app/Http/Controllers/Admin/ProgramController.php
  notes: Location/Staff/Program で重複していた isForeignKeyConstraintViolation() をベース Controller の protected helper へ集約し、削除失敗時の応答ロジックは各コントローラに維持。既存 feature test で通常削除・通常失敗・HTMX失敗の回帰を確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-feedback validator の staged PHP 取得処理を共通化）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php, tests/Tooling/ReviewFeedbackValidateTest.php
  notes: auto-promoted guard 分岐の最後のケースにも明示 continue を追加し、5つの get_staged_php_files_in_* 関数で重複していた git diff + PHP サフィックス判定を共通ヘルパーへ集約。temp git repo を使う tooling テストを追加し、admin feature test / admin controller の auto-promoted guard が共通化後も動作することを確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/ProgramController.php, resources/views/admin/programs/_delete_error_row.blade.php, tests/Feature/Admin/AdminProgramCrudTest.php
  notes: Program削除でFK制約違反（lesson_sessions / program_repetition_rules）を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連LessonSessionあり時の削除失敗をFeatureテストで追加検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（残りのinvalid-feedbackへ role="alert" を横展開）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/partials/_master_code_name_sort_status_fields.blade.php, resources/views/admin/additional-items/_form.blade.php, resources/views/admin/locations/_form.blade.php, resources/views/admin/staffs/_form.blade.php, resources/views/admin/store-settings/edit.blade.php, resources/views/auth/login.blade.php, resources/views/auth/register.blade.php, resources/views/auth/forgot-password.blade.php, resources/views/auth/reset-password.blade.php, tests/Feature/Admin/AdminCategoryCrudTest.php, tests/Feature/Admin/AdminStoreSettingsTest.php, tests/Feature/AuthViewsTest.php
  notes: programs と汎用エラーパーシャル以外に残っていた invalid-feedback へ role="alert" を追加し、admin/auth の主要入力画面でスクリーンリーダー通知を一貫化。共有 partial・単独管理画面・認証画面の回帰テストを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Programフォームのinvalid-feedbackへ role="alert" 追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/programs/_form.blade.php, tests/Feature/Admin/AdminProgramCrudTest.php
  notes: programs フォームの各 invalid-feedback に role="alert" を付与し、スクリーンリーダーでの即時通知を改善。必須項目エラー表示で role 属性が描画される回帰テストを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType更新で同一code許可の回帰テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramTypeCrudTest.php
  notes: UpdateProgramTypeRequest が unique:program_types,code,$id で同一レコードを除外する挙動に合わせ、test_update_allows_same_code を追加して Category CRUD テストとの整合性を確保。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings更新経路をトランザクション+行ロック化）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StoreSettingsController.php, tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: StoreSettings 更新時に ConnectionInterface の transaction 内で createOrFirst 後の単一行を lockForUpdate で再取得し、RFP-003 の競合耐性パターンへ整合。設定未作成状態からの update 回帰テストを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（バリデーションエラー表示に role="alert" を追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/partials/_errors.blade.php
  notes: スクリーンリーダーで即時に伝わるよう alert 要素に role="alert" を付与し、アクセシビリティを向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StaffController.php, resources/views/admin/staffs/_delete_error_row.blade.php, tests/Feature/Admin/AdminStaffCrudTest.php
  notes: Staff削除でFK制約違反を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連LessonSessionあり時の削除失敗をFeatureテストで追加検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Location削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/LocationController.php, resources/views/layouts/admin.blade.php, resources/views/admin/locations/_delete_error_row.blade.php, tests/Feature/Admin/AdminLocationCrudTest.php
  notes: Location削除でFK制約違反（lesson_sessions / program_repetition_rules）を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連データあり時の削除失敗をFeatureテストで追加検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramControllerのフォーム取得共通化と主要アクションPHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/ProgramController.php
  notes: create/edit で重複していたカテゴリ・プログラム種別取得を resolveFormMasterData() へ集約。index/create/store/edit/update/destroy に責務・前提・更新方針を示すPHPDocを追加し、規約準拠と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItemController主要アクションへPHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/AdditionalItemController.php
  notes: index/create/store/edit/update/destroy に責務・前提・更新方針を示す短いPHPDocを追加し、コントローラの規約準拠と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStaffCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Location CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminLocationCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettingsテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性を確保。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Category CRUDテストのロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminCategoryCrudTest.php
  notes: role の文字列リテラル（admin/member）を User::ROLE_ADMIN / User::ROLE_MEMBER へ置換し、モデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem FormRequestのstatus定数統一）
  adopted: yes
  classification: 汎用
  targets: app/Http/Requests/Admin/StoreAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php
  notes: status の in ルールでハードコード（active/inactive）を廃止し、AdditionalItem::STATUS_ACTIVE / STATUS_INACTIVE を使用してモデル定数と一貫化。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType CRUDテストのロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramTypeCrudTest.php
  notes: role の文字列リテラル（admin/member）を User::ROLE_ADMIN / User::ROLE_MEMBER へ置換し、モデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType CRUDテストの観点拡充）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramTypeCrudTest.php
  notes: HTMX部分レンダリング、フォーム表示、認可（guest/non-admin）、unique code と invalid status の異常系を追加し、AdminCategoryCrudTest と同水準のCRUD観点へ整合。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（additional_item_type 固定値UIの簡素化）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/additional-items/_form.blade.php
  notes: 選択肢が1件固定の additional_item_type を select から hidden + 表示テキストへ変更し、UIノイズを削減。値は member_profile を固定送信してバリデーション意図を維持。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（programs フォーム数値入力の step 明示）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/programs/_form.blade.php
  notes: 整数前提項目（duration_minutes / price / point_cost / ticket_cost）に step=\"1\" を追加し、UI入力制約とサーバー側 integer バリデーションの意図を一致させた。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（store_settings の単一レコード制約をDBで担保）
  adopted: yes
  classification: 汎用
  targets: database/migrations/2026_03_02_080811_create_store_settings_table.php, app/Http/Controllers/Admin/StoreSettingsController.php, app/Models/StoreSettings.php, database/factories/StoreSettingsFactory.php, tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: singleton_key を単一値 enum（singleton）+ UNIQUE 制約で追加し、StoreSettings 初期化を createOrFirst（singleton_key=singleton）へ変更。設定画面の連続アクセスで常に1行に維持されることをテストで検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PH6-1 マスタ系管理画面 (HTMX) + HTMXインフラ + AdditionalItem/StoreSettingsモデル新規作成
  adopted: no
  classification: none
  targets: routes/web.php, resources/views/layouts/admin.blade.php, public/assets/vendor/htmx/htmx.min.js, app/Http/Controllers/Admin/*.php, app/Http/Requests/Admin/*.php, app/Models/AdditionalItem.php, app/Models/StoreSettings.php, database/factories/AdditionalItemFactory.php, database/factories/StoreSettingsFactory.php, database/migrations/2026_03_02_080811_create_store_settings_table.php, resources/views/admin/**/*.blade.php, tests/Feature/Admin/*.php
  notes: HTMX自己ホスト配置とCSRF自動付与。Category/ProgramType/Program/Location/Staff/AdditionalItem の6リソースCRUDとStoreSettings単行編集。全7コントローラ・14 FormRequest・全ビュー（index/_table/create/edit/_form）。AdditionalItem/StoreSettingsモデル+Factory新規作成。store_settingsマイグレーション追加。49テスト（認可・CRUD・バリデーション・HTMX部分更新）追加、全176テスト通過。

- date: 2026-03-02
  branch: feat/ph13-2-1-admin-layout
  scope: PR指摘対応（管理画面CSS規約準拠とカード描画の重複解消）
  adopted: yes
  classification: 汎用
  targets: public/assets/css/app.css, public/assets/css/pages/admin.css, resources/views/layouts/admin.blade.php, resources/views/admin/dashboard.blade.php
  notes: 管理画面CSSを pages/admin.css へ移動し、BEM命名 + app.cssトークン参照へ統一。ダッシュボードサマリーカードを配列+@foreach化して重複マークアップを削減。

- date: 2026-03-02
  branch: feat/ph13-2-1-admin-layout
  scope: PH13-2-1 管理用共通レイアウトとダッシュボードの実装
  adopted: no
  classification: none
  targets: resources/views/layouts/admin.blade.php, resources/views/admin/dashboard.blade.php, public/assets/css/pages/admin.css, routes/web.php, tests/Feature/AdminDashboardTest.php, tests/Feature/AdminAuthorizationTest.php
  notes: 管理用レイアウト（サイドバー+ヘッダー+メインコンテンツ）を作成。/admin をビュー返却に変更。AdminDashboardTest で描画・ナビ・ログアウト確認。AdminAuthorizationTest のアサーションを日本語化に合わせて更新。

- date: 2026-03-02
  branch: feat/ph13-1-1-admin-auth-guard
  scope: PR指摘対応（nullロールテストをFactoryで統一）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/AdminAuthorizationTest.php
  notes: test_null_role_is_forbidden_on_admin_route で new User 手動生成を User::factory()->makeOne(['role' => null]) に置換。DB制約上 role は NOT NULL のため create ではなく make を使用。

- date: 2026-03-02
  branch: feat/ph13-1-1-admin-auth-guard
  scope: PH13-1-1 管理者認可（Gate）と /admin 保護ルート
  adopted: no
  classification: none
  targets: app/Providers/AppServiceProvider.php, routes/web.php, tests/Feature/AdminAuthorizationTest.php
  notes: Gate::define('access-admin') を追加し、/admin を auth + can:access-admin で保護。未ログイン/非管理者/管理者/境界ロール（空・NULL・未知値）のFeatureテストを追加。

- date: 2026-03-02
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（resolveSubscriptionLine の責務分離）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php, tests/Feature/SubscriptionPaymentWebhookTest.php
  notes: resolveSubscriptionLine で subscription 不一致/price.id 欠落を除外しないようにし、呼び出し側の mismatch / missing price バリデーション分岐を到達可能化。mismatched_line_subscription テスト期待値を具体的な mismatch メッセージに更新。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（RFP-005 Subscription テストデータ作成をFactoryへ統一）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/SubscriptionPaymentWebhookTest.php
  notes: Subscription::query()->create を 6 箇所すべて Subscription::factory()->create へ置換。Cashier同梱Factoryを利用し新規Factory作成は不要。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（RFP-003 createOrFirst 後の lockForUpdate 再取得）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php
  notes: course_entitlements を createOrFirst 後に lockForUpdate で再取得し、子要素作成を同一ロックスコープで実行。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（RFP-002 PHPDoc・エッジケーステスト）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php, tests/Feature/SubscriptionPaymentWebhookTest.php
  notes: $payload 配列形状型 @param 追加。unknown_price / per_category_no_categories のエッジケーステスト追加。per_category カテゴリ未設定時の部分保存不整合を修正（付与前にカテゴリチェック）。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PH5-5 サブスク購読Webhookと周期枠付与ジョブ
  adopted: no
  classification: none
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, app/Jobs/RouteCheckoutSessionWebhookJob.php, database/migrations/2026_03_01_150116_add_unique_period_constraint_to_course_entitlements_table.php, tests/Feature/SubscriptionPaymentWebhookTest.php, config/cashier.php
  notes: invoice.payment_succeeded で course_entitlements を付与し、checkout.session.* (mode=subscription) は専用Jobへルーティング。user+plan+period の unique 制約で重複付与を防止。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PR Nitpick 対応（review-feedback-validate PHPDoc・複数行属性スキップ）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php
  notes: is_rfp009_target_method に PHPDoc 追加。複数行 PHP 8 属性のスキップを skip_attribute_blocks/find_attribute_start_index で対応。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PR Nitpick 対応（release 失敗時の再送出・RFP-009 ガード追加）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/RouteCheckoutSessionWebhookJob.php, scripts/review-feedback-validate.php
  notes: release() 失敗時に例外を再送出し retry/failed() に委譲。RFP-009 を Phase 4 として review-feedback-validate.php に追加。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: Webhook 受信と購入レコード作成の競合リスク対策（信頼性）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/RouteCheckoutSessionWebhookJob.php, app/Providers/AppServiceProvider.php, app/Jobs/ProcessPrepaidPaymentWebhookJob.php
  notes: exists() 即 failed を廃止。RouteCheckoutSessionWebhookJob を常時 dispatch し、対象未検出時は遅延リトライ。markFailed は status=received 時のみ更新。クロージャ PHPDoc を @var に変更。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PR Nitpick 対応（payment_status 検証・遅延決済対応・async_payment_succeeded）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessPrepaidPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, config/cashier.php, tests/Feature/PrepaidPaymentWebhookTest.php
  notes: payment_status を検証（unpaid はスキップ、paid/no_payment_required のみ付与）。checkout.session.async_payment_succeeded を購読・処理対象に追加。handle PHPDoc に遅延決済の扱いを明記。テスト追加（unpaid スキップ・async_succeeded 付与）。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PH5-4 プリペイド Webhook 処理
  adopted: no
  classification: none
  targets: app/Jobs/ProcessPrepaidPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, tests/Feature/PrepaidPaymentWebhookTest.php
  notes: checkout.session.completed のプリペイド向け処理。event_id 冪等、balance_transactions 付与、idempotency_key で二重付与防止。体験/プリペイドの振り分けを AppServiceProvider に追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（refunded ファクトリ・setUp 集約・markRefunded ガード）
  adopted: yes
  classification: 汎用
  targets: database/factories/TrialApplicationFactory.php, tests/Feature/ProcessTrialRefundJobTest.php, app/Jobs/ProcessTrialRefundJob.php
  notes: TrialApplicationFactory に refunded() ステート追加。ProcessTrialRefundJobTest で StripeRefundService モックを setUp に集約。markRefunded に STATUS_REFUNDED ガード追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（payment_intent 欠落時の運用検知ログ）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessTrialRefundJob.php, tests/Feature/ProcessTrialRefundJobTest.php
  notes: paymentIntentId 空時に Log::warning を追加。キュー成功扱いでも運用検知可能に。テストに Log::shouldReceive 検証を追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（ProcessTrialRefundJob PHPDoc・対象外スキップテスト・RFP-009）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessTrialRefundJob.php, tests/Feature/ProcessTrialRefundJobTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: handle/buildRefundIdempotencyKey/markRefunded/markRefundFailed に PHPDoc 追加。並行実行設計意図を handle に記載。RFP-009 追加。対象外スキップ（id 存在しない・既に refunded）テスト追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（ProcessTrialRefundJob 例外再送出でキュー再試行を有効化）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessTrialRefundJob.php, tests/Feature/ProcessTrialRefundJobTest.php
  notes: catch 内で markRefundFailed 後に throw $exception を追加。tries/backoff が有効化。テストは例外再送出を検証する形に更新。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（PHPDoc・リトライ・STATUS_REFUND_FAILED・PII・戻り値型）
  adopted: yes
  classification: 汎用
  targets: app/Services/StripeRefundService.php, app/Models/TrialApplication.php, app/Jobs/ProcessTrialRefundJob.php, app/Jobs/ProcessTrialPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, tests/Feature/TrialPaymentWebhookTest.php
  notes: StripeRefundService PHPDoc・TrialApplication BelongsTo PHPDoc・ProcessTrialRefundJob tries/backoff・ProcessTrialPaymentWebhookJob tries=1・STATUS_REFUND_FAILED 冪等ガード・event_id 欠落時の PII 排除（payload→event_type/checkout_session_id）・postWebhook 戻り値型 TestResponse。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PH5-3 体験決済 Webhook 処理（予約確定/返金: refund_pending/failed + Idempotency-Key）
  adopted: no
  classification: none
  targets: app/Jobs/ProcessTrialPaymentWebhookJob.php, app/Jobs/ProcessTrialRefundJob.php, app/Models/TrialApplication.php, app/Providers/AppServiceProvider.php, app/Services/StripeRefundService.php, config/cashier.php, database/factories/TrialApplicationFactory.php, tests/Feature/ProcessTrialRefundJobTest.php, tests/Feature/TrialPaymentWebhookTest.php
  notes: checkout.session.completed の Webhook 処理。event_id 冪等、予約確定 or refund_pending + Refund Job。返金は Idempotency-Key で冪等化。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PR Nitpick 対応（リプレイ攻撃境界テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: タイムスタンプ期限切れ（time()-301）の境界テスト追加。makeStripeSignatureHeader に ?int $timestamp を追加。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PR Nitpick 対応（PHPDoc・setUp 集約）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: makeStripeSignatureHeader に PHPDoc 追加。config()->set を setUp へ集約（RFP-007）。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PR Nitpick 対応（CSRF除外絞り込み・weird path テスト追加）
  adopted: yes
  classification: 汎用
  targets: bootstrap/app.php, tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: stripe/* → stripe/webhook に限定。署名ヘッダ欠落の feature テスト追加。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PH5-2 Stripe Webhook 署名検証（Stripe-Signature）
  adopted: no
  classification: none
  targets: bootstrap/app.php, tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: stripe/* の CSRF 除外。署名検証の feature テスト（無効/有効署名）追加。

- date: 2026-02-24
  branch: feat/ph5-1-cashier-install
  scope: PR Nitpick 対応 & RFP-008（インラインコメント禁止）の自動検知追加
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-guard.sh, .env.example, config/cashier.php, .cursor/rules/review-feedback-prevention.mdc, scripts/review-feedback-validate.php
  notes: guard: REPO_ROOT/LOG_PATH 先頭集約・git add $LOG_PATH。.env.example: `CASHIER_*` を `STRIPE_*` より前に。cashier.php: コメントアウト import と // Supported: 削除。RFP-008 追加と Phase 3 検知実装。

- date: 2026-02-24
  branch: feat/ph5-1-cashier-install
  scope: PH5-1 Cashier インストール & 設定 / log.md 自動追記
  adopted: no
  classification: none
  targets: composer.json, app/Models/User.php, config/services.php, config/cashier.php, .env.example, database/migrations/*, scripts/review-feedback-guard.sh
  notes: Cashier 導入。User に Billable と $hidden 追加。Stripe 環境変数。guard で log.md 未更新時にテンプレート自動追記。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: PRレビュー指摘対応（インラインコメント→PHPDoc）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php
  notes: CodeRabbit 指摘。Phase 1/Phase 2 のインラインコメントを PHPDoc ブロックへ置換。review-feedback-prevention 2.5 方針に準拠。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: log.md 自動活用 Phase 1+2 実装
  adopted: no
  classification: none
  targets: scripts/review-feedback-validate.php, scripts/review-feedback-guard.sh, tests/Tooling/ReviewFeedbackValidateTest.php
  notes: Phase 1: log 必須キー・classification・adopted・日付・targets の検証。Phase 2: RFP-001 チェッカー（->refresh()/->fresh() 禁止）。guard に統合。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: PRレビュー指摘対応（update 直後の refresh 削除）
  adopted: yes
  classification: 汎用
  targets: app/Services/ReservationService.php
  notes: RFP-001 趣旨に沿い、update() 直後の不要な refresh() を削除。Eloquent の update() は in-memory 属性を更新するため追加 SELECT は不要。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: PH4-3 予約キャンセル・巻き戻しロジック実装
  adopted: no
  classification: none
  targets: app/Services/ReservationService.php, tests/Feature/ReservationServiceTest.php
  notes: ReservationService::cancel() を実装。トランザクション・lockForUpdate・冪等・カウンタデクリメント・異常系テストを追加。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: ReservationService / ReservationServiceTest（レビュー対応一式）
  adopted: yes
  classification: 汎用
  targets: .cursor/rules/review-feedback-prevention.mdc (RFP-001..RFP-007)
  notes: fresh削除、TODO整理、配列形状型、setUp集約、DBファサード回避、同時実行競合、識別子生成TOCTOU対策、テストのFactory化/uniqid排除を汎用ルールとして反映。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: レビュー蓄積漏れ再発防止の運用強化
  adopted: yes
  classification: 汎用
  targets: .cursor/rules/review-feedback-prevention.mdc, scripts/review-feedback-guard.sh, .githooks/pre-commit, .cursor/commands/commit-only.md, .cursor/commands/commit-push.md
  notes: コード変更コミット時に review-feedback log 更新を必須化し、pre-commit/hook と commit コマンド手順で機械的に強制する運用へ変更。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: PRレビュー指摘対応（コメント対応）
  adopted: yes
  classification: 汎用
  targets: README.md, scripts/install-review-feedback-hook.sh, scripts/review-feedback-guard.sh, .cursor/commands/commit-only.md, .cursor/commands/commit-push.md, app/Models/LessonSession.php, tests/Feature/ReservationServiceTest.php
  notes: フック有効化手順のドキュメント化、core.hooksPath方式への変更、case文統合、ガード文言の条件付き明確化、LessonSession belongsTo追加、ModelNotFoundExceptionテスト追加。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: PRレビュー指摘対応（追加）
  adopted: yes
  classification: 汎用
  targets: README.md, .cursor/commands/commit-push.md, app/Models/LessonSession.php, tests/Feature/ReservationServiceTest.php
  notes: フック目的・適用対象の明記、必須ガード見出し分離と||exit 1追加、BelongsToジェネリック型PHPDoc、非存在IDの動的生成。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: レビュー指摘対応（ロールバック検証とresources対象化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/ReservationServiceTest.php, scripts/review-feedback-guard.sh, .cursor/rules/review-feedback-prevention.mdc, .cursor/commands/commit-only.md, .cursor/commands/commit-push.md, README.md
  notes: キャパ超過時の例外テストで予約未作成とカウンタ不変を確認し、レビュー蓄積ガード対象にresources/を追加して関連ルール・ドキュメントの記載を統一。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: PRレビュー指摘対応（commit-push Bセクション）
  adopted: yes
  classification: 汎用
  targets: .cursor/commands/commit-push.md
  notes: Bセクションの git add -A に || exit 1 を追加し、Aセクションと整合。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PH6-2-2 生成候補列挙サービス実装
  adopted: no
  classification: none
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php, tests/Unit/ProgramRepetitionRuleSessionCandidateServiceTest.php
  notes: daily/weekly の候補日時列挙サービスを追加。start_date/end_date/start_time/day_of_week の不正値を fail fast で拒否し、境界値と異常系の unit test を追加。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（private helper PHPDoc 追加）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: private helper に責務・前提・更新方針の PHPDoc を追加し、review-feedback-prevention 2.5 の既存方針に合わせた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（enumerate エントリポイント PHPDoc 追加）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: エントリポイント enumerate() に責務・前提・更新方針の PHPDoc を追加し、review-feedback-prevention 2.5 に合わせた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（enumerateDaily の $rule パラメータ整理）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: day_of_week 検証を enumerate() に移動し、enumerateDaily() から $rule を削除。enumerateWeekly() とシグネチャを揃えた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（lock/transaction/idempotency PHPDoc 明示）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: エントリポイント PHPDoc に Lock/Transaction/Idempotent を明示し、RFP-009 に合わせた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（reservation_management 作成を relation 経由へ統一）
  adopted: yes
  classification: PR限定
  targets: app/Services/ProgramRepetitionRuleSessionGenerationService.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。LessonSession に reservationManagement() を追加済みのため、関連行作成は relation の create() を使う方が外部キーの手詰めを避けて意図を明確にできる。一方で、他サービスとの書き方統一という根拠は現状コードベースでは強くなかったため、その点は採用せず、生成処理の最小差分リファクタに限定して対応した。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（reservation_management 作成失敗時の transaction rollback テスト追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。今回の主題は lesson_sessions と reservation_management を同一 transaction で扱う点にあるため、ReservationManagement::creating で例外を発生させたときに generate() 全体がロールバックされ、両テーブルが 0 件のままになる失敗系テストを追加した。既存実装の挙動確認が目的で、本番コードの変更は行っていない。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（reservation_management 関連の存在検証を false positive にならない形へ修正）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。従来の生成テストは relation()->value(...) の戻り値を int キャストしており、関連行が未作成でも null が 0 になって assertion を通過し得た。daily/weekly の正常系テストで reservationManagement を eager load し、関連が ReservationManagement インスタンスとして存在することを先に検証したうえでカウンタ値を読む形へ修正した。
