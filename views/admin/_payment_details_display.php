<?php
/**
 * Shared helper: format payment details for read-only display.
 *
 * Detects whether $details is a JSON object (structured fields) or
 * plain legacy text and renders accordingly. Empty fields are hidden.
 *
 * Usage:
 *   echo formatPaymentDetailsHtml($methodName, $details);
 */
if (!function_exists('formatPaymentDetailsHtml')) {
    function formatPaymentDetailsHtml(string $method, string $details): string
    {
        $details = trim($details);
        if ($details === '' && $method === '') return '';

        // Label maps for known structured types
        $labelMaps = [
            // payoneer / paypal / wise — 2-field simple
            'account_holder_name' => 'Account Holder Name',
            'email'               => 'Email / Account ID',
            // wire transfer
            'bank_name'           => 'Bank Name',
            'account_number'      => 'Account Number',
            'iban_swift'          => 'IBAN / SWIFT Code',
            'routing_number'      => 'Routing Number',
            'branch_name'         => 'Branch Name',
            'bank_address'        => 'Bank Address',
            // crypto
            'crypto_type'         => 'Cryptocurrency',
            'network_type'        => 'Network',
            'wallet_address'      => 'Wallet Address',
        ];

        // Detect JSON
        $parsed = null;
        if ($details !== '' && $details[0] === '{') {
            $decoded = json_decode($details, true);
            if (is_array($decoded)) {
                $parsed = $decoded;
            }
        }

        $html = '';

        if ($parsed !== null) {
            // Structured JSON — render each non-empty field as a labelled row
            $rows = '';
            foreach ($parsed as $key => $val) {
                $val = trim((string)$val);
                if ($val === '') continue;
                $label = $labelMaps[$key] ?? ucwords(str_replace('_', ' ', $key));

                // Wallet address: monospace
                $valueStyle = ($key === 'wallet_address')
                    ? 'font-family:monospace;font-size:12px;word-break:break-all'
                    : 'font-size:13px;font-weight:600;color:#1E293B';

                $rows .= '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #F1F5F9;align-items:flex-start">'
                       . '<span style="font-size:11px;color:#64748B;min-width:130px;flex-shrink:0;padding-top:1px">' . htmlspecialchars($label) . '</span>'
                       . '<span style="' . $valueStyle . '">' . htmlspecialchars($val) . '</span>'
                       . '</div>';
            }

            if ($rows === '') {
                // JSON parsed but all fields empty
                $html = '<span style="color:#94A3B8;font-size:12px;font-style:italic">No details entered yet</span>';
            } else {
                $html = '<div style="display:flex;flex-direction:column">' . $rows . '</div>';
            }
        } else {
            // Plain text (legacy) — preserve line breaks, escape HTML
            if ($details === '') {
                $html = '<span style="color:#94A3B8;font-size:12px;font-style:italic">No details entered yet</span>';
            } else {
                $html = '<div style="font-size:13px;color:#475569;white-space:pre-wrap">'
                      . htmlspecialchars($details)
                      . '</div>';
            }
        }

        return $html;
    }
}
