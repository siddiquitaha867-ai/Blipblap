<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignEvent;
use App\Models\PromotionRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Promotions/Index', [
            'promotions' => PromotionRule::query()->latest()->paginate(15),
            'recentEvents' => CampaignEvent::query()->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['conditions'] = [];
        $data['actions'] = [];

        PromotionRule::query()->create($data);

        return back()->with('status', 'Promotion created.');
    }

    public function update(Request $request, PromotionRule $promotion): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $promotion->update($data);

        return back()->with('status', 'Promotion updated.');
    }
}
