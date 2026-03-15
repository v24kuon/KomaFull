<?php

namespace Tests\Unit;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProgramRepetitionRuleFoundationMigrationTest extends TestCase
{
    public function test_up_fails_fast_on_mysql_versions_before_8_0_16(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');

        Schema::shouldReceive('getConnection')->once()->andReturn($connection);
        Schema::shouldReceive('table')->never();
        DB::shouldReceive('selectOne')->once()->with('select version() as version')->andReturn((object) ['version' => '8.0.15']);
        DB::shouldReceive('statement')->never();

        $this->expectException(RuntimeException::class);

        $this->runMigrationUp();
    }

    private function migration(): Migration
    {
        /** @var Migration $migration */
        $migration = require base_path('database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php');

        return $migration;
    }

    private function runMigrationUp(): void
    {
        $migration = $this->migration();

        (new \ReflectionMethod($migration, 'up'))->invoke($migration);
    }
}
