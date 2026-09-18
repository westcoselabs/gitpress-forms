<?php
$taskRoot = realpath(dirname(__DIR__, 2) . '/.runtime/matrix-6.6/wordpress');
if (!$taskRoot || !is_file($taskRoot . '/.gitpress-test-sandbox') || !is_file($taskRoot . '/packages/gitpress-forms/gitpress-forms.php')) { throw new RuntimeException('This destructive lifecycle test requires the isolated packaged-plugin matrix site.'); }
require $taskRoot . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once WP_PLUGIN_DIR . '/gitpress-forms/gitpress-forms.php';
use GitPress\Forms\{Database, Repository, Definition};
$definition = Definition::empty(); $form = Repository::save(['title' => 'Lifecycle fixture', 'definition' => $definition]);
$plugin = 'gitpress-forms/gitpress-forms.php';
deactivate_plugins($plugin);
if (!Repository::form($form['id'])) { throw new RuntimeException('Deactivation deleted data.'); }
echo "PASS: Deactivation preserves forms.\n";
update_option('gitpress_forms_delete_on_uninstall', 'no');
if (($argv[1] ?? 'preserve') === 'preserve') {
uninstall_plugin($plugin);
if (!Repository::form($form['id'])) { throw new RuntimeException('Default uninstall deleted data.'); }
echo "PASS: Uninstall preserves data by default.\n";
exit;
}
update_option('gitpress_forms_delete_on_uninstall', 'yes');
uninstall_plugin($plugin);
global $wpdb;
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like(Database::table('forms'))))) { throw new RuntimeException('Explicit uninstall did not delete plugin tables.'); }
if (get_role('administrator')->has_cap('manage_gitpress_entries')) { throw new RuntimeException('Uninstall left role capabilities.'); }
echo "PASS: Explicit uninstall removes plugin tables and role capabilities.\n";
