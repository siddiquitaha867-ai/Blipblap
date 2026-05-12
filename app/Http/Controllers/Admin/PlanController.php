<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, EsimPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'unlimited' => ['required', 'boolean'],
            'data_amount' => ['nullable', 'numeric', 'min:0'],
            'data_unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
        ]);

        if (! (bool) $data['unlimited'] && ($data['data_amount'] === null || $data['data_unit'] === null || $data['data_unit'] === '')) {
            throw ValidationException::withMessages([
                'data_amount' => 'Set a data amount and unit for limited plans.',
            ]);
        }

        if ((bool) $data['unlimited']) {
            $data['data_amount'] = null;
            $data['data_unit'] = null;
        }

        $plan->update($data);

        return back()->with('status', 'Plan updated.');
    }
}
