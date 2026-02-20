<?php

namespace Tests\Unit;

use App\Models\BalanceTransaction;
use App\Models\CourseEntitlement;
use App\Models\CourseEntitlementItem;
use App\Models\CoursePlan;
use App\Models\CoursePlanCategory;
use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\PrepaidProduct;
use App\Models\PrepaidPurchase;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function test_user_implements_must_verify_email_contract(): void
    {
        // Given: a user model instance
        $user = new User;

        // When: checking implemented authentication contract
        $implementsContract = $user instanceof MustVerifyEmail;

        // Then: user model supports email verification workflow
        $this->assertTrue($implementsContract);
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

    public function test_reservation_has_course_entitlement_relation(): void
    {
        // Given: a reservation model instance
        $reservation = new Reservation;

        // When: courseEntitlement relation is resolved
        $relation = $reservation->courseEntitlement();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('course_entitlement_id', $relation->getForeignKeyName());
    }

    public function test_prepaid_product_has_expected_relation_and_casts(): void
    {
        // Given: a prepaid product model instance
        $product = new PrepaidProduct;

        // When: relation and casts are resolved
        $relation = $product->purchases();
        $casts = $product->getCasts();

        // Then: relation and numeric casts are correctly defined
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('prepaid_product_id', $relation->getForeignKeyName());
        $this->assertArrayHasKey('usage_count', $casts);
        $this->assertSame('integer', $casts['usage_count']);
        $this->assertArrayHasKey('expires_in_days', $casts);
        $this->assertSame('integer', $casts['expires_in_days']);
        $this->assertArrayHasKey('price', $casts);
        $this->assertSame('integer', $casts['price']);
    }

    public function test_prepaid_product_constants_are_defined(): void
    {
        // Given: prepaid product type and status constants
        // When: constants are referenced
        $types = [
            PrepaidProduct::PREPAID_TYPE_TICKETS,
            PrepaidProduct::PREPAID_TYPE_POINTS,
        ];
        $statuses = [
            PrepaidProduct::STATUS_ACTIVE,
            PrepaidProduct::STATUS_INACTIVE,
        ];

        // Then: all expected constants are available
        $this->assertSame(['tickets', 'points'], $types);
        $this->assertSame(['active', 'inactive'], $statuses);
    }

    public function test_prepaid_purchase_has_expected_relations_casts_and_statuses(): void
    {
        // Given: a prepaid purchase model instance
        $purchase = new PrepaidPurchase;

        // When: relations and casts are resolved
        $userRelation = $purchase->user();
        $productRelation = $purchase->prepaidProduct();
        $transactionRelation = $purchase->balanceTransactions();
        $casts = $purchase->getCasts();
        $statuses = [
            PrepaidPurchase::STATUS_PENDING_PAYMENT,
            PrepaidPurchase::STATUS_PROCESSING,
            PrepaidPurchase::STATUS_COMPLETED,
            PrepaidPurchase::STATUS_EXPIRED,
            PrepaidPurchase::STATUS_GRANT_FAILED,
        ];

        // Then: relations, casts, and statuses are correctly defined
        $this->assertInstanceOf(BelongsTo::class, $userRelation);
        $this->assertSame('user_id', $userRelation->getForeignKeyName());
        $this->assertInstanceOf(BelongsTo::class, $productRelation);
        $this->assertSame('prepaid_product_id', $productRelation->getForeignKeyName());
        $this->assertInstanceOf(HasMany::class, $transactionRelation);
        $this->assertSame('prepaid_purchase_id', $transactionRelation->getForeignKeyName());
        $this->assertArrayHasKey('purchased_at', $casts);
        $this->assertSame('datetime', $casts['purchased_at']);
        $this->assertArrayHasKey('expires_at', $casts);
        $this->assertSame('datetime', $casts['expires_at']);
        $this->assertSame(
            ['pending_payment', 'processing', 'completed', 'expired', 'grant_failed'],
            $statuses
        );
    }

    public function test_balance_transaction_has_expected_relations_casts_and_constants(): void
    {
        // Given: a balance transaction model instance
        $transaction = new BalanceTransaction;

        // When: relations, casts, and constants are resolved
        $userRelation = $transaction->user();
        $purchaseRelation = $transaction->prepaidPurchase();
        $reservationRelation = $transaction->reservation();
        $casts = $transaction->getCasts();
        $units = [
            BalanceTransaction::UNIT_TICKETS,
            BalanceTransaction::UNIT_POINTS,
        ];
        $types = [
            BalanceTransaction::TYPE_GRANT,
            BalanceTransaction::TYPE_CONSUME,
            BalanceTransaction::TYPE_REFUND,
            BalanceTransaction::TYPE_EXPIRE,
            BalanceTransaction::TYPE_ADJUST,
        ];

        // Then: model definition is correctly configured
        $this->assertInstanceOf(BelongsTo::class, $userRelation);
        $this->assertSame('user_id', $userRelation->getForeignKeyName());
        $this->assertInstanceOf(BelongsTo::class, $purchaseRelation);
        $this->assertSame('prepaid_purchase_id', $purchaseRelation->getForeignKeyName());
        $this->assertInstanceOf(BelongsTo::class, $reservationRelation);
        $this->assertSame('reservation_id', $reservationRelation->getForeignKeyName());
        $this->assertArrayHasKey('amount', $casts);
        $this->assertSame('integer', $casts['amount']);
        $this->assertArrayHasKey('occurred_at', $casts);
        $this->assertSame('datetime', $casts['occurred_at']);
        $this->assertArrayHasKey('expires_at', $casts);
        $this->assertSame('datetime', $casts['expires_at']);
        $this->assertSame(['tickets', 'points'], $units);
        $this->assertSame(['grant', 'consume', 'refund', 'expire', 'adjust'], $types);
    }

    public function test_course_plan_has_expected_relations_casts_and_constants(): void
    {
        // Given: a course plan model instance
        $coursePlan = new CoursePlan;

        // When: relations, casts, and constants are resolved
        $categoriesRelation = $coursePlan->categories();
        $entitlementsRelation = $coursePlan->entitlements();
        $casts = $coursePlan->getCasts();
        $allocationTypes = [
            CoursePlan::ALLOCATION_TYPE_TOTAL,
            CoursePlan::ALLOCATION_TYPE_PER_CATEGORY,
        ];
        $statuses = [
            CoursePlan::STATUS_ACTIVE,
            CoursePlan::STATUS_INACTIVE,
        ];

        // Then: model definition is correctly configured
        $this->assertInstanceOf(HasMany::class, $categoriesRelation);
        $this->assertSame('course_plan_id', $categoriesRelation->getForeignKeyName());
        $this->assertInstanceOf(HasMany::class, $entitlementsRelation);
        $this->assertSame('course_plan_id', $entitlementsRelation->getForeignKeyName());
        $this->assertArrayHasKey('usage_count', $casts);
        $this->assertSame('integer', $casts['usage_count']);
        $this->assertSame(['total', 'per_category'], $allocationTypes);
        $this->assertSame(['active', 'inactive'], $statuses);
    }

    public function test_course_plan_category_belongs_to_course_plan(): void
    {
        // Given: a course plan category model instance
        $category = new CoursePlanCategory;

        // When: coursePlan relation is resolved
        $relation = $category->coursePlan();

        // Then: relation type and key mapping are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('course_plan_id', $relation->getForeignKeyName());
    }

    public function test_course_entitlement_has_expected_relations_and_casts(): void
    {
        // Given: a course entitlement model instance
        $entitlement = new CourseEntitlement;

        // When: relations and casts are resolved
        $userRelation = $entitlement->user();
        $planRelation = $entitlement->coursePlan();
        $itemsRelation = $entitlement->items();
        $reservationsRelation = $entitlement->reservations();
        $casts = $entitlement->getCasts();

        // Then: relation mapping and casts are correct
        $this->assertInstanceOf(BelongsTo::class, $userRelation);
        $this->assertSame('user_id', $userRelation->getForeignKeyName());
        $this->assertInstanceOf(BelongsTo::class, $planRelation);
        $this->assertSame('course_plan_id', $planRelation->getForeignKeyName());
        $this->assertInstanceOf(HasMany::class, $itemsRelation);
        $this->assertSame('course_entitlement_id', $itemsRelation->getForeignKeyName());
        $this->assertInstanceOf(HasMany::class, $reservationsRelation);
        $this->assertSame('course_entitlement_id', $reservationsRelation->getForeignKeyName());
        $this->assertArrayHasKey('period_start', $casts);
        $this->assertSame('date', $casts['period_start']);
        $this->assertArrayHasKey('period_end', $casts);
        $this->assertSame('date', $casts['period_end']);
        $this->assertArrayHasKey('granted_uses', $casts);
        $this->assertSame('integer', $casts['granted_uses']);
        $this->assertArrayHasKey('used_uses', $casts);
        $this->assertSame('integer', $casts['used_uses']);
    }

    public function test_course_entitlement_item_has_expected_relation_and_casts(): void
    {
        // Given: a course entitlement item model instance
        $item = new CourseEntitlementItem;

        // When: relation and casts are resolved
        $relation = $item->courseEntitlement();
        $casts = $item->getCasts();

        // Then: relation mapping and casts are correct
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('course_entitlement_id', $relation->getForeignKeyName());
        $this->assertArrayHasKey('granted_uses', $casts);
        $this->assertSame('integer', $casts['granted_uses']);
        $this->assertArrayHasKey('used_uses', $casts);
        $this->assertSame('integer', $casts['used_uses']);
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
