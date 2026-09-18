<?php
// This file runs only in the isolated test WordPress, never in a client installation.
$sandbox = dirname(__DIR__, 2) . '/.runtime/wordpress';
if (!is_file($sandbox . '/.gitpress-test-sandbox')) { throw new RuntimeException('Missing test sandbox.'); }
require $sandbox . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
wp_set_current_user(get_user_by('login', 'gpf_admin')->ID);
$gitpress = 'gitpress/divi-github-sync.php';
if (!is_file(WP_PLUGIN_DIR . '/' . $gitpress)) { throw new RuntimeException('Mount the reference GitPress plugin in the isolated test site first.'); }
$result = activate_plugin($gitpress);
if (is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
if (!class_exists('DGS_Cache_Handler')) { require_once WP_PLUGIN_DIR . '/' . $gitpress; }
GitPress::init();
$definition = GitPress\Forms\Definition::empty();
$definition['fields'] = [['id' => 'email', 'name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true, 'options' => [], 'children' => []]];
$form = GitPress\Forms\Repository::save(['title' => 'GitPress embed acceptance', 'status' => 'published', 'definition' => $definition]);
$pages = [];
foreach (['theme_wrapped', 'full_canvas', 'gitpress_managed', 'stale'] as $mode) {
    $path = $mode . '.html';
    $key = DGS_Cache_Handler::generate_key('gitpress-forms-test', 'fixture', $path);
    $payload = ['owner' => 'gitpress-forms-test', 'repo' => 'fixture', 'branch' => 'main', 'path' => $path, 'sha' => 'fixture-v1', 'content' => '<h2>GitPress cached fragment</h2>[gitpress_form id="' . $form['id'] . '"]', 'fetched_at' => time()];
    DGS_Cache_Handler::set($key, $payload, 86400);
    if ($mode === 'stale') { delete_transient(DGS_CACHE_PREFIX . $key); }
    $page = wp_insert_post(['post_type' => 'page', 'post_title' => 'GitPress ' . $mode, 'post_status' => 'publish', 'post_content' => '']);
    update_post_meta($page, DGS_Page_Shortcode_Manager::META_KEY_SHORTCODE, '[divi_github_content owner="gitpress-forms-test" repo="fixture" path="' . $path . '" stale_notice="true"]');
    update_post_meta($page, DGS_Page_Shortcode_Manager::META_KEY_RENDER_MODE, $mode === 'stale' ? 'full_canvas' : $mode);
    update_post_meta($page, DGS_Page_Shortcode_Manager::META_KEY_PLACEMENT, 'replace');
    $pages[$mode] = $page;
}
$pages['multiple'] = wp_insert_post(['post_type' => 'page', 'post_title' => 'Two instances', 'post_status' => 'publish', 'post_content' => '[gitpress_form id="' . $form['id'] . '"][gitpress_form id="' . $form['id'] . '"]']);
$pages['popup'] = wp_insert_post(['post_type' => 'page', 'post_title' => 'Popup form', 'post_status' => 'publish', 'post_content' => '[gitpress_form id="' . $form['id'] . '" popup="Contact us"]']);
$pages['block'] = wp_insert_post(['post_type' => 'page', 'post_title' => 'Styled form block', 'post_status' => 'publish', 'post_content' => '<!-- wp:gitpress-forms/form ' . wp_json_encode(['id' => $form['id'], 'primary' => '#ac2255', 'radius' => 12, 'maxWidth' => 600]) . ' /-->']);
file_put_contents(dirname(__DIR__, 2) . '/.runtime/fixtures.json', wp_json_encode(['formId' => $form['id'], 'pages' => $pages], JSON_PRETTY_PRINT));
echo "Created isolated GitPress integration fixtures.\n";
