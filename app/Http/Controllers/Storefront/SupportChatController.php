<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SupportChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $message = trim((string) $data['message']);
        $knowledge = $this->knowledgeBase();
        $reply = $this->matchReply($message, $knowledge);

        return response()->json($reply);
    }

    private function knowledgeBase(): Collection
    {
        $homepage = SiteContent::value('homepage', []);
        $email = SiteContent::value('emails.esim_ready', []);

        $entries = collect($homepage['faqs'] ?? [])
            ->map(fn (array $item): array => [
                'question' => (string) ($item['question'] ?? ''),
                'answer' => (string) ($item['answer'] ?? ''),
                'keywords' => $this->keywordsFromText(($item['question'] ?? '') . ' ' . ($item['answer'] ?? '')),
                'category' => 'faq',
            ])
            ->filter(fn (array $item): bool => $item['question'] !== '' && $item['answer'] !== '');

        $trustEntries = collect($homepage['trust_items'] ?? [])
            ->map(fn (array $item): array => [
                'question' => (string) ($item['title'] ?? 'Why choose BlipBlap?'),
                'answer' => (string) ($item['text'] ?? ''),
                'keywords' => $this->keywordsFromText(($item['title'] ?? '') . ' ' . ($item['text'] ?? '')),
                'category' => 'trust',
            ])
            ->filter(fn (array $item): bool => $item['answer'] !== '');

        $supportEntries = collect([
            [
                'question' => 'How do I install my eSIM?',
                'answer' => $this->installAnswer($email),
                'keywords' => ['install', 'installation', 'setup', 'activate', 'activation', 'qr', 'scan', 'apple', 'android', 'ios'],
                'category' => 'install',
            ],
            [
                'question' => 'Where do I find my purchased eSIMs?',
                'answer' => 'Open My eSIMs after you log in. You can view your purchased eSIMs, install details, QR code, and supported usage details there.',
                'keywords' => ['my esim', 'my esims', 'purchased', 'purchase', 'find esim', 'order', 'orders', 'account'],
                'category' => 'account',
            ],
            [
                'question' => 'How do checkout and payment work?',
                'answer' => 'Choose a plan, continue to checkout, enter your billing details, and complete payment. After a successful order, your eSIM details and install steps appear on the success screen and are also emailed to you.',
                'keywords' => ['checkout', 'payment', 'pay', 'card', 'billing', 'purchase', 'buy'],
                'category' => 'checkout',
            ],
            [
                'question' => 'Can I check remaining data or days?',
                'answer' => 'Yes. When provider usage data is available, remaining data and remaining days can appear in My eSIMs. If your latest usage is not visible yet, refresh the page after a short wait.',
                'keywords' => ['remaining', 'usage', 'days left', 'data left', 'balance', 'how much data', 'how many days'],
                'category' => 'usage',
            ],
            [
                'question' => 'What if I need more help?',
                'answer' => 'If your question is not answered here, please use the Contact Us section on the site and include your order email plus destination so support can help faster.',
                'keywords' => ['help', 'support', 'contact', 'agent', 'human', 'issue', 'problem'],
                'category' => 'fallback',
            ],
        ]);

        return $entries
            ->concat($trustEntries)
            ->concat($supportEntries)
            ->values();
    }

    private function matchReply(string $message, Collection $knowledge): array
    {
        $normalized = Str::lower(preg_replace('/\s+/', ' ', $message) ?? $message);
        $tokens = $this->keywordsFromText($normalized);

        $best = $knowledge
            ->map(function (array $entry) use ($normalized, $tokens): array {
                $score = 0;

                foreach ($entry['keywords'] as $keyword) {
                    $keyword = Str::lower($keyword);

                    if ($keyword !== '' && str_contains($normalized, $keyword)) {
                        $score += max(3, strlen($keyword) > 8 ? 5 : 4);
                    }
                }

                foreach ($tokens as $token) {
                    if (in_array($token, $entry['keywords'], true)) {
                        $score += 2;
                    }
                }

                similar_text($normalized, Str::lower($entry['question']), $questionPercent);
                $score += (int) round($questionPercent / 20);

                return [
                    ...$entry,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->first();

        if (! $best || ($best['score'] ?? 0) < 3) {
            return [
                'answer' => 'I can help with plans, checkout, installation, My eSIMs, and common support questions. Try asking about installation, QR code setup, payment, remaining data, or where to find your purchased eSIM.',
                'suggestions' => [
                    'How do I install my eSIM?',
                    'Where can I find My eSIMs?',
                    'How does checkout work?',
                ],
            ];
        }

        return [
            'answer' => $best['answer'],
            'suggestions' => $this->suggestionsForCategory((string) $best['category']),
        ];
    }

    private function installAnswer(array $email): string
    {
        $steps = collect($email['steps'] ?? [])
            ->filter(fn ($step): bool => is_string($step) && trim($step) !== '')
            ->values();

        if ($steps->isEmpty()) {
            return 'After purchase, open your eSIM details, scan the QR code, and follow your phone prompts to install the line. Keep Wi-Fi on during setup, then enable the eSIM for data when you travel.';
        }

        return 'After purchase, open your eSIM details and follow these steps: ' . $steps->take(3)->implode(' ');
    }

    private function keywordsFromText(string $text): array
    {
        return collect(preg_split('/[^a-z0-9]+/i', Str::lower($text)) ?: [])
            ->filter(fn (string $token): bool => strlen($token) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    private function suggestionsForCategory(string $category): array
    {
        return match ($category) {
            'install' => [
                'Where is my QR code?',
                'Do you support iPhone?',
                'How do I install on Android?',
            ],
            'account' => [
                'How do I open My eSIMs?',
                'Can I see remaining data?',
                'Where is my order email?',
            ],
            'checkout' => [
                'What happens after payment?',
                'How do I buy a plan?',
                'Can I checkout without issues?',
            ],
            default => [
                'Show me popular eSIM plans',
                'How does installation work?',
                'Where can I get more help?',
            ],
        };
    }
}
