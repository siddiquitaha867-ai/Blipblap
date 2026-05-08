<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignEvent;
use App\Models\EsimEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $type = (string) $request->query('type', 'esim');

        if ($type === 'campaign') {
            $logs = CampaignEvent::query()
                ->latest()
                ->paginate(20)
                ->withQueryString();
        } else {
            $type = 'esim';
            $logs = EsimEvent::query()
                ->latest()
                ->paginate(20)
                ->withQueryString();
        }

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => [
                'type' => $type,
            ],
        ]);
    }
}
