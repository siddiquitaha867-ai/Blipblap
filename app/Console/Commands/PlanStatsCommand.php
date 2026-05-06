<?php

namespace App\Console\Commands;

use App\Models\EsimPlan;
use Illuminate\Console\Command;

class PlanStatsCommand extends Command
{
    protected $signature = 'blipblap:plan-stats';

    protected $description = 'Show local eSIM plan counts.';

    public function handle(): int
    {
        $this->line('Local plans: ' . EsimPlan::query()->count());
        $this->line('Active local plans: ' . EsimPlan::query()->where('is_active', true)->count());

        $this->table(
            ['Country', 'Plans'],
            EsimPlan::query()
                ->select('country_name')
                ->selectRaw('count(*) as total')
                ->groupBy('country_name')
                ->orderBy('country_name')
                ->get()
                ->map(fn (EsimPlan $plan): array => [
                    $plan->country_name ?: 'Unknown',
                    $plan->total,
                ])
                ->toArray()
        );

        return self::SUCCESS;
    }
}
