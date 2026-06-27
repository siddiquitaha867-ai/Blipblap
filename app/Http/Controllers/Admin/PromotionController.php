<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignEvent;
use App\Models\EsimPlan;
use App\Models\PromotionRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Promotions/Index', [
            'promotions' => PromotionRule::query()->latest()->paginate(15),
            'plans' => EsimPlan::query()
                ->where('is_active', true)
                ->orderBy('country_name')
                ->orderBy('title')
                ->get(['id', 'title', 'supplier_code', 'country_name', 'region_name', 'coverage_type', 'retail_price', 'currency'])
                ->map(fn (EsimPlan $plan): array => [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'supplier_code' => $plan->supplier_code,
                    'location' => $plan->country_name ?: $plan->region_name ?: $plan->coverage_type,
                    'price' => (float) $plan->retail_price,
                    'currency' => $plan->currency,
                ]),
            'recentEvents' => CampaignEvent::query()->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:40'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'applies_to' => ['required', 'in:all,plans'],
            'plan_ids' => ['array'],
            'plan_ids.*' => ['integer', 'exists:esim_plans,id'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $planIds = collect($data['plan_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($data['applies_to'] === 'plans' && $planIds === []) {
            return back()
                ->withErrors(['plan_ids' => 'Select at least one plan for this promotion.'])
                ->withInput();
        }

        if ($data['discount_type'] === 'percent' && (float) $data['discount_value'] > 100) {
            return back()
                ->withErrors(['discount_value' => 'Percentage discounts cannot be more than 100%.'])
                ->withInput();
        }

        $code = Str::upper(preg_replace('/[^A-Z0-9_-]/i', '', (string) $data['code']));

        if ($code === '') {
            return back()
                ->withErrors(['code' => 'Enter or generate a valid promotion code.'])
                ->withInput();
        }

        $exists = PromotionRule::query()
            ->where('conditions->code', $code)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['code' => 'This promotion code already exists.'])
                ->withInput();
        }

        $promotion = [
            'title' => $data['title'],
            'rule_type' => $data['rule_type'],
            'is_active' => $data['is_active'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'conditions' => [
                'code' => $code,
                'applies_to' => $data['applies_to'],
                'plan_ids' => $data['applies_to'] === 'plans' ? $planIds : [],
                'usage_limit' => $data['usage_limit'] ?? null,
            ],
            'actions' => [
                'discount_type' => $data['discount_type'],
                'discount_value' => (float) $data['discount_value'],
            ],
        ];

        PromotionRule::query()->create($promotion);

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
