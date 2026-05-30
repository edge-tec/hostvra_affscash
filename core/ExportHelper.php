<?php
/**
 * ExportHelper — minimal CSV / XLS export utility.
 *
 * For Excel (.xls) output we use HTML-table content with the Microsoft
 * application/vnd.ms-excel mime type. Excel and Google Sheets both open this
 * format reliably without any external library, while preserving column
 * separation and basic formatting (text vs number).
 *
 * Usage:
 *   ExportHelper::beginXls('conversions');     // sends headers, opens HTML table
 *   ExportHelper::xlsHeaderRow(['Col 1','Col 2']);
 *   foreach ($rows as $r) ExportHelper::xlsRow([$r['a'], $r['b']]);
 *   ExportHelper::endXls(); exit;
 */
final class ExportHelper
{
    public static function beginXls(string $filenameStem): void
    {
        $fname = preg_replace('/[^a-z0-9_-]+/i', '_', $filenameStem) . '_' . date('Y-m-d') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Pragma: no-cache');
        // UTF-8 BOM + minimal HTML so Excel reads UTF-8 correctly.
        echo "\xEF\xBB\xBF";
        echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">";
        echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\"/>";
        echo "<style>th{background:#f1f5f9;font-weight:bold;text-align:left;border:1px solid #cbd5e1;padding:4px 6px}td{border:1px solid #e2e8f0;padding:4px 6px}</style>";
        echo "</head><body><table>";
    }

    public static function xlsHeaderRow(array $cells): void
    {
        echo '<tr>';
        foreach ($cells as $c) echo '<th>' . htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') . '</th>';
        echo '</tr>';
    }

    public static function xlsRow(array $cells): void
    {
        echo '<tr>';
        foreach ($cells as $c) {
            // Force text where the value is a leading-zero ID or an obvious string
            // so Excel doesn't strip leading zeros or convert long IDs to scientific notation.
            $val = (string)$c;
            $isNumeric = is_numeric($val) && $val !== '' && strlen($val) < 16
                && !preg_match('/^0\d/', $val);  // 0123 → text
            if ($isNumeric) {
                echo '<td>' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</td>';
            } else {
                // mso-number-format:'\@' tells Excel to render as text verbatim.
                echo '<td style="mso-number-format:\'\@\'">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</td>';
            }
        }
        echo '</tr>';
    }

    public static function endXls(): void
    {
        echo '</table></body></html>';
    }

    /** Convenience: dump rows[] of associative arrays with given column map. */
    public static function dumpXls(string $filenameStem, array $headers, array $rows, array $columnKeys): void
    {
        self::beginXls($filenameStem);
        self::xlsHeaderRow($headers);
        foreach ($rows as $r) {
            $cells = [];
            foreach ($columnKeys as $k) $cells[] = $r[$k] ?? '';
            self::xlsRow($cells);
        }
        self::endXls();
    }
}
