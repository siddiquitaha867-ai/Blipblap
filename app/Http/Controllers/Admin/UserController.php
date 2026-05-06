<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerEsim;
use App\Models\EsimOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(User $user): Response
    {
        $orders = EsimOrder::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->latest()
            ->limit(25)
            ->get();

        $esims = CustomerEsim::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            })
            ->latest()
            ->limit(25)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'customer' => $user,
            'orders' => $orders,
            'esims' => $esims,
        ]);
    }
}
