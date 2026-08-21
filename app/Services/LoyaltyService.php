<?php

namespace App\Services;

use App\Models\EsimOrder;
use App\Models\LoyaltyBalance;
use App\Models\LoyaltyEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class LoyaltyService
{
    public const POINTS_PER_PURCHASE = 100;

    public const REDEEM_THRESHOLD = 1000;

    public function awardPurchasePoints(EsimOrder $order): ?LoyaltyEvent
    {
        if (! in_array($order->status, ['paid', 'provisioning', 'provisioned', 'topup_processing', 'topup_applied'], true)) {
            return null;
        }

        if (! $order->user_id && ! $order->customer_email) {
            return null;
        }

        $reference = 'purchase:' . $order->id;
        $existing = LoyaltyEvent::query()
            ->where('event_reference', $reference)
            ->first();

        if ($existing) {
            return $existing;
        }

        $event = LoyaltyEvent::query()->create([
            'user_id' => $order->user_id,
            'customer_email' => $order->customer_email,
            'esim_order_id' => $order->id,
            'points' => self::POINTS_PER_PURCHASE,
            'event_type' => 'purchase_award',
            'event_status' => 'completed',
            'event_reference' => $reference,
            'event_payload' => [
                'order_reference' => $order->order_reference,
                'order_type' => $order->order_type,
                'total' => $order->total,
                'currency' => $order->currency,
            ],
        ]);

        $balance = $this->balanceRecord($order->user_id, $order->customer_email);

        $balance->update([
            'points_balance' => (int) $balance->points_balance + self::POINTS_PER_PURCHASE,
            'lifetime_points_earned' => (int) $balance->lifetime_points_earned + self::POINTS_PER_PURCHASE,
            'last_event_at' => now(),
        ]);

        return $event;
    }

    public function summaryForUser(?User $user, ?string $email = null, int $historyLimit = 10): array
    {
        $balance = $this->balanceRecord($user?->id, $email ?: $user?->email);
        $pointsBalance = (int) $balance->points_balance;
        $redeemableRewards = intdiv($pointsBalance, self::REDEEM_THRESHOLD);
        $redeemablePoints = $redeemableRewards * self::REDEEM_THRESHOLD;
        $pointsToNextRedeem = $pointsBalance >= self::REDEEM_THRESHOLD
            ? 0
            : self::REDEEM_THRESHOLD - $pointsBalance;

        return [
            'points_per_purchase' => self::POINTS_PER_PURCHASE,
            'redeem_threshold' => self::REDEEM_THRESHOLD,
            'balance' => [
                'points_balance' => $pointsBalance,
                'lifetime_points_earned' => (int) $balance->lifetime_points_earned,
                'lifetime_points_redeemed' => (int) $balance->lifetime_points_redeemed,
                'redeemable_rewards' => $redeemableRewards,
                'redeemable_points' => $redeemablePoints,
                'points_to_next_redeem' => $pointsToNextRedeem,
                'last_event_at' => optional($balance->last_event_at)?->toIso8601String(),
            ],
            'history' => $this->historyForUser($user?->id, $email ?: $user?->email, $historyLimit)
                ->map(function (LoyaltyEvent $event): array {
                    return [
                        'id' => $event->id,
                        'points' => (int) $event->points,
                        'event_type' => $event->event_type,
                        'event_status' => $event->event_status,
                        'order_reference' => $event->event_payload['order_reference'] ?? null,
                        'order_type' => $event->event_payload['order_type'] ?? null,
                        'currency' => $event->event_payload['currency'] ?? null,
                        'total' => isset($event->event_payload['total']) ? (float) $event->event_payload['total'] : null,
                        'created_at' => optional($event->created_at)?->toIso8601String(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function balanceRecord(?int $userId, ?string $email): LoyaltyBalance
    {
        $email = $email ?: null;

        if ($userId) {
            $balance = LoyaltyBalance::query()
                ->where('user_id', $userId)
                ->first();

            if ($balance) {
                if (! $balance->customer_email && $email) {
                    $balance->update(['customer_email' => $email]);
                }

                return $balance;
            }

            return LoyaltyBalance::query()->create([
                'user_id' => $userId,
                'customer_email' => $email,
            ]);
        }

        return LoyaltyBalance::query()->firstOrCreate([
            'customer_email' => $email,
        ]);
    }

    private function historyForUser(?int $userId, ?string $email, int $limit): Collection
    {
        return LoyaltyEvent::query()
            ->when(
                $userId,
                fn ($query) => $query->where('user_id', $userId),
                fn ($query) => $query->where('customer_email', $email)
            )
            ->latest()
            ->limit($limit)
            ->get();
    }
}
