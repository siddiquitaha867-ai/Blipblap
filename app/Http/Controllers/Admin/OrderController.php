<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimOrder;
use App\Models\EsimPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $range = $this->validatedRange((string) $request->query('range', 'last_7_days'));

        $orders = $this->filteredOrders($search, $range)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'range' => $range,
                'range_options' => $this->rangeOptions(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $range = $this->validatedRange((string) $request->query('range', 'last_7_days'));
        $filename = 'blipblap-orders-' . $range . '-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($search, $range): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Invoice No',
                'Order ID',
                'Date',
                'Customer Name',
                'Customer Email',
                'Products',
                'Bundle Code',
                'Subtotal',
                'Tax Amount',
                'Total',
                'Currency',
                'Payment Method',
                'Payment Reference',
                'Status',
                'Fulfillment Status',
                'ICCID',
            ]);

            $this->filteredOrders($search, $range)
                ->oldest()
                ->chunk(200, function ($orders) use ($handle): void {
                    $planNames = EsimPlan::query()
                        ->whereIn('id', $orders->pluck('request_payload.plan_id')->filter()->unique())
                        ->pluck('title', 'id');

                    foreach ($orders as $order) {
                        $subtotal = (float) $order->subtotal;
                        $total = (float) $order->total;
                        $tax = max(0, $total - $subtotal);

                        fputcsv($handle, [
                            $order->order_reference ?: 'BB-' . $order->id,
                            $order->id,
                            $this->csvText(optional($order->created_at)->format('Y-m-d H:i:s')),
                            data_get($order->request_payload, 'customer_name', ''),
                            $order->customer_email,
                            $planNames->get(data_get($order->request_payload, 'plan_id'))
                                ?: data_get($order->request_payload, 'plan_slug', $order->bundle_code),
                            $order->bundle_code,
                            number_format($subtotal, 2, '.', ''),
                            number_format($tax, 2, '.', ''),
                            number_format($total, 2, '.', ''),
                            $order->currency,
                            $this->paymentMethod($order),
                            $order->payment_reference,
                            $order->status,
                            $order->fulfillment_status,
                            $order->iccid,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredOrders(string $search, string $range): Builder
    {
        return EsimOrder::query()
            ->where('created_at', '>=', $this->rangeStart($range))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('customer_email', 'like', "%{$search}%")
                        ->orWhere('order_reference', 'like', "%{$search}%")
                        ->orWhere('payment_reference', 'like', "%{$search}%")
                        ->orWhere('bundle_code', 'like', "%{$search}%")
                        ->orWhere('iccid', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            });
    }

    private function rangeOptions(): array
    {
        return [
            'last_7_days' => 'Last 7 days',
            'last_15_days' => 'Last 15 days',
            'last_1_month' => 'Last 1 month',
            'last_3_months' => 'Last 3 months',
        ];
    }

    private function validatedRange(string $range): string
    {
        return array_key_exists($range, $this->rangeOptions()) ? $range : 'last_7_days';
    }

    private function rangeStart(string $range)
    {
        return match ($range) {
            'last_15_days' => now()->subDays(15),
            'last_1_month' => now()->subMonth(),
            'last_3_months' => now()->subMonths(3),
            default => now()->subDays(7),
        };
    }

    private function paymentMethod(EsimOrder $order): string
    {
        $methods = data_get($order->response_payload, 'payment_method_types');

        if (is_array($methods) && count($methods) > 0) {
            return implode(', ', $methods);
        }

        return data_get($order->response_payload, 'payment_method_collection', 'stripe');
    }

    private function csvText(?string $value): string
    {
        return $value === null || $value === '' ? '' : '="' . str_replace('"', '""', $value) . '"';
    }
}
