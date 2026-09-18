<?php
$sandbox = dirname(__DIR__, 2) . '/.runtime/wordpress';
if (!is_file($sandbox . '/.gitpress-test-sandbox')) { throw new RuntimeException('Missing isolated test sandbox.'); }
require $sandbox . '/wp-load.php';
use GitPress\Forms\{Database, Definition, Recaptcha, Repository, Security, Validation};
$passed = 0;
function verify($value, $message): void { global $passed; if (!$value) { throw new RuntimeException('FAIL: ' . $message); } $passed++; echo 'PASS: ' . $message . "\n"; }
function request($method, $path, $body = null) { $r = new WP_REST_Request($method, '/gitpress-forms/v1/' . $path); if ($body !== null) { $r->set_header('content-type', 'application/json'); $r->set_body(wp_json_encode($body)); } return rest_get_server()->dispatch($r); }
$admin = get_user_by('login', 'gpf_admin'); wp_set_current_user($admin->ID);
$readerId = wp_insert_user(['user_login' => 'gpf_reader_' . wp_generate_password(8, false), 'user_pass' => wp_generate_password(32), 'user_email' => wp_generate_uuid4() . '@example.test', 'role' => 'subscriber']);
$reader = new WP_User($readerId); $reader->add_cap('view_gitpress_entries'); $reader->add_cap('manage_gitpress_forms');
$def = Definition::empty(); $def['fields'] = [['id' => 'email', 'name' => 'email', 'type' => 'email', 'label' => 'Email', 'children' => [], 'options' => []]];
$def['settings']['access'] = ['restricted' => true, 'manageUsers' => [], 'entryUsers' => []];
$private = Repository::save(['title' => 'Private security fixture', 'definition' => $def, 'status' => 'published']);
$entryId = Database::transaction(static function () use ($private) { global $wpdb; $wpdb->insert(Database::table('entries'), ['form_id' => $private['id'], 'form_version' => 1, 'status' => 'unread', 'response' => '{"email":"private@example.test"}', 'notes' => '[]', 'user_id' => 0, 'source_url' => '', 'idempotency_key' => wp_generate_uuid4(), 'created_at' => Database::now(), 'updated_at' => Database::now()]); return (int) $wpdb->insert_id; });
wp_set_current_user($readerId);
verify(request('GET', 'forms/' . $private['id'])->get_status() === 403, 'Private form definition denied to unrelated manager');
verify(!in_array($private['id'], array_column(request('GET', 'forms')->get_data(), 'id'), true), 'Private form absent from form list');
verify(request('GET', 'entries/' . $entryId . '/files')->get_status() === 403, 'Private attachment listing denied');
$entries = request('GET', 'entries')->get_data(); verify(!in_array($entryId, array_column($entries['items'], 'id'), true), 'Global entries list respects form permissions');
verify(request('PUT', 'entries/' . $entryId, ['status' => 'trash'])->get_status() === 403, 'Reader cannot modify entries');
verify(request('PUT', 'settings', ['deleteOnUninstall' => true])->get_status() === 403, 'Form manager cannot enable uninstall deletion');
verify(request('PUT', 'permissions', ['roles' => []])->get_status() === 403, 'Form manager cannot grant role capabilities');
$private['definition']['settings']['access']['manageUsers'] = [$readerId];
wp_set_current_user($admin->ID); $private = Repository::save($private, $private['id']);
wp_set_current_user($readerId);
verify(request('GET', 'forms/' . $private['id'])->get_status() === 200, 'Explicit form manager may open assigned form');
verify(request('GET', 'entries/' . $entryId . '/files')->get_status() === 403, 'Form editing grant does not imply entry access');
$escalation = $private; $escalation['definition']['settings']['access']['entryUsers'] = [$readerId];
verify(request('PUT', 'forms/' . $private['id'], $escalation)->get_status() === 403, 'Manager cannot self-grant entry access');
wp_set_current_user($admin->ID);
global $wpdb; $before = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Database::table('forms'));
try { Database::transaction(static function () use ($def) { Repository::save(['title' => 'Must roll back', 'definition' => $def]); throw new RuntimeException('Controlled rollback'); }); } catch (RuntimeException $e) { verify($e->getMessage() === 'Controlled rollback', 'Nested transaction reaches controlled failure'); }
verify((int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Database::table('forms')) === $before, 'Outer rollback reverses nested form save and revision');
$callbacks = [];
Database::transaction(static function () use (&$callbacks) {
    Database::afterCommit(static function () use (&$callbacks) { $callbacks[] = 'outer'; });
    try { Database::transaction(static function () use (&$callbacks) { Database::afterCommit(static function () use (&$callbacks) { $callbacks[] = 'rolled-back'; }); throw new RuntimeException('rollback'); }); } catch (RuntimeException $e) {}
    verify(!$callbacks, 'Extension callbacks wait for outer commit');
});
verify($callbacks === ['outer'], 'Rolled-back nested callbacks never execute');
$plain = 'credential-' . wp_generate_uuid4(); $cipher = Security::encrypt($plain);
verify(!str_contains($cipher, $plain) && Security::decrypt($cipher) === $plain, 'Credentials encrypt and decrypt correctly');
$siteKey = 'test-site-key-' . str_repeat('a', 24); $secretKey = 'test-secret-key-' . str_repeat('b', 24);
$settings = request('PUT', 'settings', ['deleteOnUninstall' => false, 'recaptchaSiteKey' => $siteKey, 'recaptchaSecret' => $secretKey])->get_data();
verify($settings['recaptchaConfigured'] && $settings['recaptchaSiteKey'] === $siteKey && !isset($settings['recaptchaSecret']), 'reCAPTCHA settings expose the site key but never the secret');
verify(get_option('gitpress_forms_recaptcha_secret') !== $secretKey, 'reCAPTCHA secret is encrypted at rest');
$captchaFields = [['id' => 'captcha', 'name' => 'captcha', 'type' => 'recaptcha', 'label' => 'Human check', 'required' => true, 'children' => [], 'options' => []]];
$passFilter = static fn () => true; add_filter('gitpress_forms/recaptcha_verification', $passFilter);
verify(Recaptcha::verify($captchaFields, ['captcha' => 'valid-test-token'], $private['id']) === [], 'reCAPTCHA accepts a server-verified token');
remove_filter('gitpress_forms/recaptcha_verification', $passFilter);
$failFilter = static fn () => false; add_filter('gitpress_forms/recaptcha_verification', $failFilter);
verify(isset(Recaptcha::verify($captchaFields, ['captcha' => 'invalid-test-token'], $private['id'])['captcha']), 'reCAPTCHA rejects a failed server verification');
remove_filter('gitpress_forms/recaptcha_verification', $failFilter);
request('PUT', 'settings', ['deleteOnUninstall' => false, 'clearRecaptcha' => true]);
verify(!Security::checkToken(Security::token($private['id']), $private['id'] + 1), 'Submission token is bound to its form');
verify(!Security::lookup(str_repeat('a', 64), 'resume', $private['id']), 'Unknown resume token yields no data');
$fields = [['id' => 'group', 'name' => 'group', 'type' => 'repeat', 'children' => [['id' => 'pw', 'name' => 'password', 'type' => 'password', 'children' => []]]]];
verify(Validation::withoutPasswords($fields, ['group' => [['password' => 'sensitive']]]) === ['group' => [[]]], 'Nested passwords removed before storage');
require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user($readerId);
echo "$passed WordPress/MySQL security checks passed.\n";
