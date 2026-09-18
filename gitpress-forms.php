<?php
/**
 * Plugin Name: GitPress Forms
 * Description: Independent forms, submissions, and workflows for WordPress and GitPress.
 * Version: 0.1.1-dev
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: WestCose Labs
 * License: GPL-2.0-or-later
 * Text Domain: gitpress-forms
 */
defined('ABSPATH') || exit;
define('GPF_VERSION', '0.1.1-dev');
define('GPF_DIR', __DIR__ . '/');
define('GPF_URL', plugin_dir_url(__FILE__));
if (is_file(GPF_DIR . 'vendor/autoload.php')) { require_once GPF_DIR . 'vendor/autoload.php'; }
spl_autoload_register(static function (string $class): void {
    $prefix = 'GitPress\\Forms\\';
    if (str_starts_with($class, $prefix)) {
        $path = GPF_DIR . 'includes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) { require_once $path; }
    }
});
register_activation_hook(__FILE__, [GitPress\Forms\Database::class, 'install']);
register_deactivation_hook(__FILE__, static function (): void { wp_clear_scheduled_hook('gitpress_forms_jobs'); wp_clear_scheduled_hook('gitpress_forms_jobs_soon'); });
add_action('plugins_loaded', [GitPress\Forms\Plugin::class, 'boot']);
