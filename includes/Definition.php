<?php
namespace GitPress\Forms;

final class Definition
{
    public static function catalog(): array { return array_merge(json_decode(file_get_contents(GPF_DIR . 'schema/fields.json'), true), Extensions::fields()); }
    public static function empty(): array
    {
        return ['schemaVersion' => 1, 'fields' => [], 'settings' => ['submitLabel' => 'Submit Form', 'confirmation' => 'Thank you! Your submission has been received.', 'redirect' => '', 'requireLogin' => false, 'maxEntries' => 0, 'opensAt' => '', 'closesAt' => '', 'approval' => false, 'doubleOptin' => false, 'saveResume' => false, 'mode' => 'standard', 'retentionDays' => 0, 'notifications' => [], 'confirmations' => [], 'css' => '', 'js' => ''], 'style' => ['primary' => '#3865e9', 'text' => '#263348', 'background' => '#ffffff', 'border' => '#dce1eb', 'radius' => 6, 'gap' => 20, 'fontSize' => 15, 'maxWidth' => 780, 'labelPosition' => 'top']];
    }
    public static function flatten(array $fields): array
    {
        $result = [];
        foreach ($fields as $field) { $result[] = $field; $result = array_merge($result, self::flatten($field['children'] ?? [])); }
        return $result;
    }
    public static function validate(array $input): array
    {
        if (($input['schemaVersion'] ?? null) !== 1 || !isset($input['fields']) || !is_array($input['fields'])) { throw new \InvalidArgumentException('Unsupported form definition version.'); }
        if (strlen(Database::json($input)) > 1024 * 1024) { throw new \InvalidArgumentException('Form definition is too large.'); }
        $types = array_column(self::catalog(), 'type'); $ids = []; $names = []; $count = 0;
        $walk = function (array $fields, int $depth = 0) use (&$walk, &$ids, &$names, &$count, $types): array {
            if ($depth > 8) { throw new \InvalidArgumentException('Maximum layout nesting is 8 levels.'); }
            $result = [];
            foreach ($fields as $field) {
                if (++$count > 300 || !is_array($field) || !in_array($field['type'] ?? '', $types, true)) { throw new \InvalidArgumentException('Unsupported field or too many fields.'); }
                $id = (string) ($field['id'] ?? ''); $name = (string) ($field['name'] ?? '');
                if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id) || isset($ids[$id])) { throw new \InvalidArgumentException('Each field needs a unique ID.'); }
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/', $name) || isset($names[$name])) { throw new \InvalidArgumentException('Field names must be unique letters, numbers, and underscores.'); }
                $ids[$id] = true; $names[$name] = true;
                $clean = ['id' => $id, 'name' => $name, 'type' => $field['type'], 'label' => sanitize_text_field($field['label'] ?? ''), 'required' => !empty($field['required']), 'placeholder' => sanitize_text_field($field['placeholder'] ?? ''), 'help' => sanitize_text_field($field['help'] ?? ''), 'default' => sanitize_textarea_field($field['default'] ?? ''), 'width' => max(1, min(12, (int) ($field['width'] ?? 12))), 'options' => [], 'children' => []];
                foreach (array_slice($field['options'] ?? [], 0, 1000) as $option) { $clean['options'][] = ['label' => sanitize_text_field($option['label'] ?? ''), 'value' => sanitize_text_field($option['value'] ?? '')]; }
                $clean['children'] = $walk($field['children'] ?? [], $depth + 1);
                if ($extension = Extensions::field($clean['type'])) {
                    $clean['extension'] = [];
                    foreach ($extension->settingsSchema() as $key => $schema) {
                        $value = $field['extension'][$key] ?? $schema['default'] ?? null;
                        if ($value === null) { continue; }
                        $valid = rest_validate_value_from_schema($value, $schema, $key);
                        if (is_wp_error($valid)) { throw new \InvalidArgumentException('Invalid extension setting: ' . $key); }
                        $clean['extension'][$key] = rest_sanitize_value_from_schema($value, $schema);
                    }
                }
                if (!empty($field['condition'])) { $clean['condition'] = self::rule($field['condition']); }
                foreach (['min', 'max'] as $key) { if (isset($field[$key]) && is_numeric($field[$key])) { $clean[$key] = (float) $field[$key]; } }
                if ($clean['type'] === 'rating') { $clean['min'] = 1; $clean['max'] = max(1, min(10, (int) ($field['max'] ?? 5))); }
                if ($clean['type'] === 'nps') { $clean['min'] = 0; $clean['max'] = 10; }
                $clean['mask'] = substr(sanitize_text_field($field['mask'] ?? '999-999-9999'), 0, 100);
                $clean['dateMode'] = in_array($field['dateMode'] ?? '', ['date', 'time', 'datetime-local'], true) ? $field['dateMode'] : 'date';
                foreach (['minLength', 'maxLength'] as $key) { if (isset($field[$key])) { $clean[$key] = max(0, min(100000, (int) $field[$key])); } }
                foreach (['multiple', 'unique'] as $key) { $clean[$key] = !empty($field[$key]); }
                $clean['accept'] = sanitize_text_field($field['accept'] ?? '.pdf,.jpg,.jpeg,.png,.webp,.txt,.csv');
                $clean['rows'] = array_map('sanitize_text_field', array_slice($field['rows'] ?? [], 0, 50));
                $clean['html'] = wp_kses_post($field['html'] ?? '');
                $clean['formula'] = sanitize_text_field($field['formula'] ?? '0');
                if ($clean['type'] === 'calculation') { Logic::calculate($clean['formula'], [], true); }
                $result[] = $clean;
            }
            return $result;
        };
        $output = self::empty(); $output['fields'] = $walk($input['fields']);
        // Reject missing references and cycles before the form can be published.
        $graph = [];
        $dependencies = function (array $fields, array $ancestors = []) use (&$dependencies, &$graph, $names): void { foreach ($fields as $field) {
            $deps = array_merge($ancestors, array_column($field['condition']['rules'] ?? [], 'field'));
            if ($field['type'] === 'calculation') { preg_match_all('/\{([a-zA-Z][\w]*)\}/', $field['formula'], $m); $deps = array_merge($deps, $m[1]); }
            foreach ($deps as $dep) { if (!isset($names[$dep])) { throw new \InvalidArgumentException('A formula or rule references a missing field: ' . $dep); } }
            $graph[$field['name']] = $deps;
            $dependencies($field['children'], $deps);
        } };
        $dependencies($output['fields']);
        $visited = []; $active = [];
        $visit = function ($name) use (&$visit, &$visited, &$active, $graph): void { if (isset($active[$name])) { throw new \InvalidArgumentException('Circular field dependencies are not supported.'); } if (isset($visited[$name])) { return; } $active[$name] = true; foreach ($graph[$name] ?? [] as $dep) { $visit($dep); } unset($active[$name]); $visited[$name] = true; };
        foreach (array_keys($graph) as $name) { $visit($name); }
        $settings = $input['settings'] ?? [];
        $pdf = $settings['pdf'] ?? [];
        $output['settings']['pdf'] = ['title' => sanitize_text_field($pdf['title'] ?? ''), 'intro' => sanitize_textarea_field($pdf['intro'] ?? ''), 'footer' => sanitize_textarea_field($pdf['footer'] ?? ''), 'paper' => ($pdf['paper'] ?? '') === 'letter' ? 'letter' : 'A4'];
        $entryView = $settings['entryView'] ?? [];
        $output['settings']['entryView'] = ['enabled' => !empty($entryView['enabled']), 'restricted' => !empty($entryView['restricted']), 'intro' => sanitize_textarea_field($entryView['intro'] ?? ''), 'background' => sanitize_hex_color($entryView['background'] ?? '') ?: '#f5f7fb'];
        $access = $settings['access'] ?? [];
        $output['settings']['access'] = ['restricted' => !empty($access['restricted']), 'manageUsers' => array_values(array_unique(array_filter(array_map('intval', array_slice($access['manageUsers'] ?? [], 0, 1000))))), 'entryUsers' => array_values(array_unique(array_filter(array_map('intval', array_slice($access['entryUsers'] ?? [], 0, 1000)))))];
        foreach (['submitLabel', 'confirmation', 'opensAt', 'closesAt'] as $key) { $output['settings'][$key] = sanitize_textarea_field($settings[$key] ?? $output['settings'][$key]); }
        $output['settings']['redirect'] = esc_url_raw($settings['redirect'] ?? '');
        foreach (['requireLogin', 'approval', 'doubleOptin', 'saveResume', 'trackPartial'] as $key) { $output['settings'][$key] = !empty($settings[$key]); }
        foreach (['maxEntries', 'retentionDays'] as $key) { $output['settings'][$key] = max(0, (int) ($settings[$key] ?? 0)); }
        $output['settings']['mode'] = ($settings['mode'] ?? '') === 'conversational' ? 'conversational' : 'standard';
        foreach (array_slice($settings['notifications'] ?? [], 0, 30) as $notification) {
            foreach (['cc', 'bcc', 'replyTo'] as $header) { $notification[$header] = sanitize_text_field($notification[$header] ?? ''); }
            $output['settings']['notifications'][] = ['attachPdf' => !empty($notification['attachPdf']), 'id' => sanitize_key($notification['id'] ?? wp_generate_uuid4()), 'to' => sanitize_text_field($notification['to'] ?? ''), 'subject' => sanitize_text_field($notification['subject'] ?? ''), 'message' => wp_kses_post($notification['message'] ?? ''), 'condition' => self::rule($notification['condition'] ?? [])];
            $index = array_key_last($output['settings']['notifications']);
            foreach (['cc', 'bcc', 'replyTo'] as $header) { $output['settings']['notifications'][$index][$header] = $notification[$header]; }
        }
        foreach (array_slice($settings['confirmations'] ?? [], 0, 30) as $confirmation) { $output['settings']['confirmations'][] = ['condition' => self::rule($confirmation['condition'] ?? []), 'message' => sanitize_textarea_field($confirmation['message'] ?? ''), 'redirect' => esc_url_raw($confirmation['redirect'] ?? '')]; }
        foreach (['css', 'js'] as $key) {
            if (!empty($settings[$key]) && !current_user_can('unfiltered_html')) { throw new \InvalidArgumentException('Custom CSS and JavaScript require unfiltered_html permission.'); }
            $output['settings'][$key] = (string) ($settings[$key] ?? '');
        }
        foreach (['primary', 'text', 'background', 'border'] as $key) { $output['style'][$key] = sanitize_hex_color($input['style'][$key] ?? '') ?: $output['style'][$key]; }
        foreach (['radius' => [0, 40], 'gap' => [0, 80], 'fontSize' => [12, 30], 'maxWidth' => [280, 2000]] as $key => [$min, $max]) { $output['style'][$key] = max($min, min($max, (int) ($input['style'][$key] ?? $output['style'][$key]))); }
        $output['style']['labelPosition'] = ($input['style']['labelPosition'] ?? '') === 'left' ? 'left' : 'top';
        return $output;
    }
    public static function rule(array $rule): array
    {
        $rules = [];
        foreach (array_slice($rule['rules'] ?? [], 0, 50) as $item) {
            if (!in_array($item['operator'] ?? '', ['eq', 'neq', 'contains', 'gt', 'gte', 'lt', 'lte', 'empty', 'not_empty'], true)) { throw new \InvalidArgumentException('Unknown condition operator.'); }
            $rules[] = ['field' => sanitize_text_field($item['field'] ?? ''), 'operator' => $item['operator'], 'value' => sanitize_text_field($item['value'] ?? '')];
        }
        return ['mode' => ($rule['mode'] ?? '') === 'any' ? 'any' : 'all', 'rules' => $rules];
    }
}
