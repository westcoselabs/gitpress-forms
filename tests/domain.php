<?php
// Domain checks run without a database; integration tests execute against real WordPress.
define('GPF_DIR', dirname(__DIR__) . '/');
spl_autoload_register(static function ($class) { $prefix = 'GitPress\\Forms\\'; if (str_starts_with($class, $prefix)) { require GPF_DIR . 'includes/' . substr($class, strlen($prefix)) . '.php'; } });
function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_textarea_field($v) { return trim(strip_tags((string) $v)); }
function wp_kses_post($v) { return strip_tags((string) $v, '<p><b><em><strong>'); }
function wp_strip_all_tags($v) { return strip_tags((string) $v); }
function is_email($v) { return filter_var($v, FILTER_VALIDATE_EMAIL) !== false; }
function wp_http_validate_url($v) { return filter_var($v, FILTER_VALIDATE_URL) !== false; }
function wp_json_encode($v, $flags = 0) { return json_encode($v, $flags); }
function sanitize_key($v) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($v)); }
function sanitize_hex_color($v) { return preg_match('/^#[a-f0-9]{6}$/i', $v) ? $v : null; }
function esc_url_raw($v) { return filter_var($v, FILTER_VALIDATE_URL) ? $v : ''; }
function current_user_can($cap) { return true; }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
use GitPress\Forms\{Logic, Validation, Definition};
$passed = 0;
function check($truth, $description) { global $passed; if (!$truth) { fwrite(STDERR, "FAIL: $description\n"); exit(1); } $passed++; echo "PASS: $description\n"; }
$cases = json_decode(file_get_contents(__DIR__ . '/fixtures/logic.json'), true);
foreach ($cases['calculations'] as $case) { check(abs(Logic::calculate($case['formula'], $case['values']) - $case['expected']) < 0.00001, 'Formula ' . $case['formula']); }
foreach ($cases['invalid'] as $expression) { $threw = false; try { Logic::calculate($expression, ['quantity' => 'not a number']); } catch (\InvalidArgumentException) { $threw = true; } check($threw, 'Reject invalid formula ' . $expression); }
foreach ($cases['conditions'] as $index => $case) { check(Logic::matches($case['rule'], $case['values']) === $case['expected'], 'Condition ' . $index); }
function field($name, $type = 'text', $extra = []) { return array_merge(['id' => $name, 'name' => $name, 'type' => $type, 'label' => ucfirst($name), 'required' => false, 'children' => [], 'options' => [], 'default' => '', 'placeholder' => '', 'help' => '', 'width' => 12], $extra); }
$condition = ['mode' => 'all', 'rules' => [['field' => 'choice', 'operator' => 'eq', 'value' => 'yes']]];
$fields = [field('choice'), field('private', 'text', ['required' => true, 'condition' => $condition]), field('quantity', 'number'), field('price', 'number'), field('total', 'calculation', ['formula' => '{quantity}*{price}'])];
$result = Validation::values($fields, ['choice' => 'no', 'private' => 'injected hidden answer', 'quantity' => '3', 'price' => '10', 'total' => '-1', 'is_admin' => true]);
check(!isset($result['values']['private']) && !isset($result['values']['is_admin']), 'Hidden and unknown fields are not persisted');
check($result['values']['total'] === 30.0, 'Server overrides tampered calculation');
check(!$result['errors'], 'Hidden required field is not required');
$result = Validation::values([field('email', 'email', ['required' => true]), field('color', 'select', ['options' => [['label' => 'Blue', 'value' => 'blue']]])], ['email' => 'invalid', 'color' => 'injected']);
check(isset($result['errors']['email'], $result['errors']['color']), 'Reject malformed email and unlisted choice');
$fields = [field('choice'), field('container', 'container', ['condition' => $condition, 'children' => [field('price', 'number')]]), field('total', 'calculation', ['formula' => '{price}+1'])];
$result = Validation::values($fields, ['choice' => 'no', 'price' => '999']);
check($result['values']['total'] === 1.0, 'Hidden container values do not influence dependent calculations');
$result = Validation::values([field('people', 'repeat', ['children' => [field('email', 'email', ['required' => true])]])], ['people' => [['email' => 'bad'], ['email' => 'valid@example.com']]]);
check(isset($result['errors']['people.0.email']) && !isset($result['errors']['people.1.email']), 'Repeater validation uses row-specific errors');
$captcha = field('human_check', 'recaptcha', ['required' => true]);
$result = Validation::values([$captcha], []);
check(isset($result['errors']['human_check']), 'reCAPTCHA is required before submission');
$result = Validation::values([$captcha], ['human_check' => 'one-time-provider-token']);
check(!$result['errors'] && Validation::withoutPasswords([$captcha], $result['values']) === [], 'reCAPTCHA tokens validate locally but are never persisted');
$def = Definition::empty(); $def['fields'] = [field('a', 'calculation', ['formula' => '{b}']), field('b', 'calculation', ['formula' => '{a}'])];
$threw = false; try { Definition::validate($def); } catch (\InvalidArgumentException) { $threw = true; } check($threw, 'Reject cyclic calculations at save time');
$def['fields'] = [field('duplicate'), field('duplicate')]; $threw = false; try { Definition::validate($def); } catch (\InvalidArgumentException) { $threw = true; } check($threw, 'Reject duplicate field identifiers');
echo "$passed PHP domain checks passed.\n";
