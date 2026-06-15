<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use App\Models\PromotionRule;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $supportTableExists = Schema::hasTable((new ContactRequest())->getTable());

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'verified_users' => User::query()->whereNotNull('email_verified_at')->count(),
                'plans' => EsimPlan::query()->count(),
                'orders' => EsimOrder::query()->count(),
                'promotions' => PromotionRule::query()->count(),
                'support_open' => $supportTableExists
                    ? ContactRequest::query()->whereIn('status', ['new', 'in_progress'])->count()
                    : 0,
            ],
            'recentRequests' => $supportTableExists ? ContactRequest::query()
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (ContactRequest $request): array => [
                    'id' => $request->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'topic' => $request->topic,
                    'order_reference' => $request->order_reference,
                    'status' => $request->status,
                ]) : [],
            'recentUsers' => User::query()
                ->latest()
                ->limit(8)
                ->get(['id', 'name', 'email', 'is_admin', 'email_verified_at', 'last_login_at', 'created_at']),
        ]);
    }
}
