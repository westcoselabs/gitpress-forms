<?php
namespace GitPress\Forms;

final class Importer
{
    public static function preview(array $input): array
    {
        $forms = $input['forms'] ?? (isset($input['definition']) || isset($input['form_fields']) ? [$input] : $input);
        if (!is_array($forms) || count($forms) > 100) { throw new \InvalidArgumentException('Import up to 100 forms at a time.'); }
        $results = [];
        foreach ($forms as $index => $form) {
            if (!is_array($form)) { throw new \InvalidArgumentException('Invalid form export.'); }
            $warnings = [];
            if (isset($form['definition'])) { $definition = Definition::validate($form['definition']); }
            else {
                if (!isset($form['form_fields'])) { throw new \InvalidArgumentException('Unsupported export format. Supply a GitPress Forms or Fluent Forms JSON export.'); }
                $raw = $form['form_fields'] ?? [];
                if (is_string($raw)) { $raw = json_decode($raw, true); }
                $definition = Definition::empty();
                $definition['fields'] = self::fluentFields($raw['fields'] ?? [], $warnings);
                FluentMigration::settings($form, $raw, $definition, $warnings);
                $definition = Definition::validate($definition);
            }
            $entries = $form['entries'] ?? [];
            if (!is_array($entries) || count($entries) > 500) { throw new \InvalidArgumentException('Import at most 500 historical entries per form in one batch.'); }
            if ($entries) { $warnings[] = 'Historical entries are imported without sending notifications or integrations. Attachment URLs remain references; protected attachment copying is not yet supported.'; }
            $results[] = ['sourceId' => (string) ($form['id'] ?? hash('sha256', Database::json($form))), 'title' => sanitize_text_field($form['title'] ?? 'Imported Form ' . ($index + 1)), 'definition' => $definition, 'warnings' => array_values(array_unique($warnings)), 'entries' => $entries, 'entryCount' => count($entries)];
        }
        return ['forms' => $results];
    }
    public static function run(array $input): array
    {
        global $wpdb;
        $preview = self::preview($input); $created = []; $source = substr(sanitize_key($input['source'] ?? 'json_' . hash('sha256', Database::json($input))), 0, 80);
        foreach ($preview['forms'] as $form) {
            $existing = $wpdb->get_var($wpdb->prepare('SELECT target_id FROM ' . Database::table('imports') . ' WHERE source=%s AND source_id=%s AND kind=%s', $source, $form['sourceId'], 'form'));
            if ($existing) { if (!Permissions::can((int) $existing)) { throw new \RuntimeException('Import destination access denied.', 403); } $count = self::entries($source, $form, (int) $existing); $created[] = ['id' => (int) $existing, 'alreadyImported' => true, 'entriesImported' => $count]; continue; }
            $created[] = Database::transaction(static function () use ($wpdb, $source, $form) {
                $record = Repository::save(['title' => $form['title'], 'definition' => $form['definition'], 'status' => 'draft']);
                if (!$wpdb->insert(Database::table('imports'), ['source' => $source, 'source_id' => $form['sourceId'], 'target_id' => $record['id'], 'kind' => 'form', 'created_at' => Database::now()])) { throw new \RuntimeException('Could not record import mapping.'); }
                return ['id' => $record['id'], 'title' => $record['title'], 'warnings' => $form['warnings'], 'entriesImported' => self::entries($source, $form, $record['id'])];
            });
        }
        return ['forms' => $created];
    }
    private static function entries(string $source, array $form, int $formId): int
    {
        global $wpdb; $count = 0;
        foreach ($form['entries'] as $entry) {
            if (!is_array($entry)) { throw new \InvalidArgumentException('Invalid historical entry.'); }
            $sourceId = $form['sourceId'] . ':' . ($entry['id'] ?? hash('sha256', Database::json($entry)));
            if ($wpdb->get_var($wpdb->prepare('SELECT target_id FROM ' . Database::table('imports') . " WHERE source=%s AND source_id=%s AND kind='entry'", $source, $sourceId))) { continue; }
            Database::transaction(static function () use ($wpdb, $source, $sourceId, $form, $formId, $entry) {
                $values = $entry['response'] ?? []; if (is_string($values)) { $values = json_decode($values, true); }
                if (!is_array($values)) { throw new \InvalidArgumentException('Invalid historical entry answers.'); }
                $clean = static function ($value) use (&$clean) { return is_array($value) ? array_map($clean, $value) : sanitize_textarea_field((string) $value); };
                $values = Validation::withoutPasswords($form['definition']['fields'], $clean($values));
                $now = Database::now(); $timestamp = strtotime($entry['created_at'] ?? '');
                $ok = $wpdb->insert(Database::table('entries'), ['form_id' => $formId, 'form_version' => Repository::form($formId)['version'], 'status' => in_array($entry['status'] ?? '', ['read', 'unread', 'spam', 'trash'], true) ? $entry['status'] : 'read', 'response' => Database::json($values), 'notes' => '[]', 'user_id' => 0, 'source_url' => esc_url_raw($entry['source_url'] ?? ''), 'idempotency_key' => hash('sha256', $source . ':' . $sourceId), 'created_at' => $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : $now, 'updated_at' => $now]);
                if (!$ok) { throw new \RuntimeException('Could not import historical entry.'); }
                if (!$wpdb->insert(Database::table('imports'), ['source' => $source, 'source_id' => $sourceId, 'target_id' => $wpdb->insert_id, 'kind' => 'entry', 'created_at' => $now])) { throw new \RuntimeException('Could not record entry mapping.'); }
            });
            $count++;
        }
        return $count;
    }
    private static function fluentFields(array $fields, array &$warnings): array
    {
        $map = ['input_text' => 'text', 'textarea' => 'textarea', 'input_email' => 'email', 'input_number' => 'number', 'input_url' => 'url', 'input_password' => 'password', 'input_hidden' => 'hidden', 'select' => 'select', 'select_country' => 'country', 'input_radio' => 'radio', 'input_checkbox' => 'checkbox', 'multi_select' => 'multiselect', 'input_date' => 'date', 'input_name' => 'name', 'address' => 'address', 'input_file' => 'file', 'input_image' => 'image', 'phone' => 'phone', 'section_break' => 'section', 'custom_html' => 'html', 'terms_and_condition' => 'terms', 'gdpr_agreement' => 'gdpr', 'ratings' => 'rating', 'net_promoter_score' => 'nps', 'container' => 'container', 'form_step' => 'step'];
        $out = [];
        foreach ($fields as $index => $field) {
            $element = $field['element'] ?? ''; $type = $map[$element] ?? null;
            if (!$type) { $warnings[] = 'Unsupported Fluent Forms field: ' . $element; continue; }
            $settings = $field['settings'] ?? []; $attributes = $field['attributes'] ?? [];
            $name = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) ($attributes['name'] ?? 'field_' . substr(wp_generate_uuid4(), 0, 8)));
            if (!preg_match('/^[a-zA-Z]/', $name)) { $name = 'field_' . $name; }
            $converted = ['id' => 'import_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12), 'name' => $name, 'type' => $type, 'label' => $settings['label'] ?? ucfirst($type), 'required' => !empty($settings['validation_rules']['required']['value']), 'placeholder' => $attributes['placeholder'] ?? '', 'help' => $settings['help_message'] ?? '', 'default' => is_scalar($attributes['value'] ?? '') ? (string) ($attributes['value'] ?? '') : '', 'width' => 12, 'children' => [], 'options' => []];
            foreach ($settings['advanced_options'] ?? [] as $option) { $converted['options'][] = ['label' => (string) ($option['label'] ?? ''), 'value' => (string) ($option['value'] ?? $option['label'] ?? '')]; }
            if ($type === 'container') { foreach ($field['columns'] ?? [] as $column) { $children = self::fluentFields($column['fields'] ?? [], $warnings); foreach ($children as &$child) { $child['width'] = max(1, (int) round(12 / max(1, count($field['columns'])))); } unset($child); $converted['children'] = array_merge($converted['children'], $children); } }
            $converted['condition'] = FluentMigration::condition($settings['conditional_logics'] ?? [], $warnings, $converted['label']);
            foreach (['min', 'max'] as $bound) { if (isset($attributes[$bound]) && is_numeric($attributes[$bound])) { $converted[$bound] = (float) $attributes[$bound]; } }
            if ($type === 'html') { $converted['html'] = $settings['html_codes'] ?? ''; }
            if ($type === 'name' || $type === 'address') { $warnings[] = 'Review compound field parts and their individual validation: ' . $converted['label']; }
            $out[] = $converted;
        }
        return $out;
    }
}
