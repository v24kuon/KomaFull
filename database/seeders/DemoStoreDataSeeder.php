<?php

namespace Database\Seeders;

use App\Models\AdditionalItem;
use App\Models\Category;
use App\Models\CoursePlan;
use App\Models\LessonSession;
use App\Models\Location;
use App\Models\PrepaidProduct;
use App\Models\Program;
use App\Models\ProgramType;
use App\Models\ReservationManagement;
use App\Models\Staff;
use App\Models\StoreSettings;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * デモ・開発用: 店舗設定・各種マスタ・開催枠（当月・翌月）を埋め、公開カレンダーに表示される状態にする。
 *
 * `DatabaseSeeder` からは呼ばない（デフォルトの `db:seed` で大量デモが入らないようにする）。
 * 実行: php artisan db:seed --class=DemoStoreDataSeeder
 */
class DemoStoreDataSeeder extends Seeder
{
    /**
     * デモ用の店舗設定・マスタ・開催枠（当月・翌月）を一括で投入する。
     *
     * 責務: `config('app.timezone')` 基準の「今月」「翌月」に、公開カレンダーへ載る active 開催枠と `reservation_management` カウンタを揃える。
     *
     * トランザクション境界: 全体を `DB::transaction` 1 回に包み、途中で例外が出た場合は当該実行の変更をすべてロールバックする。
     *
     * 再実行・冪等性: 内部は `updateOrCreate` 中心。`singleton_key` やマスタの `code` などで行を特定。開催枠は DB 上の concrete slot 一意（`program_id` + `location_id` + `staff_id` + `starts_at`）に合わせて upsert し、`lesson_sessions.code` はそのスロットを表す文字列に同期する（ローテーション順を変えても別枠へ `lesson_session_id` が付け替わらない）。`ReservationManagement` は `lesson_session_id` に対する行が無いときだけ `firstOrCreate` で 0 初期化し、既存行の `reserved_count` / `reserved_trial_count` は上書きしない（実予約とカウンタの整合を壊さない）。
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedStoreSettings();
            $categoryYoga = $this->seedCategory('CAT-DEMO-01', 'ヨガ・ピラティス', 1);
            $categoryDance = $this->seedCategory('CAT-DEMO-02', 'ダンス', 2);
            $typeGroup = $this->seedProgramType('PT-DEMO-01', 'グループレッスン', 1);
            $typePersonal = $this->seedProgramType('PT-DEMO-02', 'マンツーマン', 2);

            $locShibuya = $this->seedLocation(
                'LOC-DEMO-SHIB',
                'コマフル 渋谷スタジオ',
                '〒150-0002 東京都渋谷区渋谷1-1-1 デモビル3F',
                '03-0000-0001',
                'demo-shibuya@example.com',
                'JR渋谷駅ハチ公口より徒歩5分。体験・一般レッスンとも受付中（デモ文言）。'
            );
            $locYokohama = $this->seedLocation(
                'LOC-DEMO-YOKO',
                'コマフル 横浜スタジオ',
                '〒220-0005 神奈川県横浜市西区南幸2-15 デモスクエア2F',
                '045-000-0002',
                'demo-yokohama@example.com',
                'みなとみらい線「高島町」徒歩3分（デモ文言）。'
            );

            $staffA = $this->seedStaff('STF-DEMO-01', '山田 花子', 'female', 'ヨガアライアンス認定');
            $staffB = $this->seedStaff('STF-DEMO-02', '佐藤 太郎', 'male', 'ピラティス指導歴5年');
            $staffC = $this->seedStaff('STF-DEMO-03', '鈴木 美咲', 'female', 'ジャズダンス');

            $progBeginner = $this->seedProgram(
                'PRG-DEMO-BEG',
                'デモ・初心者ヨガ',
                $categoryYoga->id,
                $typeGroup->id,
                'beginner',
                '呼吸と基本ポーズを丁寧に。未経験の方歓迎（デモ）。',
            );
            $progPilates = $this->seedProgram(
                'PRG-DEMO-PIL',
                'デモ・ピラティス',
                $categoryYoga->id,
                $typeGroup->id,
                'intermediate',
                'コアを鍛え姿勢改善を目指します（デモ）。',
            );
            $progJazz = $this->seedProgram(
                'PRG-DEMO-JAZZ',
                'デモ・ジャズダンス入門',
                $categoryDance->id,
                $typeGroup->id,
                'beginner',
                'リズムに乗って基礎ステップを楽しく学べます（デモ）。',
            );
            $progPersonal = $this->seedProgram(
                'PRG-DEMO-PERS',
                'デモ・マンツーマン（ピラティス）',
                $categoryYoga->id,
                $typePersonal->id,
                'intermediate',
                '個別にフォームを調整します（デモ）。',
            );

            $this->seedAdditionalItems();
            $this->seedPrepaidProducts();
            $this->seedCoursePlan();

            $programs = [$progBeginner, $progPilates, $progJazz, $progPersonal];
            $locations = [$locShibuya, $locYokohama];
            $staffMembers = [$staffA, $staffB, $staffC];

            $this->seedLessonSessionsForMonthRange($programs, $locations, $staffMembers, 0);
            $this->seedLessonSessionsForMonthRange($programs, $locations, $staffMembers, 1);
        });
    }

    private function seedStoreSettings(): void
    {
        StoreSettings::query()->updateOrCreate(
            ['singleton_key' => 'singleton'],
            [
                'program_label' => 'プログラム',
                'session_label' => '開催枠',
                'staff_label' => 'インストラクター',
                'location_label' => '店舗',
                'reserve_deadline_minutes' => 120,
                'cancel_deadline_minutes' => 1440,
                'withdrawal_deadline_days' => 30,
            ]
        );
    }

    private function seedCategory(string $code, string $name, int $sortOrder): Category
    {
        /** @var Category $model */
        $model = Category::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'sort_order' => $sortOrder,
                'status' => Category::STATUS_ACTIVE,
            ]
        );

        return $model;
    }

    private function seedProgramType(string $code, string $name, int $sortOrder): ProgramType
    {
        /** @var ProgramType $model */
        $model = ProgramType::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'sort_order' => $sortOrder,
                'status' => ProgramType::STATUS_ACTIVE,
            ]
        );

        return $model;
    }

    private function seedLocation(
        string $code,
        string $name,
        string $address,
        string $tel,
        string $email,
        string $description,
    ): Location {
        /** @var Location $model */
        $model = Location::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'address' => $address,
                'tel' => $tel,
                'email' => $email,
                'description' => $description,
                'status' => Location::STATUS_ACTIVE,
            ]
        );

        return $model;
    }

    private function seedStaff(string $code, string $name, ?string $gender, string $skill): Staff
    {
        /** @var Staff $model */
        $model = Staff::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'gender' => $gender,
                'birth_date' => '1990-05-15',
                'licence_skill' => $skill,
                'main_expertise' => 'レッスン指導',
                'role' => 'インストラクター',
                'description' => 'デモ用スタッフです。',
                'status' => Staff::STATUS_ACTIVE,
            ]
        );

        return $model;
    }

    private function seedProgram(
        string $code,
        string $name,
        int $categoryId,
        int $programTypeId,
        string $level,
        string $overview,
    ): Program {
        /** @var Program $model */
        $model = Program::query()->updateOrCreate(
            ['code' => $code],
            [
                'category_id' => $categoryId,
                'program_type_id' => $programTypeId,
                'name' => $name,
                'level' => $level,
                'duration_minutes' => 60,
                'overview' => $overview,
                'detail' => "【デモ詳細】\n".str_repeat($overview."\n", 2),
                'price' => 3000,
                'point_cost' => 3,
                'ticket_cost' => 1,
                'status' => Program::STATUS_ACTIVE,
            ]
        );

        return $model;
    }

    private function seedAdditionalItems(): void
    {
        AdditionalItem::query()->updateOrCreate(
            ['code' => 'ADD-DEMO-TEXT'],
            [
                'additional_item_type' => AdditionalItem::TYPE_MEMBER_PROFILE,
                'label_name' => 'ご質問・ご要望（デモ）',
                'input_type' => 'text',
                'digits' => null,
                'select_options' => null,
                'status' => AdditionalItem::STATUS_ACTIVE,
            ]
        );

        AdditionalItem::query()->updateOrCreate(
            ['code' => 'ADD-DEMO-SELECT'],
            [
                'additional_item_type' => AdditionalItem::TYPE_MEMBER_PROFILE,
                'label_name' => 'レッスン経験（デモ）',
                'input_type' => 'select',
                'digits' => null,
                'select_options' => [
                    ['value' => 'none', 'label' => '未経験'],
                    ['value' => 'lt1y', 'label' => '1年未満'],
                    ['value' => 'gte1y', 'label' => '1年以上'],
                ],
                'status' => AdditionalItem::STATUS_ACTIVE,
            ]
        );
    }

    private function seedPrepaidProducts(): void
    {
        PrepaidProduct::query()->updateOrCreate(
            ['code' => 'PP-DEMO-TICKET-4'],
            [
                'prepaid_type' => PrepaidProduct::PREPAID_TYPE_TICKETS,
                'sales_name' => 'デモ・回数券4回',
                'usage_count' => 4,
                'expires_in_days' => 180,
                'price' => 12000,
                'status' => PrepaidProduct::STATUS_ACTIVE,
            ]
        );

        PrepaidProduct::query()->updateOrCreate(
            ['code' => 'PP-DEMO-POINT-500'],
            [
                'prepaid_type' => PrepaidProduct::PREPAID_TYPE_POINTS,
                'sales_name' => 'デモ・ポイント500P',
                'usage_count' => 500,
                'expires_in_days' => 365,
                'price' => 5000,
                'status' => PrepaidProduct::STATUS_ACTIVE,
            ]
        );
    }

    private function seedCoursePlan(): void
    {
        CoursePlan::query()->updateOrCreate(
            ['code' => 'CP-DEMO-MONTH'],
            [
                'name' => 'デモ・月額サブスク（4回枠）',
                'stripe_price_id' => 'price_demo_monthly_4',
                'usage_count' => 4,
                'allocation_type' => CoursePlan::ALLOCATION_TYPE_TOTAL,
                'level' => CoursePlan::LEVEL_STANDARD,
                'description' => 'デモ用プラン。実Stripeと接続する際は price ID を差し替えてください。',
                'status' => CoursePlan::STATUS_ACTIVE,
            ]
        );
    }

    /**
     * 指定オフセット月について、平日（月〜土）の固定時刻に開催枠と `ReservationManagement` を生成する。
     *
     * 生成対象日: `Carbon::now($tz)->startOfMonth()->addMonths($monthOffset)` からその月末までを日単位で走査し、`dayOfWeekIso` が 1〜6（月〜土）の日のみ対象とする（日曜はスキップ）。
     *
     * 各対象日について 10:00 / 14:00 / 18:00 の 3 枠を作る。`lesson_sessions` はマイグレーションの `lesson_sessions_concrete_slot_unique`（`program_id` + `location_id` + `staff_id` + `starts_at`）と同一粒度で `updateOrCreate` する。`code` は `LS-DEMO-P{program_id}-L{location_id}-S{staff_id}-{Ymd-Hi}` 形式でスロットと 1 対 1（`code` 列の unique も満たす）。
     *
     * 既存データ更新方針: 上記 4 キーで行を特定し、定員・`code`・ステータスなどをデモ既定へ上書きする。ローテーション配列の順序だけ変えても、同一スロットは同一 `lesson_session_id` に留まる。`ReservationManagement` は `firstOrCreate(['lesson_session_id' => …], [初期値 0])` とし、行が既にあれば件数は変更しない（欠損時のみ作成）。
     *
     * @param  array<int, Program>  $programs
     * @param  array<int, Location>  $locations
     * @param  array<int, Staff>  $staffMembers
     * @param  int  $monthOffset  0 = 当月、`config('app.timezone')` 上の現在月の月初から
     */
    private function seedLessonSessionsForMonthRange(array $programs, array $locations, array $staffMembers, int $monthOffset): void
    {
        $tz = config('app.timezone');
        $monthStart = Carbon::now($tz)->startOfMonth()->addMonths($monthOffset);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $day = $monthStart->copy();
        $programIndex = 0;
        while ($day->lte($monthEnd)) {
            if ($day->dayOfWeekIso >= 1 && $day->dayOfWeekIso <= 6) {
                foreach ([10, 14, 18] as $hour) {
                    $startsAt = Carbon::create(
                        (int) $day->format('Y'),
                        (int) $day->format('n'),
                        (int) $day->format('j'),
                        $hour,
                        0,
                        0,
                        $tz
                    );
                    $program = $programs[$programIndex % count($programs)];
                    $location = $locations[$programIndex % count($locations)];
                    $staff = $staffMembers[$programIndex % count($staffMembers)];
                    $code = sprintf(
                        'LS-DEMO-P%d-L%d-S%d-%s',
                        $program->id,
                        $location->id,
                        $staff->id,
                        $startsAt->format('Ymd-Hi')
                    );

                    /** @var LessonSession $session */
                    $session = LessonSession::query()->updateOrCreate(
                        [
                            'program_id' => $program->id,
                            'location_id' => $location->id,
                            'staff_id' => $staff->id,
                            'starts_at' => $startsAt,
                        ],
                        [
                            'code' => $code,
                            'capacity' => 12,
                            'trial_capacity' => 3,
                            'status' => LessonSession::STATUS_ACTIVE,
                        ]
                    );

                    ReservationManagement::query()->firstOrCreate(
                        ['lesson_session_id' => $session->id],
                        [
                            'reserved_count' => 0,
                            'reserved_trial_count' => 0,
                        ]
                    );

                    $programIndex++;
                }
            }
            $day->addDay();
        }
    }
}
