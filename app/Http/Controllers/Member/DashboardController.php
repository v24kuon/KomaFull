<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Member\MemberDashboardSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MemberDashboardSummary $memberDashboardSummary,
    ) {}

    public function __invoke(): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $summary = $this->memberDashboardSummary->build($user);

        return view('pages.member.dashboard', [
            'memberProfile' => $user->memberProfile,
            'ticketBalance' => $summary['ticket_balance'],
            'pointBalance' => $summary['point_balance'],
            'subscriptionEntitlements' => $summary['subscription_entitlements'],
            'upcomingReservations' => $summary['upcoming_reservations'],
        ]);
    }
}
