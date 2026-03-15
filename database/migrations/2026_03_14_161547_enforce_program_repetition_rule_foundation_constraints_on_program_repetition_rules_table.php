<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SQLITE_TEMP_TABLE = 'program_repetition_rules_tmp';

    private const SCHEDULE_CHECK_NAME = 'program_repetition_rules_supported_schedule_check';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(constrainFoundation: true);

            return;
        }

        Schema::table('program_repetition_rules', function (Blueprint $table) {
            $table->enum('cycle_type', ['daily', 'weekly'])->change();
            $table->date('end_date')->change();
        });

        $this->addScheduleCheckConstraint();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteTable(constrainFoundation: false);

            return;
        }

        $this->dropScheduleCheckConstraint();

        Schema::table('program_repetition_rules', function (Blueprint $table) {
            $table->string('cycle_type')->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    private function addScheduleCheckConstraint(): void
    {
        DB::statement(sprintf(
            'ALTER TABLE program_repetition_rules ADD CONSTRAINT %s CHECK (%s)',
            self::SCHEDULE_CHECK_NAME,
            $this->scheduleCheckExpression()
        ));
    }

    /**
     * Drop the schedule check constraint using the syntax required by each
     * supported driver. `sqlite` is handled by table rebuilds in `up()` / `down()`.
     */
    private function dropScheduleCheckConstraint(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE program_repetition_rules DROP CHECK %s',
                self::SCHEDULE_CHECK_NAME
            ));

            return;
        }

        if ($driver === 'pgsql' || $driver === 'sqlsrv') {
            DB::statement(sprintf(
                'ALTER TABLE program_repetition_rules DROP CONSTRAINT %s',
                self::SCHEDULE_CHECK_NAME
            ));
        }
    }

    private function rebuildSqliteTable(bool $constrainFoundation): void
    {
        Schema::withoutForeignKeyConstraints(function () use ($constrainFoundation): void {
            Schema::dropIfExists(self::SQLITE_TEMP_TABLE);

            DB::statement($this->sqliteCreateStatement($constrainFoundation));
            DB::statement($this->sqliteCopyStatement());

            Schema::drop('program_repetition_rules');
            DB::statement(sprintf(
                'ALTER TABLE %s RENAME TO program_repetition_rules',
                self::SQLITE_TEMP_TABLE
            ));
        });
    }

    private function sqliteCreateStatement(bool $constrainFoundation): string
    {
        $cycleTypeDefinition = $constrainFoundation
            ? "varchar not null check (cycle_type in ('daily', 'weekly'))"
            : 'varchar not null';

        $endDateDefinition = $constrainFoundation ? 'date not null' : 'date null';

        $scheduleConstraint = $constrainFoundation
            ? sprintf(', check (%s)', $this->scheduleCheckExpression())
            : '';

        return sprintf(
            <<<'SQL'
CREATE TABLE %s (
    id integer primary key autoincrement not null,
    program_id integer not null,
    location_id integer not null,
    staff_id integer not null,
    cycle_type %s,
    day_of_week integer null,
    week_of_month integer null,
    start_date date not null,
    end_date %s,
    start_time time not null,
    capacity integer not null,
    trial_capacity integer not null,
    status varchar not null default 'active',
    created_at datetime null,
    updated_at datetime null,
    foreign key (program_id) references programs (id) on update cascade on delete restrict,
    foreign key (location_id) references locations (id) on update cascade on delete restrict,
    foreign key (staff_id) references staffs (id) on update cascade on delete restrict%s
)
SQL,
            self::SQLITE_TEMP_TABLE,
            $cycleTypeDefinition,
            $endDateDefinition,
            $scheduleConstraint
        );
    }

    private function sqliteCopyStatement(): string
    {
        return sprintf(
            <<<'SQL'
INSERT INTO %s (
    id,
    program_id,
    location_id,
    staff_id,
    cycle_type,
    day_of_week,
    week_of_month,
    start_date,
    end_date,
    start_time,
    capacity,
    trial_capacity,
    status,
    created_at,
    updated_at
)
SELECT
    id,
    program_id,
    location_id,
    staff_id,
    cycle_type,
    day_of_week,
    week_of_month,
    start_date,
    end_date,
    start_time,
    capacity,
    trial_capacity,
    status,
    created_at,
    updated_at
FROM program_repetition_rules
SQL,
            self::SQLITE_TEMP_TABLE
        );
    }

    private function scheduleCheckExpression(): string
    {
        return "((cycle_type = 'daily' and day_of_week is null and week_of_month is null) or (cycle_type = 'weekly' and day_of_week is not null and day_of_week between 0 and 6 and week_of_month is null))";
    }
};
