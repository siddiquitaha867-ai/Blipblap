<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $range = $this->validatedRange((string) $request->query('range', 'last_30_days'));
        $sort = $this->validatedSort((string) $request->query('sort', 'sales_desc'));
        $search = trim((string) $request->query('search', ''));

        $orders = EsimOrder::query()
            ->where(function ($query): void {
                $query->whereNotNull('paid_at')
                    ->orWhereIn('status', ['paid', 'completed', 'provisioning_failed']);
            })
            ->when($range !== 'all_time', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->get();

        $planIds = $orders->pluck('request_payload.plan_id')->filter()->unique();
        $bundleCodes = $orders->pluck('bundle_code')->filter()->unique();
        $plans = EsimPlan::query()
            ->whereIn('id', $planIds)
            ->orWhereIn('supplier_code', $bundleCodes)
            ->get();

        $rows = $this->planRows($orders, $plans, $search, $sort);
        $summary = [
            'orders' => $orders->count(),
            'plans_sold' => $rows->count(),
            'revenue' => round($rows->sum('revenue'), 2),
            'tax' => round($rows->sum('tax'), 2),
            'supplier_cost' => round($rows->sum('supplier_cost'), 2),
            'profit' => round($rows->sum('profit'), 2),
        ];

        return Inertia::render('Admin/Reports/Index', [
            'summary' => $summary,
            'rows' => $rows->values(),
            'filters' => [
                'search' => $search,
                'range' => $range,
                'sort' => $sort,
                'range_options' => $this->rangeOptions(),
                'sort_options' => $this->sortOptions(),
            ],
        ]);
    }

    private function planRows(Collection $orders, Collection $plans, string $search, string $sort): Collection
    {
        $plansById = $plans->keyBy('id');
        $plansByBundle = $plans->keyBy('supplier_code');

        $rows = $orders
            ->groupBy(fn (EsimOrder $order): string => (string) (data_get($order->request_payload, 'plan_id') ?: $order->bundle_code ?: 'unknown'))
            ->map(function (Collection $group) use ($plansById, $plansByBundle): array {
                $first = $group->first();
                $plan = $plansById->get(data_get($first->request_payload, 'plan_id'))
                    ?: $plansByBundle->get($first->bundle_code);
                $sales = $group->count();
                $supplierUnit = (float) ($plan?->supplier_price ?? 0);
                $configuredTaxUnit = (float) ($plan?->tax_amount ?? 0);
                $subtotal = round($group->sum(fn (EsimOrder $order): float => (float) $order->subtotal), 2);
                $revenue = round($group->sum(fn (EsimOrder $order): float => (float) $order->total), 2);
                $tax = round($group->sum(function (EsimOrder $order) use ($configuredTaxUnit): float {
                    $storedTax = (float) data_get($order->request_payload, 'tax_amount', 0);
                    $orderTax = max(0, (float) $order->total - (float) $order->subtotal);

                    return $orderTax > 0 ? $orderTax : ($storedTax > 0 ? $storedTax : $configuredTaxUnit);
                }), 2);
                $supplierCost = round($supplierUnit * $sales, 2);
                $profit = round($subtotal - $supplierCost - $tax, 2);

                return [
                    'plan_id' => $plan?->id,
                    'plan_title' => $plan?->title ?: (string) data_get($first->request_payload, 'plan_slug', $first->bundle_code),
                    'bundle_code' => $first->bundle_code,
                    'country' => $plan?->country_name ?: $plan?->region_name ?: $plan?->coverage_type ?: 'Unknown',
                    'currency' => $first->currency ?: $plan?->currency ?: config('blipblap.currency', 'USD'),
                    'sales' => $sales,
                    'unit_price' => $sales > 0 ? round($subtotal / $sales, 2) : 0,
                    'unit_tax' => $sales > 0 ? round($tax / $sales, 2) : 0,
                    'supplier_unit' => $supplierUnit,
                    'subtotal' => $subtotal,
                    'revenue' => $revenue,
                    'tax' => $tax,
                    'supplier_cost' => $supplierCost,
                    'profit' => $profit,
                ];
            });

        if ($search !== '') {
            $needle = strtolower($search);
            $rows = $rows->filter(function (array $row) use ($needle): bool {
                return str_contains(strtolower((string) $row['plan_title']), $needle)
                    || str_contains(strtolower((string) $row['bundle_code']), $needle)
                    || str_contains(strtolower((string) $row['country']), $needle);
            });
        }

        return match ($sort) {
            'price_asc' => $rows->sortBy('unit_price'),
            'price_desc' => $rows->sortByDesc('unit_price'),
            'profit_desc' => $rows->sortByDesc('profit'),
            'tax_desc' => $rows->sortByDesc('tax'),
            'revenue_desc' => $rows->sortByDesc('revenue'),
            default => $rows->sortByDesc('sales'),
        };
    }

    private function rangeOptions(): array
    {
        return [
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'last_3_months' => 'Last 3 months',
            'all_time' => 'All time',
        ];
    }

    private function sortOptions(): array
    {
        return [
            'sales_desc' => 'Most sold plans',
            'revenue_desc' => 'Revenue high to low',
            'profit_desc' => 'Profit high to low',
            'tax_desc' => 'Tax high to low',
            'price_asc' => 'Plan price low to high',
            'price_desc' => 'Plan price high to low',
        ];
    }

    private function validatedRange(string $range): string
    {
        return array_key_exists($range, $this->rangeOptions()) ? $range : 'last_30_days';
    }

    private function validatedSort(string $sort): string
    {
        return array_key_exists($sort, $this->sortOptions()) ? $sort : 'sales_desc';
    }

    private function rangeStart(string $range)
    {
        return match ($range) {
            'last_7_days' => now()->subDays(7),
            'last_3_months' => now()->subMonths(3),
            default => now()->subDays(30),
        };
    }
}
