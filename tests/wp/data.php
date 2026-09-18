<?php
$sandbox = dirname(__DIR__, 2) . '/.runtime/wordpress';
if (!is_file($sandbox . '/.gitpress-test-sandbox')) { throw new RuntimeException('Missing isolated test sandbox.'); }
require $sandbox . '/wp-load.php';
wp_set_current_user(get_user_by('login', 'gpf_admin')->ID);
use GitPress\Forms\{Database, Definition, Importer, Repository, Security, Privacy, Jobs};
$checks = 0;
function check_data($condition, $message): void { global $checks; if (!$condition) { throw new RuntimeException('FAIL: ' . $message); } $checks++; echo 'PASS: ' . $message . "\n"; }
$source = 'migration_' . wp_generate_uuid4();
$input = ['source' => $source, 'forms' => [['id' => 17, 'title' => 'Migration fixture', 'form_fields' => ['fields' => [
    ['element' => 'input_email', 'attributes' => ['name' => 'email'], 'settings' => ['label' => 'Email', 'validation_rules' => ['required' => ['value' => true]]]],
    ['element' => 'input_text', 'attributes' => ['name' => 'details'], 'settings' => ['label' => 'Details', 'conditional_logics' => ['status' => true, 'type' => 'all', 'conditions' => [['field' => 'email', 'operator' => 'contains', 'value' => '@']]]]],
    ['element' => 'input_password', 'attributes' => ['name' => 'password'], 'settings' => ['label' => 'Password']],
]], 'metas' => [['meta_key' => 'formSettings', 'value' => json_encode(['confirmation' => ['messageToShow' => 'Thanks {inputs.email}'], 'restrictions' => ['requireLogin' => ['enabled' => true]], 'layout' => ['labelPlacement' => 'left']])]], 'entries' => [['id' => 42, 'response' => ['email' => 'migration@example.test', 'details' => 'Historical answer', 'password' => 'Never store'], 'created_at' => Database::now()]]]]];
$original = json_encode($input); $preview = Importer::preview($input);
check_data($preview['forms'][0]['definition']['settings']['confirmation'] === 'Thanks {email}', 'Fluent smart codes are converted');
check_data($preview['forms'][0]['definition']['settings']['requireLogin'] === true, 'Fluent restrictions are converted');
check_data($preview['forms'][0]['definition']['fields'][1]['condition']['rules'][0]['operator'] === 'contains', 'Fluent field conditions are converted');
$first = Importer::run($input); $second = Importer::run($input); $id = $first['forms'][0]['id'];
check_data($id === $second['forms'][0]['id'] && $second['forms'][0]['entriesImported'] === 0, 'Repeat imports reuse form and entry mappings');
check_data(Repository::form($id)['status'] === 'draft', 'Imported forms remain drafts');
$entries = Repository::entries($id); $entry = $entries['items'][0];
check_data(count($entries['items']) === 1 && !isset($entry['response']['password']), 'Historical entries deduplicate and redact passwords');
global $wpdb;
check_data(!(int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Database::table('jobs') . ' WHERE entry_id=%d', $entry['id'])), 'Historical import creates no delivery jobs');
check_data(json_encode($input) === $original, 'Source export remains unchanged');
$token = Security::issue('resume', $id, ['values' => ['email' => 'migration@example.test'], 'entryId' => 0], 3600);
$export = Privacy::export('migration@example.test'); check_data(count($export['data']) === 2, 'Privacy exporter includes entries and unfinished progress');
$erase = Privacy::erase('migration@example.test'); check_data($erase['items_removed'] && !Repository::entry($entry['id']) && !Security::lookup($token, 'resume', $id), 'Privacy erasure removes entries and resume data');
$restore = get_option('gitpress_forms_connections', []);
try {
    update_option('gitpress_forms_connections', ['webhook' => Security::encrypt(Database::json(['url' => 'https://gitpress-test.invalid/receive', 'secret' => 'test-only-redaction-value']))], false);
    $feed = GitPress\Forms\Integrations::saveFeed($id, ['provider' => 'webhook', 'enabled' => true, 'configuration' => ['mapping' => ['payload' => '{all_fields}']]]);
    $wpdb->insert(Database::table('entries'), ['form_id' => $id, 'form_version' => 1, 'status' => 'unread', 'response' => '{}', 'notes' => '[]', 'user_id' => 0, 'source_url' => '', 'idempotency_key' => wp_generate_uuid4(), 'created_at' => Database::now(), 'updated_at' => Database::now()]); $entryId = (int) $wpdb->insert_id;
    Jobs::enqueue($entryId, 'integration', ['feedId' => $feed['id']], 'test'); Jobs::enqueue($entryId, 'integration', ['feedId' => $feed['id']], 'test');
    check_data((int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Database::table('jobs') . ' WHERE entry_id=%d', $entryId)) === 1, 'Duplicate workflow enqueue creates one job');
    add_filter('pre_http_request', static fn ($pre, $args, $url) => str_contains($url, 'gitpress-test.invalid') ? new WP_Error('controlled_outage', 'test-only-redaction-value') : $pre, 10, 3);
    Jobs::run(); $job = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Database::table('jobs') . ' WHERE entry_id=%d', $entryId), ARRAY_A);
    check_data($job['status'] === 'pending' && (int) $job['attempts'] === 1 && !str_contains($job['last_error'], 'test-only-redaction-value'), 'Failed provider delivery retries with redacted details');
    $wpdb->update(Database::table('jobs'), ['attempts' => 4, 'available_at' => gmdate('Y-m-d H:i:s', time() - 1)], ['id' => $job['id']]); Jobs::run();
    check_data($wpdb->get_var($wpdb->prepare('SELECT status FROM ' . Database::table('jobs') . ' WHERE id=%d', $job['id'])) === 'failed', 'Repeated delivery failure remains visible for manual retry');
} finally { update_option('gitpress_forms_connections', $restore, false); }
echo "$checks WordPress/MySQL data checks passed.\n";
