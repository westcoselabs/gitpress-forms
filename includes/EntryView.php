<?php
namespace GitPress\Forms;

final class EntryView
{
    public static function link(array $entry, array $form): string
    {
        if (empty($form['definition']['settings']['entryView']['enabled'])) { return ''; }
        $token = Security::issue('entry_view', $form['id'], ['entryId' => $entry['id']], 86400 * 365);
        return add_query_arg(['gpf_entry' => $token, 'form' => $form['id']], home_url('/'));
    }

    public static function render(): void
    {
        if (!isset($_GET['gpf_entry'])) { return; }
        $id = absint($_GET['form'] ?? 0);
        $token = Security::lookup(sanitize_text_field(wp_unslash($_GET['gpf_entry'])), 'entry_view', $id);
        $entry = $token ? Repository::entry((int) $token['payload']['entryId']) : null;
        $form = Repository::form($id); $settings = $form['definition']['settings']['entryView'] ?? [];
        if (!$entry || !$form || empty($settings['enabled']) || in_array($entry['status'], ['trash', 'spam', 'rejected'], true)) { wp_die('This submission link is unavailable.', '', ['response' => 404]); }
        $owner = is_user_logged_in() && (int) $entry['user_id'] === get_current_user_id();
        if (!empty($settings['restricted']) && !$owner && !Permissions::can($id, 'entries')) { wp_die('Sign in with the account that submitted this entry.', '', ['response' => 403]); }
        $form = Repository::formForEntry($entry);
        nocache_headers(); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
        echo '<!doctype html><html ' . get_language_attributes() . '><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . esc_html($form['title']) . '</title><style>body{margin:0;background:' . esc_attr($settings['background'] ?? '#f5f7fb') . ';font:16px/1.6 system-ui;color:#263348}main{max-width:780px;margin:40px auto;background:#fff;padding:32px;border-radius:8px}h1{font-size:26px}h2{font-size:19px}h3{font-size:14px;margin:0;color:#657187}.answer{border-bottom:1px solid #e1e6ee;padding:16px 0}.answer p{white-space:pre-wrap;overflow-wrap:anywhere}img{max-width:100%}@media(max-width:600px){main{margin:10px;padding:20px}}</style></head><body><main><h1>' . esc_html($form['title']) . '</h1><p>' . esc_html(Submissions::smart($settings['intro'] ?? '', $entry['response'])) . '</p>' . Pdf::answers($form['definition']['fields'], $entry['response'], $entry['id']) . '</main></body></html>'; exit;
    }
}
