<?php
namespace GitPress\Forms;

final class Plugin
{
    public static function boot(): void
    {
        if ((int) get_option('gitpress_forms_schema', 0) !== 3) { Database::install(); }
        do_action('gitpress_forms/register_extensions', Extensions::class);
        add_filter('map_meta_cap', static function ($caps, $cap, $userId) { if ($cap !== 'access_gitpress_forms') return $caps; $user = get_userdata($userId); return $user && ($user->has_cap('manage_gitpress_forms') || $user->has_cap('view_gitpress_entries')) ? ['read'] : ['do_not_allow']; }, 10, 3);
        add_action('init', static function (): void {
            load_plugin_textdomain('gitpress-forms', false, dirname(plugin_basename(GPF_DIR . 'gitpress-forms.php')) . '/languages');
            add_shortcode('gitpress_form', [Renderer::class, 'shortcode']);
            wp_register_script('gitpress-forms-block', GPF_URL . 'build/block.js', ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-api-fetch'], GPF_VERSION, true);
            register_block_type('gitpress-forms/form', ['api_version' => 3, 'editor_script' => 'gitpress-forms-block', 'supports' => ['align' => ['wide', 'full'], 'spacing' => ['margin' => true, 'padding' => true]], 'attributes' => ['id' => ['type' => 'number', 'default' => 0], 'primary' => ['type' => 'string'], 'background' => ['type' => 'string'], 'radius' => ['type' => 'number'], 'maxWidth' => ['type' => 'number']], 'render_callback' => [Block::class, 'render']]);
        });
        add_filter('dgs_allowed_inner_shortcodes', static fn ($names) => array_values(array_unique([...$names, 'gitpress_form'])));
        add_action('admin_menu', static function (): void { add_menu_page('GitPress Forms', 'GitPress Forms', 'access_gitpress_forms', 'gitpress-forms', [self::class, 'admin'], 'dashicons-feedback', 82); });
        add_action('admin_enqueue_scripts', static function (string $hook): void {
            if ($hook !== 'toplevel_page_gitpress-forms') { return; }
            wp_enqueue_style('gitpress-forms-admin', GPF_URL . 'build/admin.css', [], GPF_VERSION);
            wp_enqueue_script('gitpress-forms-admin', GPF_URL . 'build/admin.js', [], GPF_VERSION, true);
            wp_localize_script('gitpress-forms-admin', 'GitPressFormsAdmin', ['api' => rest_url('gitpress-forms/v1/'), 'nonce' => wp_create_nonce('wp_rest'), 'site' => home_url('/'), 'version' => GPF_VERSION, 'fieldTypes' => Definition::catalog(), 'canManage' => current_user_can('manage_gitpress_forms'), 'canIntegrate' => current_user_can('manage_gitpress_integrations'), 'canAdmin' => current_user_can('manage_options'), 'canEntries' => current_user_can('view_gitpress_entries'), 'canEditEntries' => current_user_can('manage_gitpress_entries')]);
        });
        Privacy::register();
        add_action('rest_api_init', [RestApi::class, 'register']);
        add_action('rest_api_init', [Mcp::class, 'register']);
        add_action('gitpress_forms_jobs', [Jobs::class, 'run']);
        add_action('gitpress_forms_jobs_soon', [Jobs::class, 'run']);
        add_action('wp_ajax_gitpress_forms_session', [self::class, 'session']);
        add_action('wp_ajax_nopriv_gitpress_forms_session', [self::class, 'session']);
        add_action('template_redirect', [self::class, 'standalone']);
        add_action('template_redirect', [EntryView::class, 'render']);
        add_action('admin_post_gpf_download', [self::class, 'download']);
        add_action('admin_post_gpf_pdf', [Pdf::class, 'download']);
    }
    public static function admin(): void
    {
        if (!current_user_can('access_gitpress_forms')) { wp_die('You cannot manage forms.'); }
        echo '<div id="gitpress-forms-admin"><p>Loading GitPress Forms…</p></div>';
        if (!is_file(GPF_DIR . 'build/admin.js')) { echo '<div class="notice notice-error"><p>GitPress Forms assets are missing. Run npm install and npm run build before installing the plugin.</p></div>'; }
    }
    public static function session(): void
    {
        nocache_headers();
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::origin()) { throw new \RuntimeException('Invalid request.', 403); }
            $id = absint($_POST['form_id'] ?? 0); Submissions::available($id); Security::rateLimit('session', 120);
            global $wpdb;
            $wpdb->insert(Database::table('events'), ['form_id' => $id, 'kind' => 'view', 'created_at' => Database::now()]);
            wp_send_json(['token' => Security::token($id), 'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '']);
        } catch (\Throwable $e) { wp_send_json(['message' => $e->getMessage()], in_array($e->getCode(), [401, 403, 404, 429], true) ? $e->getCode() : 400); }
    }
    public static function standalone(): void
    {
        if (isset($_GET['gpf_confirm'])) {
            try { Submissions::confirm(absint($_GET['form'] ?? 0), sanitize_text_field(wp_unslash($_GET['gpf_confirm']))); wp_die('Thank you. Your submission has been confirmed.', 'GitPress Forms', ['response' => 200]); }
            catch (\Throwable $e) { wp_die(esc_html($e->getMessage()), 'GitPress Forms', ['response' => 400]); }
        }
        $preview = isset($_GET['gpf_preview']); $landing = isset($_GET['gitpress_form']);
        if (!$preview && !$landing) { return; }
        if ($preview && (!current_user_can('manage_gitpress_forms') || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce'] ?? ''), 'wp_rest'))) { wp_die('Preview access denied.', '', ['response' => 403]); }
        $form = Repository::form(absint($_GET[$preview ? 'gpf_preview' : 'gitpress_form']));
        if (!$form || (!$preview && $form['status'] !== 'published')) { status_header(404); exit; }
        if ($preview && !Permissions::can($form['id'])) { wp_die('Preview access denied.', '', ['response' => 403]); }
        nocache_headers(); $body = Renderer::render($form, $preview);
        echo '<!doctype html><html ' . get_language_attributes() . '><head><meta charset="' . esc_attr(get_bloginfo('charset')) . '"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . esc_html($form['title']) . '</title>';
        wp_head(); echo '</head><body style="margin:0;background:#f5f7fb;font-family:system-ui,sans-serif;padding:48px 20px"><main style="max-width:960px;margin:auto"><h1 style="font-size:28px;margin-bottom:28px">' . esc_html($form['title']) . '</h1>' . $body . '</main>'; wp_footer(); echo '</body></html>'; exit;
    }
    public static function download(): void
    {
        if (!current_user_can('view_gitpress_entries')) { wp_die('Access denied.', '', ['response' => 403]); }
        check_admin_referer('gpf_download'); global $wpdb;
        $file = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Database::table('files') . ' WHERE id=%d AND entry_id>0', absint($_GET['id'] ?? 0)), ARRAY_A);
        if (!$file || !is_file($file['path'])) { wp_die('File not found.', '', ['response' => 404]); }
        if (!Permissions::can((int) $file['form_id'], 'entries')) { wp_die('Access denied.', '', ['response' => 403]); }
        $data = Security::decrypt(file_get_contents($file['path'])); nocache_headers(); header('X-Content-Type-Options: nosniff'); header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="' . sanitize_file_name($file['original_name']) . '"'); header('Content-Length: ' . strlen($data)); echo $data; exit;
    }
}
