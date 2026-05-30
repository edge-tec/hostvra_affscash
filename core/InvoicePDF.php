<?php
/**
 * InvoicePDF — Pure-PHP PDF generator for affiliate invoices.
 * No external dependencies. Uses PDF 1.4 with built-in Helvetica fonts.
 *
 * Coordinate system: (x, y) given as points from TOP-LEFT of page.
 * A4 page: 595.28 × 841.89 pts.
 */
class InvoicePDF
{
    private string $buf    = '';
    private array  $xref   = [];
    private string $stream = '';   // content stream accumulator

    private float $W = 595.28;
    private float $H = 841.89;

    // Current text state
    private string $cf = 'F1';   // F1=Regular, F2=Bold, F3=Italic
    private float  $cs = 10;
    private array  $tc = [0, 0, 0];  // text color RGB 0-255

    // Helvetica glyph widths in 1/1000 units (regular; bold ≈ +5%)
    private static array $GW = [
        ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,
        "''"=>191,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,
        '.'=>278,'/'=>278,'0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,
        '5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>278,';'=>278,
        '<'=>584,'='=>584,'>'=>584,'?'=>556,'@'=>1015,'A'=>667,'B'=>667,
        'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,
        'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,'P'=>667,
        'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,
        'X'=>667,'Y'=>667,'Z'=>611,'['=>278,'\\'=>278,']'=>278,'^'=>469,
        '_'=>556,'`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,
        'f'=>278,'g'=>556,'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,
        'm'=>833,'n'=>556,'o'=>556,'p'=>556,'q'=>556,'r'=>333,'s'=>500,
        't'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>500,
        '{'=>334,'|'=>260,'}'=>334,'~'=>584,
    ];

    // ── Buffer helpers ────────────────────────────────────────────────────────

    private function w(string $s): void  { $this->buf .= $s; }
    private function wl(string $s): void { $this->buf .= $s . "\n"; }

    private function startObj(int $n): void
    {
        $this->xref[$n] = strlen($this->buf);
        $this->wl("{$n} 0 obj");
    }
    private function endObj(): void { $this->wl("endobj"); }

