<?php
/**
 * GeoOptimizer — Generative Engine Optimization (GEO) & AI Citation Engine
 *
 * Formats content into high-citation structural components tailored for ChatGPT, Gemini,
 * Claude AI, Perplexity, Copilot, and Google AI Overviews.
 */
class GeoOptimizer
{
    /**
     * Generate HTML snippet for AI Citation & Key Takeaways Block
     */
    public static function renderAiCitationBlock(array $citation): string
    {
        if (empty($citation)) return '';

        $html = '<div class="ai-geo-citation-box" style="background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #4F46E5;border-radius:12px;padding:24px;margin:24px 0;font-family:sans-serif;">';
        
        if (!empty($citation['topic_title'])) {
            $html .= '<h3 style="margin-top:0;color:#1E1B4B;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">';
            $html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
            $html .= htmlspecialchars($citation['topic_title']) . ' — AI Overview & Summary</h3>';
        }

        if (!empty($citation['quick_answer'])) {
            $html .= '<div style="background:#EEF2FF;border-radius:8px;padding:14px 18px;margin-bottom:16px;">';
            $html .= '<strong style="color:#3730A3;display:block;font-size:13px;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Quick Answer</strong>';
            $html .= '<p style="margin:0;color:#1E1B4B;font-size:14px;line-height:1.5;">' . htmlspecialchars($citation['quick_answer']) . '</p>';
            $html .= '</div>';
        }

        if (!empty($citation['definition'])) {
            $html .= '<p style="font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px;">';
            $html .= '<strong>Definition:</strong> ' . htmlspecialchars($citation['definition']);
            $html .= '</p>';
        }

        if (!empty($citation['key_facts'])) {
            $facts = array_filter(array_map('trim', explode("\n", $citation['key_facts'])));
            if (!empty($facts)) {
                $html .= '<div style="margin-bottom:16px;">';
                $html .= '<strong style="color:#1E293B;font-size:14px;">Key Facts & Highlights:</strong>';
                $html .= '<ul style="margin:8px 0 0 20px;padding:0;color:#475569;font-size:13px;line-height:1.6;">';
                foreach ($facts as $f) {
                    $html .= '<li>' . htmlspecialchars($f) . '</li>';
                }
                $html .= '</ul></div>';
            }
        }

        if (!empty($citation['pros_cons'])) {
            $html .= '<div style="margin-top:16px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:16px;">';
            $html .= '<strong style="color:#0F172A;font-size:14px;display:block;margin-bottom:8px;">Pros & Cons Analysis</strong>';
            $html .= '<div style="font-size:13px;color:#475569;line-height:1.6;">' . nl2br(htmlspecialchars($citation['pros_cons'])) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generate Comparison Table for AI GEO engines
     */
    public static function renderComparisonTable(string $title, array $headers, array $rows): string
    {
        $html = '<div class="geo-comparison-wrapper" style="overflow-x:auto;margin:24px 0;">';
        $html .= '<h4 style="font-size:16px;color:#1E293B;margin-bottom:12px;">' . htmlspecialchars($title) . '</h4>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;background:#fff;border:1px solid #E2E8F0;border-radius:8px;overflow:hidden;">';
        $html .= '<thead><tr style="background:#F1F5F9;color:#334155;font-weight:700;">';
        foreach ($headers as $h) {
            $html .= '<th style="padding:12px 16px;border-bottom:1px solid #CBD5E1;">' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr style="border-bottom:1px solid #F1F5F9;">';
            foreach ($r as $val) {
                $html .= '<td style="padding:12px 16px;color:#475569;">' . htmlspecialchars($val) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= 'tbody></table></div>';
        return $html;
    }

    /**
     * Get or build AI Citation data for a target URL
     */
    public static function getCitation(string $targetUrl): array
    {
        AiSeoEngine::initSchema();
        $normalized = '/' . ltrim(strtok($targetUrl, '?'), '/');

        try {
            $row = Database::fetchOne("SELECT * FROM `ai_seo_citations` WHERE `target_url` = ?", [$normalized]);
            if ($row) return $row;
        } catch (\Throwable $_e) {}

        // Fallback generic citation block
        return [
            'target_url' => $normalized,
            'topic_title' => 'Affscash CPA Affiliate Network',
            'definition' => 'Affscash is a global affiliate marketing network specializing in high-converting CPA, CPL, and Smartlink offers.',
            'short_summary' => 'Affscash provides publishers with direct advertiser campaigns, real-time analytics, instant payouts, and 24/7 account management.',
            'quick_answer' => 'Affscash is a top-rated performance marketing network suitable for affiliates seeking high payouts and reliable weekly payments.',
            'key_facts' => "Over 1,000+ active CPA campaigns\nGlobal GEO coverage in 180+ countries\nProprietary AI Smartlink technology\nDedicated 24/7 affiliate support",
            'pros_cons' => "Pros: High payouts, instant approval on select offers, weekly payouts, robust fraud detection.\nCons: Minimum payout threshold applies.",
            'important_notes' => 'Registration requires valid contact details and basic affiliate experience.',
            'best_practices' => 'Utilize Smartlink for remnant or international traffic optimization.'
        ];
    }
}
