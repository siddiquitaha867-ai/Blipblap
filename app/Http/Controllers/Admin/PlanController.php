<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $plans = EsimPlan::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('supplier_code', 'like', "%{$search}%")
                        ->orWhere('country_name', 'like', "%{$search}%")
                        ->orWhere('region_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderByRaw('COALESCE(country_name, region_name, coverage_type)')
            ->orderBy('duration_days')
            ->orderBy('data_amount')
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'filters' => [
                'search' => $search,
            ],
            'bulkScopes' => [
                'all' => 'All packages',
                'country' => 'One country',
                'region' => 'One region',
                'coverage' => 'Coverage type',
                'title' => 'Title / keyword',
            ],
        ]);
    }

    public function country(string $country): Response
    {
        $plans = EsimPlan::query()
            ->where(function ($query) use ($country): void {
                $query->where('country_name', $country)
                    ->orWhere('region_name', $country)
                    ->orWhere('coverage_type', $country);
            })
            ->orderByDesc('is_active')
            ->orderBy('duration_days')
            ->orderBy('data_amount')
            ->get();

        abort_if($plans->isEmpty(), 404);

        return Inertia::render('Admin/Plans/Country', [
            'country' => $country,
            'plans' => $plans->map(fn (EsimPlan $plan): array => $this->planPayload($plan))->values(),
        ]);
    }

    public function update(Request $request, EsimPlan $plan): RedirectResponse
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'unlimited' => ['required', 'boolean'],
            'data_amount' => ['nullable', 'numeric', 'min:0'],
            'data_unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
        ];

        if (Schema::hasColumn('esim_plans', 'tax_amount')) {
            $rules['tax_amount'] = ['required', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);

        if (! (bool) $data['unlimited'] && ($data['data_amount'] === null || $data['data_unit'] === null || $data['data_unit'] === '')) {
            throw ValidationException::withMessages([
                'data_amount' => 'Set a data amount and unit for limited plans.',
            ]);
        }

        if ((bool) $data['unlimited']) {
            $data['data_amount'] = null;
            $data['data_unit'] = null;
        }

        if (! Schema::hasColumn('esim_plans', 'tax_amount')) {
            unset($data['tax_amount']);
        }

        $plan->update($data);

        return back()->with('status', 'Plan updated.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope_type' => ['required', 'in:all,country,region,coverage,title'],
            'scope_value' => ['nullable', 'string', 'max:255'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'unlimited' => ['nullable', 'boolean'],
            'margin_percent' => ['required', 'numeric', 'min:0'],
            'fixed_markup' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0'],
            'featured_only' => ['nullable', 'boolean'],
            'active_only' => ['nullable', 'boolean'],
        ]);

        $query = EsimPlan::query();

        if ((bool) ($data['active_only'] ?? true)) {
            $query->where('is_active', true);
        }

        if ((bool) ($data['featured_only'] ?? false)) {
            $query->where('is_featured', true);
        }

        if (array_key_exists('unlimited', $data) && $data['unlimited'] !== null) {
            $query->where('unlimited', (bool) $data['unlimited']);
        }

        if (! empty($data['duration_days'])) {
            $query->where('duration_days', (int) $data['duration_days']);
        }

        $scopeValue = trim((string) ($data['scope_value'] ?? ''));

        match ($data['scope_type']) {
            'country' => $query->where('country_name', $scopeValue),
            'region' => $query->where('region_name', $scopeValue),
            'coverage' => $query->where('coverage_type', $scopeValue),
            'title' => $query->where('title', 'like', '%' . $scopeValue . '%'),
            default => null,
        };

        $plans = $query->get();

        if ($plans->isEmpty()) {
            return back()->with('status', 'No plans matched the selected pricing filters.');
        }

        $marginPercent = (float) $data['margin_percent'];
        $fixedMarkup = (float) $data['fixed_markup'];
        $taxPercent = (float) $data['tax_percent'];

        foreach ($plans as $plan) {
            $supplierPrice = (float) $plan->supplier_price;
            $subtotal = $supplierPrice + $fixedMarkup;
            $withMargin = $subtotal * (1 + ($marginPercent / 100));
            $retail = round($withMargin * (1 + ($taxPercent / 100)), 2);

            $plan->update([
                'retail_price' => max(0, $retail),
            ]);
        }

        return back()->with('status', sprintf(
            'Updated %d plans with %.2f%% margin, %.2f fixed markup, and %.2f%% tax.',
            $plans->count(),
            $marginPercent,
            $fixedMarkup,
            $taxPercent,
        ));
    }

    private function planPayload(EsimPlan $plan): array
    {
        $supplierPrice = (float) $plan->supplier_price;
        $retailPrice = (float) $plan->retail_price;
        $taxAmount = (float) ($plan->tax_amount ?? 0);
        $marginAmount = round($retailPrice - $supplierPrice, 2);
        $netProfit = round($retailPrice - $supplierPrice - $taxAmount, 2);
        $marginPercent = $supplierPrice > 0
            ? round(($marginAmount / $supplierPrice) * 100, 2)
            : null;
        $netProfitPercent = $supplierPrice > 0
            ? round(($netProfit / $supplierPrice) * 100, 2)
            : null;

        return [
            ...$plan->toArray(),
            'margin_amount' => $marginAmount,
            'margin_percent' => $marginPercent,
            'net_profit' => $netProfit,
            'net_profit_percent' => $netProfitPercent,
            'total_price' => round($retailPrice + $taxAmount, 2),
        ];
    }
}
