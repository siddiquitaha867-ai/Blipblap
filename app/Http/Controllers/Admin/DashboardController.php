<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use App\Models\PromotionRule;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'verified_users' => User::query()->whereNotNull('email_verified_at')->count(),
                'plans' => EsimPlan::query()->count(),
                'orders' => EsimOrder::query()->count(),
                'promotions' => PromotionRule::query()->count(),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->limit(8)
                ->get(['id', 'name', 'email', 'is_admin', 'email_verified_at', 'last_login_at', 'created_at']),
        ]);
    }
}
