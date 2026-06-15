<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use App\Models\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Content/Index', [
            'homeContent' => SiteContent::value('homepage', $this->defaultHomeContent()),
            'emailContent' => SiteContent::value('emails.esim_ready', $this->defaultEmailContent()),
            'pages' => ContentPage::safeLatest(),
        ]);
    }

    public function updateHomepage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_eyebrow' => ['required', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_text' => ['required', 'string'],
            'hero_cta_label' => ['required', 'string', 'max:120'],
            'hero_cta_url' => ['required', 'string', 'max:255'],
            'hero_image_url' => ['nullable', 'string', 'max:500'],
            'trust_heading' => ['required', 'string', 'max:255'],
            'trust_items' => ['required', 'array', 'min:1'],
            'trust_items.*.title' => ['required', 'string', 'max:120'],
            'trust_items.*.text' => ['required', 'string', 'max:255'],
            'trust_items.*.image' => ['nullable', 'string', 'max:500'],
            'faq_heading' => ['required', 'string', 'max:255'],
            'faq_intro' => ['required', 'string', 'max:255'],
            'faqs' => ['required', 'array', 'min:1'],
            'faqs.*.question' => ['required', 'string', 'max:255'],
            'faqs.*.answer' => ['required', 'string'],
            'promo_banners' => ['nullable', 'array'],
            'promo_banners.*.title' => ['nullable', 'string', 'max:120'],
            'promo_banners.*.text' => ['nullable', 'string', 'max:255'],
            'promo_banners.*.cta_label' => ['nullable', 'string', 'max:120'],
            'promo_banners.*.cta_url' => ['nullable', 'string', 'max:255'],
        ]);

        if (! SiteContent::storeValue('homepage', 'Homepage content', $data)) {
            return back()->with('error', 'Homepage content table is not available yet. Please run migrations.');
        }

        return back()->with('status', 'Homepage content updated.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'heading' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['required', 'string', 'max:255'],
            'manual_heading' => ['required', 'string', 'max:255'],
            'manual_intro' => ['required', 'string'],
            'footer' => ['required', 'string'],
            'ios_label' => ['required', 'string', 'max:120'],
            'android_label' => ['required', 'string', 'max:120'],
        ]);

        if (! SiteContent::storeValue('emails.esim_ready', 'Ready email template', $data)) {
            return back()->with('error', 'Email content table is not available yet. Please run migrations.');
        }

        return back()->with('status', 'Ready email content updated.');
    }

    public function storePage(Request $request): RedirectResponse
    {
        if (! ContentPage::tableAvailable()) {
            return back()->with('error', 'Content pages table is not available yet. Please run migrations.');
        }

        $data = $this->validatePage($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        ContentPage::query()->create($data);

        return back()->with('status', 'Page created.');
    }

    public function updatePage(Request $request, ContentPage $page): RedirectResponse
    {
        if (! ContentPage::tableAvailable()) {
            return back()->with('error', 'Content pages table is not available yet. Please run migrations.');
        }

        $data = $this->validatePage($request, $page->id);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $page->update($data);

        return back()->with('status', 'Page updated.');
    }

    public function destroyPage(ContentPage $page): RedirectResponse
    {
        if (! ContentPage::tableAvailable()) {
            return back()->with('error', 'Content pages table is not available yet. Please run migrations.');
        }

        $page->delete();

        return back()->with('status', 'Page deleted.');
    }

    private function validatePage(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'slug' => ['nullable', 'string', 'max:255', 'unique:content_pages,slug,' . ($ignoreId ?? 'NULL') . ',id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body_html' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'is_published' => ['required', 'boolean'],
        ]);
    }

    private function defaultHomeContent(): array
    {
        return [
            'hero_eyebrow' => 'Complete eSIM Connectivity Platform',
            'hero_title' => 'Blip Blap Fast, Reliable ESIM',
            'hero_text' => 'Instant Canada & Global eSIM plans, activate in seconds with zero hassle. Connect across 190+ countries with affordable data and 24/7 support.',
            'hero_cta_label' => 'Explore eSIM Plans',
            'hero_cta_url' => '/esim-plans',
            'hero_image_url' => '/images/blipblap/trust-icon.webp',
            'trust_heading' => 'Why Travelers Worldwide Trust Blip Blap ESIM',
            'trust_items' => [
                ['title' => 'GLOBAL COVERAGE', 'text' => 'Stay connected across Canada, USA & 190 worldwide destinations.', 'image' => '/images/blipblap/Group-22-300x259.png'],
                ['title' => 'INSTANT ACTIVATION', 'text' => 'No physical SIM, just scan the QR and start.', 'image' => '/images/blipblap/ChatGPT-Image-Jan-8-2026-07_40_45-PM-02-300x300.png'],
                ['title' => 'AFFORDABLE & TRANSPARENT', 'text' => 'No hidden fees, no roaming surprises.', 'image' => '/images/blipblap/trust-transparent.svg'],
                ['title' => '24/7 SUPPORT', 'text' => 'We are here wherever you need help.', 'image' => '/images/blipblap/trust-support-24-7.svg'],
            ],
            'promo_banners' => [],
            'faq_heading' => 'Our FAQs Are A Great Place To Find Answers Quickly.',
            'faq_intro' => 'A compilation of questions and answers that will help you decide.',
            'faqs' => [
                ['question' => 'How do I activate my eSIM?', 'answer' => 'Scan the QR and install instantly from your BlipBlap account.'],
                ['question' => 'Which devices are supported?', 'answer' => 'Most modern iPhone and Android devices with eSIM support will work.'],
                ['question' => 'When should I install my eSIM?', 'answer' => 'Install it before your trip, then turn on data when you reach your destination.'],
                ['question' => 'How do loyalty rewards work?', 'answer' => 'Earn points on eligible purchases and referrals, then redeem them for rewards.'],
            ],
        ];
    }

    private function defaultEmailContent(): array
    {
        return [
            'subject' => 'Your BlipBlap eSIM is ready to install',
            'heading' => 'Your eSIM is ready to install',
            'intro' => 'Thanks for choosing BlipBlap. Your travel data plan is ready. Scan the QR code below and follow the steps to connect.',
            'steps' => [
                'Connect your phone to Wi-Fi before installing.',
                'Open your phone camera or go to cellular/mobile data settings and choose add eSIM.',
                'Scan the QR code in this email.',
                'Follow the phone prompts, then name the line BlipBlap if asked.',
                'When you reach your destination, turn on this eSIM for mobile data and enable data roaming for the BlipBlap line.',
            ],
            'manual_heading' => 'Manual install details',
            'manual_intro' => 'Use these only if your phone asks for manual eSIM setup instead of QR scanning.',
            'footer' => 'Keep this email safe until your trip is complete. If installation is interrupted, open your BlipBlap account and check the same install details there.',
            'ios_label' => 'Open Apple install link',
            'android_label' => 'Open Android install link',
        ];
    }
}
