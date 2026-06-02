<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function show(ContentPage $page): Response
    {
        abort_unless($page->is_published, 404);

        return Inertia::render('Storefront/ContentPage', [
            'page' => $page,
        ]);
    }
}
