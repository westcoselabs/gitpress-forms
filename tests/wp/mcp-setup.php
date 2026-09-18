<?php
$sandbox = dirname(__DIR__, 2) . '/.runtime/wordpress';
if (!is_file($sandbox . '/.gitpress-test-sandbox')) { throw new RuntimeException('Missing isolated test sandbox.'); }
require $sandbox . '/wp-load.php';
$user = get_user_by('login', 'gpf_mcp');
if (!$user) { $user = new WP_User(wp_insert_user(['user_login' => 'gpf_mcp', 'user_pass' => wp_generate_password(32), 'user_email' => 'mcp@example.test', 'role' => 'subscriber'])); }
$user->add_cap('manage_gitpress_forms'); $user->add_cap('view_gitpress_entries');
WP_Application_Passwords::delete_all_application_passwords($user->ID);
$result = WP_Application_Passwords::create_new_application_password($user->ID, ['name' => 'Isolated MCP acceptance']);
if (is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
file_put_contents(dirname(__DIR__, 2) . '/.runtime/mcp-credentials.json', wp_json_encode(['user' => $user->user_login, 'password' => $result[0]]));
echo "Created isolated MCP test credentials in ignored runtime storage.\n";
