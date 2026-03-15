<?php

namespace Tests\Unit;

use App\Models\LessonSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class LessonSessionModelTest extends TestCase
{
    /**
     * LessonSession が期待する cast と relation を持つこと。
     */
    public function test_lesson_session_has_expected_casts_and_relations(): void
    {
        $session = new LessonSession;
        $casts = $session->getCasts();

        $this->assertArrayHasKey('program_id', $casts);
        $this->assertSame('integer', $casts['program_id']);
        $this->assertArrayHasKey('location_id', $casts);
        $this->assertSame('integer', $casts['location_id']);
        $this->assertArrayHasKey('staff_id', $casts);
        $this->assertSame('integer', $casts['staff_id']);
        $this->assertArrayHasKey('starts_at', $casts);
        $this->assertSame('datetime', $casts['starts_at']);
        $this->assertArrayHasKey('capacity', $casts);
        $this->assertSame('integer', $casts['capacity']);
        $this->assertArrayHasKey('trial_capacity', $casts);
        $this->assertSame('integer', $casts['trial_capacity']);
        $this->assertInstanceOf(BelongsTo::class, $session->program());
        $this->assertInstanceOf(BelongsTo::class, $session->location());
        $this->assertInstanceOf(BelongsTo::class, $session->staff());
        $this->assertInstanceOf(HasOne::class, $session->reservationManagement());
    }
}
