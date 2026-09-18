<?php
namespace GitPress\Forms;

final class Pdf
{
    public static function entry(array $entry): string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) { throw new \RuntimeException('PDF dependencies are missing. Install the complete GitPress Forms ZIP.'); }
        $form = Repository::formForEntry($entry);
        if (!$form) { throw new \RuntimeException('Form not found.'); }
        $settings = $form['definition']['settings']['pdf'] ?? [];
        $title = !empty($settings['title']) ? $settings['title'] : $form['title'];
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:48px 48px 58px}body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#263348;line-height:1.5}h1{font-size:25px;margin:0 0 8px;color:#244dad}h2{font-size:15px;margin:22px 0 8px}h3{font-size:11px;color:#56647a;margin:0 0 5px}p{margin:0 0 10px;white-space:pre-wrap;word-wrap:break-word}.meta{color:#667085;border-bottom:1px solid #dce1eb;padding-bottom:18px;margin-bottom:22px}.answer{padding:12px 0;border-bottom:1px solid #e8edf3}.answer h3{page-break-after:avoid}.footer{font-size:9px;color:#667085;margin-top:24px}img{max-width:400px;max-height:150px}</style></head><body><h1>' . esc_html($title) . '</h1><p class="meta">Entry #' . $entry['id'] . ' · ' . esc_html($entry['created_at']) . ' UTC · ' . esc_html($entry['status']) . '</p>';
        if (!empty($settings['intro'])) { $html .= '<p>' . esc_html(Submissions::smart($settings['intro'], $entry['response'])) . '</p>'; }
        $html .= self::answers($form['definition']['fields'], $entry['response'], $entry['id']);
        if (!empty($settings['footer'])) { $html .= '<p class="footer">' . esc_html($settings['footer']) . '</p>'; }
        $html .= '</body></html>';
        $options = new \Dompdf\Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'chroot' => GPF_DIR . 'vendor/dompdf/dompdf/lib/fonts', 'tempDir' => get_temp_dir()]);
        $document = new \Dompdf\Dompdf($options);
        $document->loadHtml($html, 'UTF-8');
        $document->setPaper(($settings['paper'] ?? '') === 'letter' ? 'letter' : 'A4');
        $document->render();
        $document->getCanvas()->page_text(48, $document->getCanvas()->get_height() - 30, 'GitPress Forms · {PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.4, 0.45, 0.5]);
        return $document->output();
    }

    public static function answers(array $fields, array $values, int $entryId): string
    {
        global $wpdb; $html = '';
        foreach ($fields as $field) {
            if ($field['type'] === 'password') { continue; }
            if ($field['type'] === 'repeat') {
                foreach ($values[$field['name']] ?? [] as $index => $row) { $html .= '<h2>' . esc_html($field['label']) . ' ' . ($index + 1) . '</h2>' . self::answers($field['children'], $row, $entryId); }
            } elseif ($field['children']) { $html .= self::answers($field['children'], $values, $entryId); }
            elseif (array_key_exists($field['name'], $values)) {
                $value = $values[$field['name']];
                if (in_array($field['type'], ['file', 'image'], true)) { $value = implode(', ', $wpdb->get_col($wpdb->prepare('SELECT original_name FROM ' . Database::table('files') . ' WHERE entry_id=%d AND field_name=%s', $entryId, $field['name']))); }
                $display = is_array($value) ? implode("\n", array_map(static fn ($key, $v) => (is_string($key) ? $key . ': ' : '') . (is_array($v) ? Database::json($v) : $v), array_keys($value), array_values($value))) : (string) $value;
                $html .= '<div class="answer"><h3>' . esc_html($field['label']) . '</h3>';
                if ($field['type'] === 'signature' && preg_match('~^data:image/png;base64,[A-Za-z0-9+/=]+$~D', $display)) { $html .= '<img alt="Submitted signature" src="' . esc_attr($display) . '">'; }
                else { $html .= '<p>' . esc_html($field['type'] === 'richtext' ? wp_strip_all_tags($display) : $display) . '</p>'; }
                $html .= '</div>';
            }
        }
        return $html;
    }

    public static function download(): void
    {
        check_admin_referer('gpf_pdf');
        $entry = Repository::entry(absint($_GET['id'] ?? 0));
        if (!$entry || !Permissions::can($entry['form_id'], 'entries')) { wp_die('Access denied.', '', ['response' => 403]); }
        try {
            $bytes = self::entry($entry); nocache_headers(); header('X-Content-Type-Options: nosniff'); header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="gitpress-entry-' . $entry['id'] . '.pdf"'); echo $bytes; exit;
        } catch (\Throwable $e) { wp_die(esc_html($e->getMessage()), '', ['response' => 500]); }
    }
}