    private static function esc(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $s);
    }

    /** Convert y-from-top to PDF y-from-bottom */
    private function py(float $y): float { return $this->H - $y; }

    // ── Text encoding ─────────────────────────────────────────────────────────

    private function encode(string $s): string
    {
        if (function_exists('iconv')) {
            $r = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            if ($r !== false) return $r;
        }
        // Fallback: strip non-ASCII above 255
        return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
    }

    // ── Drawing state ─────────────────────────────────────────────────────────

    public function setFont(string $style, float $size): self
    {
        $this->cf = match (strtoupper($style)) {
            'B'  => 'F2',
            'I'  => 'F3',
            default => 'F1',
        };
        $this->cs = $size;
        return $this;
    }

    public function setTextColor(int $r, int $g, int $b): self
    {
        $this->tc = [$r, $g, $b];
        return $this;
    }

    // ── Measurement ───────────────────────────────────────────────────────────

    public function textWidth(string $text, ?float $size = null, ?string $style = null): float
    {
        $sz  = $size  ?? $this->cs;
        $fnt = $style ?? $this->cf;
        $enc = $this->encode($text);
        $w   = 0;
        $len = strlen($enc);
        for ($i = 0; $i < $len; $i++) {
            $w += self::$GW[$enc[$i]] ?? 556;
        }
        $bold = ($fnt === 'F2') ? 1.05 : 1.0;
        return $w * $sz * $bold / 1000;
    }

    public function wrapText(string $text, float $maxW): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $cur   = '';
        foreach ($words as $word) {
            $test = $cur === '' ? $word : "$cur $word";
            if ($this->textWidth($test) <= $maxW) {
                $cur = $test;
            } else {
                if ($cur !== '') $lines[] = $cur;
                $cur = $word;
            }
        }
        if ($cur !== '') $lines[] = $cur;
        return $lines ?: [''];
    }

    // ── Drawing commands (append to content stream) ───────────────────────────

    /** Draw text at absolute (x, y-from-top). y is the text baseline. */
    public function text(float $x, float $y, string $text): self
    {
        $enc = $this->encode($text);
        $esc = self::esc($enc);
        $py  = $this->py($y);
        [$r, $g, $b] = $this->tc;
        $tc  = sprintf('%.4f %.4f %.4f', $r / 255, $g / 255, $b / 255);
        $this->stream .= "BT /{$this->cf} {$this->cs} Tf {$tc} rg {$x} {$py} Td ({$esc}) Tj ET\n";
        return $this;
    }

    /** Right-align text so its right edge is at xRight. */
    public function textRight(float $xRight, float $y, string $text): self
    {
        $tw = $this->textWidth($text);
        return $this->text($xRight - $tw, $y, $text);
    }

    /** Center text within [x1..x2]. */
    public function textCenter(float $x1, float $x2, float $y, string $text): self
    {
        $tw = $this->textWidth($text);
        return $this->text($x1 + ($x2 - $x1 - $tw) / 2, $y, $text);
    }

    /** Filled rectangle. x,y = top-left, going right+down. */
    public function rectFill(float $x, float $y, float $w, float $h, int $r, int $g, int $b): self
    {
        $py = $this->py($y) - $h;
        $this->stream .= sprintf(
            "%.4f %.4f %.4f rg %.2f %.2f %.2f %.2f re f\n",
            $r / 255, $g / 255, $b / 255, $x, $py, $w, $h
        );
        return $this;
    }

    /** Horizontal line. */
    public function hLine(float $x1, float $y, float $x2, float $lw = 0.5, int $r = 200, int $g = 200, int $b = 200): self
    {
        $py = $this->py($y);
        $this->stream .= sprintf(
            "%.2f w %.4f %.4f %.4f RG %.2f %.2f m %.2f %.2f l S\n",
            $lw, $r / 255, $g / 255, $b / 255, $x1, $py, $x2, $py
        );
        return $this;
    }

    // ── Invoice builder ───────────────────────────────────────────────────────

    /**
     * Build a professional invoice layout.
     *
     * @param array  $inv    Invoice DB row (invoice_number, status, created_at, due_date,
     *                       period_start, period_end, subtotal, tax_rate, tax_amount,
     *                       total, notes, paid_at)
     * @param array  $items  Line items [{description, qty, rate, amount}]
     * @param string $entityName   Bill-to name
     * @param string $entityEmail  Bill-to email
     * @param string $appName      Company/site name
     * @param string $appUrl       Company URL
     */
    public function buildInvoice(
        array  $inv,
        array  $items,
        string $entityName,
        string $entityEmail,
        string $appName,
        string $appUrl,
        string $appAddress    = '',
        string $paymentMethod = '',
        string $paymentDetails= '',
        string $appPhone      = ''
    ): void {
        $L = 40;    // left margin
        $R = 555;   // right margin
        $CW = $R - $L; // content width = 515

        // ── Header bar ───────────────────────────────────────────────────────
        $this->rectFill(0, 0, $this->W, 90, 67, 56, 202); // #4338CA indigo-700

        // App name
        $this->setFont('B', 20)->setTextColor(255, 255, 255)
             ->text($L, 30, $appName);

        // App URL
        $this->setFont('', 9)->setTextColor(196, 181, 253)
             ->text($L, 50, $appUrl);

        // Company address (if set)
        if ($appAddress) {
            $this->setFont('', 8)->setTextColor(196, 181, 253)
                 ->text($L, 64, $appAddress);
        }
        // Company phone (if set)
        if ($appPhone) {
            $phoneY = $appAddress ? 74 : 64;
            $this->setFont('', 8)->setTextColor(196, 181, 253)
                 ->text($L, $phoneY, 'Tel: ' . $appPhone);
        }

        // "INVOICE" right-aligned
        $this->setFont('B', 26)->setTextColor(255, 255, 255)
             ->textRight($R, 34, 'INVOICE');

        // Invoice number
        $this->setFont('', 10)->setTextColor(196, 181, 253)
             ->textRight($R, 52, $inv['invoice_number']);

        // Status badge
        $statusColors = [
            'draft' => [148, 163, 184],
            'sent'  => [59, 130, 246],
            'paid'  => [16, 185, 129],
            'void'  => [239, 68, 68],
        ];
        [$sr, $sg, $sb] = $statusColors[$inv['status']] ?? [148, 163, 184];
        $statusText = strtoupper($inv['status']);
        $this->setFont('B', 8)->setTextColor(255, 255, 255);
        $bw = $this->textWidth($statusText) + 18;
        $this->rectFill($R - $bw, 59, $bw, 15, $sr, $sg, $sb)
             ->text($R - $bw + 9, 70, $statusText);

        // ── Info section ─────────────────────────────────────────────────────
        $y = 108;

        // Labels row
        $this->setFont('B', 7.5)->setTextColor(148, 163, 184);
        $this->text($L, $y, 'BILL TO');
        $this->textRight($R, $y, 'INVOICE DATE');

        $y += 14;

        // Entity name + invoice date
        $this->setFont('B', 12)->setTextColor(30, 41, 59)
             ->text($L, $y, $entityName);
        $this->setFont('', 11)->setTextColor(30, 41, 59)
             ->textRight($R, $y, date('M j, Y', strtotime($inv['created_at'])));

        $y += 14;

        // Email
        $this->setFont('', 10)->setTextColor(100, 116, 139)
             ->text($L, $y, $entityEmail);

        // Due date
        if (!empty($inv['due_date'])) {
            $this->setFont('B', 7.5)->setTextColor(148, 163, 184)
                 ->textRight($R, $y - 2, 'DUE DATE');
            $y += 13;
            $this->setFont('', 11)->setTextColor(30, 41, 59)
                 ->textRight($R, $y, date('M j, Y', strtotime($inv['due_date'])));
        } else {
            $y += 13;
        }

        $y += 14;

        // Period
        if (!empty($inv['period_start'])) {
            $period = date('M j, Y', strtotime($inv['period_start']))
                    . ' \x96 '   // en-dash in CP1252
                    . date('M j, Y', strtotime($inv['period_end']));
            // Use ASCII dash for safety:
            $period = date('M j, Y', strtotime($inv['period_start']))
                    . ' - '
                    . date('M j, Y', strtotime($inv['period_end']));
            $this->setFont('', 9.5)->setTextColor(100, 116, 139)
                 ->text($L, $y, 'Period: ' . $period);

            $this->setFont('B', 7.5)->setTextColor(148, 163, 184)
                 ->textRight($R, $y - 2, 'INVOICE #');
            $y += 13;
            $this->setFont('B', 10)->setTextColor(79, 70, 229)
                 ->textRight($R, $y, $inv['invoice_number']);
        }

        $y += 22;

        // ── Affiliate payment details ─────────────────────────────────────────
        if ($paymentMethod || $paymentDetails) {
            $this->hLine($L, $y, $R, 0.5, 226, 232, 240);
            $y += 14;
            $this->setFont('B', 8)->setTextColor(100, 116, 139)
                 ->text($L, $y, 'PAYMENT DETAILS');
            $y += 13;
            if ($paymentMethod) {
                $this->setFont('B', 9.5)->setTextColor(30, 41, 59)
                     ->text($L, $y, $paymentMethod . ':');
                $y += 13;
            }
            if ($paymentDetails) {
                $this->setFont('', 9.5)->setTextColor(71, 85, 105);
                foreach ($this->wrapText(trim($paymentDetails), $CW) as $pline) {
                    $this->text($L, $y, $pline);
                    $y += 13;
                }
            }
            $y += 6;
        }

        // Separator
        $this->hLine($L, $y, $R, 0.5, 226, 232, 240);
        $y += 18;

        // ── Line items table ─────────────────────────────────────────────────
        // Header row
        $this->rectFill($L, $y, $CW, 22, 248, 250, 252);
        $this->setFont('B', 8.5)->setTextColor(100, 116, 139);
        $this->text($L + 8, $y + 14, 'DESCRIPTION');
        $this->textRight(330, $y + 14, 'QTY');
        $this->textRight(430, $y + 14, 'RATE');
        $this->textRight($R, $y + 14, 'AMOUNT');
        $y += 22;

        $this->hLine($L, $y, $R, 1.0, 226, 232, 240);

        // Item rows
        $rowH = 22;
        foreach ($items as $idx => $item) {
            $desc = $item['description'] ?? '';
            // Truncate long descriptions
            $maxDescW = 515 - (515 - 270); // description column width ~270
            while (strlen($desc) > 3 && $this->textWidth($desc, 10) > 270) {
                $desc = substr($desc, 0, -4) . '...';
            }

            if ($idx % 2 === 1) {
                $this->rectFill($L, $y, $CW, $rowH, 249, 250, 251);
            }

            $this->setFont('', 10)->setTextColor(30, 41, 59)
                 ->text($L + 8, $y + 15, $desc);

            $qty = is_numeric($item['qty']) ? number_format((float)$item['qty'], 0) : $item['qty'];
            $this->setFont('', 10)->textRight(330, $y + 15, $qty);
            $this->setFont('', 10)->textRight(430, $y + 15, '$' . number_format((float)($item['rate'] ?? 0), 2));
            $this->setFont('B', 10)->setTextColor(30, 41, 59)
                 ->textRight($R, $y + 15, '$' . number_format((float)($item['amount'] ?? 0), 2));

            $y += $rowH;
            $this->hLine($L, $y, $R, 0.3, 241, 245, 249);
        }

        $y += 16;

        // ── Totals ───────────────────────────────────────────────────────────
        $tX = 360; // totals left label x

        $this->setFont('', 10)->setTextColor(100, 116, 139)
             ->text($tX, $y, 'Subtotal:');
        $this->setFont('', 10)->setTextColor(30, 41, 59)
             ->textRight($R, $y, '$' . number_format((float)$inv['subtotal'], 2));
        $y += 18;

        if ((float)($inv['tax_rate'] ?? 0) > 0) {
            $this->setFont('', 10)->setTextColor(100, 116, 139)
                 ->text($tX, $y, 'Tax (' . $inv['tax_rate'] . '%):');
            $this->setFont('', 10)->setTextColor(30, 41, 59)
                 ->textRight($R, $y, '$' . number_format((float)$inv['tax_amount'], 2));
            $y += 18;
        }

        $this->hLine($tX, $y, $R, 1.0, 226, 232, 240);
        $y += 8;

        // Total highlight box
        $this->rectFill($tX - 8, $y - 2, $R - $tX + 18, 26, 238, 242, 255);
        $this->setFont('B', 12)->setTextColor(67, 56, 202)
             ->text($tX, $y + 17, 'TOTAL:');
        $this->setFont('B', 14)->setTextColor(67, 56, 202)
             ->textRight($R, $y + 17, '$' . number_format((float)$inv['total'], 2));
        $y += 36;

        // ── Notes ────────────────────────────────────────────────────────────
        if (!empty(trim($inv['notes'] ?? ''))) {
            $this->hLine($L, $y, $R, 0.5, 226, 232, 240);
            $y += 14;
            $this->setFont('B', 8.5)->setTextColor(100, 116, 139)
                 ->text($L, $y, 'NOTES');
            $y += 13;
            $this->setFont('I', 10)->setTextColor(71, 85, 105);
            foreach ($this->wrapText(trim($inv['notes']), $CW) as $line) {
                $this->text($L, $y, $line);
                $y += 14;
            }
        }

        // ── Footer ───────────────────────────────────────────────────────────
        $fy = 824;
        $this->hLine($L, $fy, $R, 0.5, 226, 232, 240);
        $fy += 11;

        $this->setFont('', 7.5)->setTextColor(148, 163, 184)
             ->text($L, $fy, 'Generated by ' . $appName . ' - ' . $appUrl);

        if (!empty($inv['paid_at'])) {
            $this->setFont('B', 8)->setTextColor(16, 185, 129)
                 ->textRight($R, $fy, 'PAID ON ' . date('M j, Y', strtotime($inv['paid_at'])));
        }
    }

    // ── PDF assembly ──────────────────────────────────────────────────────────

    public function output(): string
    {
        $this->buf  = '';
        $this->xref = [];

        $stream = $this->stream;
        if ($stream !== '' && substr($stream, -1) !== "\n") {
            $stream .= "\n";
        }
        $slen = strlen($stream);
        $W    = round($this->W, 2);
        $H    = round($this->H, 2);

        // Header
        $this->wl('%PDF-1.4');
        $this->w('%' . chr(226) . chr(227) . chr(207) . chr(211) . "\n"); // binary marker

        // 1: Catalog
        $this->startObj(1);
        $this->wl('<< /Type /Catalog /Pages 2 0 R >>');
        $this->endObj();

        // 2: Pages
        $this->startObj(2);
        $this->wl('<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
        $this->endObj();

        // 3: Page
        $this->startObj(3);
        $this->wl("<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$W} {$H}]");
        $this->wl('/Contents 4 0 R');
        $this->wl('/Resources << /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R >> >>');
        $this->wl('>>');
        $this->endObj();

        // 4: Content stream
        $this->startObj(4);
        $this->wl("<< /Length {$slen} >>");
        $this->wl('stream');
        $this->w($stream);
        $this->wl('endstream');
        $this->endObj();

        // 5: Helvetica regular
        $this->startObj(5);
        $this->wl('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->endObj();

        // 6: Helvetica bold
        $this->startObj(6);
        $this->wl('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
        $this->endObj();

        // 7: Helvetica oblique (italic)
        $this->startObj(7);
        $this->wl('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>');
        $this->endObj();

        // XRef
        $xrefPos = strlen($this->buf);
        $this->wl('xref');
        $this->wl('0 8');
        $this->buf .= sprintf("%010d %05d f\r\n", 0, 65535);
        for ($i = 1; $i <= 7; $i++) {
            $this->buf .= sprintf("%010d %05d n\r\n", $this->xref[$i], 0);
        }

        $this->wl('trailer');
        $this->wl('<< /Size 8 /Root 1 0 R >>');
        $this->wl('startxref');
        $this->wl((string) $xrefPos);
        $this->w('%%EOF');

        return $this->buf;
    }

    // ── Static factory ────────────────────────────────────────────────────────

    /**
     * Generate and save PDF for an invoice. Returns the saved file path.
     *
     * @param array  $inv      Invoice row from DB
     * @param array  $items    Decoded line items
     * @param string $entityName
     * @param string $entityEmail
     * @return string  Absolute path to the saved PDF file
     */
    public static function generate(
        array  $inv,
        array  $items,
        string $entityName,
        string $entityEmail,
        string $paymentMethod  = '',
        string $paymentDetails = ''
    ): string {
        $dir = BASE_PATH . '/uploads/invoices';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $appName    = Config::get('config', 'app.name')    ?? 'AffiliateTracker';
        $appUrl     = rtrim(Config::get('config', 'app.url') ?? '', '/');
        $appAddress = Config::get('config', 'app.address') ?? '';

        $pdf = new self();
        $appPhone   = Config::get('config', 'app.phone') ?? '';
        $pdf->buildInvoice($inv, $items, $entityName, $entityEmail, $appName, $appUrl, $appAddress, $paymentMethod, $paymentDetails, $appPhone);

        $path = $dir . '/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $inv['invoice_number']) . '.pdf';
        file_put_contents($path, $pdf->output());

        return $path;
    }
}
