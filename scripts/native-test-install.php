<?php
define('WP_INSTALLING', true);
require dirname(__DIR__) . '/.runtime/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
add_filter('pre_wp_mail', static fn () => true);
if (!is_blog_installed()) { wp_install('GitPress Forms Test', 'gpf_admin', 'admin@example.test', false, '', 'gitpress-test-only'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$result = activate_plugin('gitpress-forms/gitpress-forms.php');
if (is_wp_error($result)) { fwrite(STDERR, $result->get_error_message()); exit(1); }
update_option('permalink_structure', '/%postname%/');
flush_rewrite_rules();
echo 'WordPress ' . get_bloginfo('version') . " installed on isolated MySQL.\n";
