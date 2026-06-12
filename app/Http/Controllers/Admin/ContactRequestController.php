<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'open');
        $search = trim((string) $request->query('search', ''));

        $query = ContactRequest::query()
            ->when($status === 'open', fn ($builder) => $builder->whereIn('status', ['new', 'in_progress']))
            ->when($status === 'resolved', fn ($builder) => $builder->where('status', 'resolved'))
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhere('order_reference', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            });

        $requests = $query->latest()->paginate(15)->withQueryString();

        $requests->through(fn (ContactRequest $contactRequest): array => [
            'id' => $contactRequest->id,
            'name' => $contactRequest->name,
            'email' => $contactRequest->email,
            'topic' => $contactRequest->topic,
            'order_reference' => $contactRequest->order_reference,
            'status' => $contactRequest->status,
            'message' => $contactRequest->message,
            'created_at' => $contactRequest->created_at?->toDayDateTimeString(),
            'resolved_at' => $contactRequest->resolved_at?->toDayDateTimeString(),
        ]);

        return Inertia::render('Admin/Support/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'stats' => [
                'new' => ContactRequest::query()->where('status', 'new')->count(),
                'in_progress' => ContactRequest::query()->where('status', 'in_progress')->count(),
                'resolved' => ContactRequest::query()->where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function update(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,in_progress,resolved'],
        ]);

        $contactRequest->update([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        return back()->with('status', 'Support request updated.');
    }
}
