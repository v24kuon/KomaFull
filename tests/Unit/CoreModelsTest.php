<?php

namespace Tests\Unit;

use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class CoreModelsTest extends TestCase
{
    public function test_user_has_member_profile_relation(): void
    {
        // Given: a user model instance
        $user = new User;

        // When: memberProfile relation is resolved
        $relation = $user->memberProfile();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_member_profile_belongs_to_user(): void
    {
        // Given: a member profile model instance
        $profile = new MemberProfile;

        // When: user relation is resolved
        $relation = $profile->user();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_lesson_session_has_starts_at_datetime_cast(): void
    {
        // Given: a lesson session model instance
        $session = new LessonSession;

        // When: cast definitions are inspected
        $casts = $session->getCasts();

        // Then: starts_at is cast as datetime
        $this->assertArrayHasKey('starts_at', $casts);
        $this->assertSame('datetime', $casts['starts_at']);
    }

    public function test_member_profile_status_constants_are_defined(): void
    {
        // Given: member profile status constants
        // When: constants are referenced
        $statuses = [
            MemberProfile::STATUS_PROVISIONAL,
            MemberProfile::STATUS_ACTIVE,
            MemberProfile::STATUS_WITHDRAWN,
        ];

        // Then: all expected statuses are available
        $this->assertSame(['provisional', 'active', 'withdrawn'], $statuses);
    }

    public function test_user_is_administrator_returns_false_when_role_is_not_admin(): void
    {
        // Given: a user model with no role assigned
        $user = new User;

        // When: administrator check is executed
        $isAdministrator = $user->isAdministrator();

        // Then: user is treated as non-admin
        $this->assertFalse($isAdministrator);
    }

    public function test_user_is_administrator_returns_true_when_role_is_admin(): void
    {
        // Given: a user model with admin role
        $user = new User;
        $user->setAttribute('role', User::ROLE_ADMIN);

        // When: administrator check is executed
        $isAdministrator = $user->isAdministrator();

        // Then: user is treated as admin
        $this->assertTrue($isAdministrator);
    }

    public function test_user_is_administrator_does_not_use_is_admin_flag_directly(): void
    {
        // Given: a user model with only is_admin set
        $user = new User;
        $user->setAttribute('is_admin', true);

        // When: administrator check is executed
        $isAdministrator = $user->isAdministrator();

        // Then: user is treated as non-admin without role=admin
        $this->assertFalse($isAdministrator);
    }
}
