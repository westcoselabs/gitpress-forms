<?php
defined('WP_UNINSTALL_PLUGIN') || exit;
if (get_option('gitpress_forms_delete_on_uninstall') !== 'yes') { return; }
global $wpdb;
$uploads = wp_upload_dir();
$directory = realpath($uploads['basedir'] . '/gitpress-forms-private');
if ($directory) {
    $files = $wpdb->get_col('SELECT path FROM ' . $wpdb->prefix . 'gitpress_forms_files');
    foreach ($files as $file) { $path = realpath($file); if ($path && dirname($path) === $directory && str_ends_with($path, '.enc')) { wp_delete_file($path); } }
}
foreach (['forms', 'revisions', 'entry_revisions', 'entries', 'files', 'feeds', 'jobs', 'logs', 'payments', 'subscriptions', 'inventory', 'imports', 'tokens', 'events'] as $suffix) {
    $table = $wpdb->prefix . 'gitpress_forms_' . $suffix;
    $wpdb->query("DROP TABLE IF EXISTS `$table`");
}
foreach (['gitpress_forms_schema', 'gitpress_forms_settings', 'gitpress_forms_connections', 'gitpress_forms_delete_on_uninstall', 'gitpress_forms_recaptcha_site_key', 'gitpress_forms_recaptcha_secret'] as $option) { delete_option($option); }
foreach (array_keys(wp_roles()->roles) as $name) {
    $role = get_role($name);
    foreach (['manage_gitpress_forms', 'view_gitpress_entries', 'manage_gitpress_entries', 'manage_gitpress_integrations'] as $cap) { $role?->remove_cap($cap); }
}
wp_clear_scheduled_hook('gitpress_forms_jobs');
wp_clear_scheduled_hook('gitpress_forms_jobs_soon');
