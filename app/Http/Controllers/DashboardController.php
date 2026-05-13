<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the agent dashboard with registration statistics.
     *
     * GET /dashboard
     */
    public function index(): View
    {
        $user = Auth::user();

        $totalRegistrations = Registration::count();

        $statusCounts = collect(RegistrationStatus::cases())
            ->mapWithKeys(fn (RegistrationStatus $status) => [
                $status->value => Registration::query()
                    ->where('status', $status->value)
                    ->count(),
            ]);

        $recentRegistrations = Registration::query()
            ->latestFirst()
            ->limit(5)
            ->get();

        $promotionEligible = Registration::query()
            ->promotionEligible()
            ->count();

        $loanApproved = Registration::query()
            ->loanApproved()
            ->count();

        return view('dashboard', compact(
            'user',
            'totalRegistrations',
            'statusCounts',
            'recentRegistrations',
            'promotionEligible',
            'loanApproved',
        ));
    }
}
