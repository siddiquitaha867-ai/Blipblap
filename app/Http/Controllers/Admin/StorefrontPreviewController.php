<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontPreviewController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Storefront/Home', [
            'featuredDestinations' => config('blipblap.featured_destinations'),
            'storefrontPreview' => true,
        ]);
    }
}
