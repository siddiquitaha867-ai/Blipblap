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

        $orders->through(function (EsimOrder $order): array {
            return [
                'id' => $order->id,
                'order_reference' => $order->order_reference,
                'esim_go_lookup_reference' => $this->esimGoLookupReference($order),
                'apply_reference' => $this->providerApplyReference($order),
                'order_type' => $order->order_type,
                'payment_reference' => $order->payment_reference,
                'bundle_code' => $order->bundle_code,
                'status' => $order->status,
                'fulfillment_status' => $order->fulfillment_status,
                'currency' => $order->currency,
                'total' => (float) $order->total,
                'subtotal' => (float) $order->subtotal,
                'paid_at' => optional($order->paid_at)?->toDateTimeString(),
                'created_at' => optional($order->created_at)?->toDateTimeString(),
                'customer_email' => $order->customer_email,
                'customer_name' => (string) data_get($order->request_payload, 'customer_name', ''),
                'customer_phone' => (string) data_get($order->request_payload, 'customer_phone', ''),
                'address_line1' => (string) data_get($order->request_payload, 'address_line1', ''),
                'address_line2' => (string) data_get($order->request_payload, 'address_line2', ''),
                'city' => (string) data_get($order->request_payload, 'city', ''),
                'state' => (string) data_get($order->request_payload, 'state', ''),
                'postal_code' => (string) data_get($order->request_payload, 'postal_code', ''),
                'country' => (string) data_get($order->request_payload, 'country', ''),
                'payment_method' => $this->paymentMethod($order),
                'payment_brand' => (string) data_get($order->response_payload, 'payment_method_details.card.brand', ''),
                'payment_last4' => (string) data_get($order->response_payload, 'payment_method_details.card.last4', ''),
                'iccid' => (string) $order->iccid,
            ];
        });

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
                'BlipBlap Reference',
                'eSIM Go Lookup Reference',
                'Order Type',
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
                            $order->order_reference,
                            $this->csvText($this->esimGoLookupReference($order)),
                            $order->order_type,
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
                        ->orWhere('request_payload->customer_name', 'like', "%{$search}%")
                        ->orWhere('order_reference', 'like', "%{$search}%")
                        ->orWhere('apply_reference', 'like', "%{$search}%")
                        ->orWhere('response_payload->esim_go_lookup_reference', 'like', "%{$search}%")
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

    private function esimGoLookupReference(EsimOrder $order): string
    {
        return (string) (
            $this->providerApplyReference($order)
            ?: data_get($order->response_payload, 'esim_go_lookup_reference')
            ?: data_get($order->response_payload, 'esim_go_order.orderReference')
            ?: data_get($order->response_payload, 'esim_go_order.order_reference')
            ?: data_get($order->response_payload, 'esim_go_order.reference')
            ?: data_get($order->response_payload, 'esim_go_order.id')
            ?: data_get($order->response_payload, 'topup_apply.applyReference')
            ?: data_get($order->response_payload, 'topup_apply.apply_reference')
            ?: data_get($order->response_payload, 'topup_apply.id')
            ?: $order->order_reference
        );
    }

    private function providerApplyReference(EsimOrder $order): string
    {
        $reference = trim((string) $order->apply_reference);

        if ($reference === '' || str_starts_with($reference, 'pi_')) {
            return '';
        }

        return $reference;
    }

    private function csvText(?string $value): string
    {
        return $value === null || $value === '' ? '' : '="' . str_replace('"', '""', $value) . '"';
    }
}
