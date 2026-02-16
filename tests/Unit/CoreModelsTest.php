<?php

namespace Tests\Unit;

use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Reservation;
use App\Models\ReservationManagement;
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

    public function test_member_profile_has_date_and_datetime_casts(): void
    {
        // Given: a member profile model instance
        $profile = new MemberProfile;

        // When: cast definitions are inspected
        $casts = $profile->getCasts();

        // Then: date and datetime casts are properly defined
        $this->assertArrayHasKey('birth_date', $casts);
        $this->assertSame('date', $casts['birth_date']);
        $this->assertArrayHasKey('activated_at', $casts);
        $this->assertSame('datetime', $casts['activated_at']);
        $this->assertArrayHasKey('withdrawn_at', $casts);
        $this->assertSame('datetime', $casts['withdrawn_at']);
    }

    public function test_lesson_session_status_constants_are_defined(): void
    {
        // Given: lesson session status constants
        // When: constants are referenced
        $statuses = [
            LessonSession::STATUS_ACTIVE,
            LessonSession::STATUS_INACTIVE,
        ];

        // Then: all expected statuses are available
        $this->assertSame(['active', 'inactive'], $statuses);
    }

    public function test_reservation_has_user_relation(): void
    {
        // Given: a reservation model instance
        $reservation = new Reservation;

        // When: user relation is resolved
        $relation = $reservation->user();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_reservation_has_lesson_session_relation(): void
    {
        // Given: a reservation model instance
        $reservation = new Reservation;

        // When: lessonSession relation is resolved
        $relation = $reservation->lessonSession();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('lesson_session_id', $relation->getForeignKeyName());
    }

    public function test_reservation_has_canceled_at_datetime_cast(): void
    {
        // Given: a reservation model instance
        $reservation = new Reservation;

        // When: cast definitions are inspected
        $casts = $reservation->getCasts();

        // Then: canceled_at is cast as datetime
        $this->assertArrayHasKey('canceled_at', $casts);
        $this->assertSame('datetime', $casts['canceled_at']);
    }

    public function test_reservation_status_constants_are_defined(): void
    {
        // Given: reservation status constants
        // When: constants are referenced
        $statuses = [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_CANCELED,
        ];

        // Then: all expected statuses are available
        $this->assertSame(['confirmed', 'canceled'], $statuses);
    }

    public function test_reservation_seat_bucket_constants_are_defined(): void
    {
        // Given: reservation seat bucket constants
        // When: constants are referenced
        $buckets = [
            Reservation::SEAT_BUCKET_TRIAL,
            Reservation::SEAT_BUCKET_NORMAL,
        ];

        // Then: all expected buckets are available
        $this->assertSame(['trial', 'normal'], $buckets);
    }

    public function test_reservation_payment_method_constants_are_defined(): void
    {
        // Given: reservation payment method constants
        // When: constants are referenced
        $methods = [
            Reservation::PAYMENT_METHOD_SUBSCRIPTION,
            Reservation::PAYMENT_METHOD_TICKETS,
            Reservation::PAYMENT_METHOD_POINTS,
            Reservation::PAYMENT_METHOD_TRIAL_CARD,
            Reservation::PAYMENT_METHOD_TRIAL_ONSITE,
        ];

        // Then: all expected payment methods are available
        $this->assertSame(['subscription', 'tickets', 'points', 'trial_card', 'trial_onsite'], $methods);
    }

    public function test_reservation_has_cost_integer_casts(): void
    {
        // Given: a reservation model instance
        $reservation = new Reservation;

        // When: cast definitions are inspected
        $casts = $reservation->getCasts();

        // Then: cost fields are cast as integers
        $this->assertArrayHasKey('ticket_cost', $casts);
        $this->assertSame('integer', $casts['ticket_cost']);
        $this->assertArrayHasKey('point_cost', $casts);
        $this->assertSame('integer', $casts['point_cost']);
    }

    public function test_reservation_management_has_lesson_session_relation(): void
    {
        // Given: a reservation management model instance
        $management = new ReservationManagement;

        // When: lessonSession relation is resolved
        $relation = $management->lessonSession();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('lesson_session_id', $relation->getForeignKeyName());
    }

    public function test_reservation_management_has_reserved_count_integer_cast(): void
    {
        // Given: a reservation management model instance
        $management = new ReservationManagement;

        // When: casts are retrieved
        $casts = $management->getCasts();

        // Then: reserved_count has integer cast
        $this->assertArrayHasKey('reserved_count', $casts);
        $this->assertSame('integer', $casts['reserved_count']);
    }

    public function test_reservation_management_has_reserved_trial_count_integer_cast(): void
    {
        // Given: a reservation management model instance
        $management = new ReservationManagement;

        // When: casts are retrieved
        $casts = $management->getCasts();

        // Then: reserved_trial_count has integer cast
        $this->assertArrayHasKey('reserved_trial_count', $casts);
        $this->assertSame('integer', $casts['reserved_trial_count']);
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
