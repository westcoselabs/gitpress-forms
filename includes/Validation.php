<?php
namespace GitPress\Forms;

final class Validation
{
    public static function withoutPasswords(array $fields, array $values): array
    {
        foreach ($fields as $field) {
            if ($field['type'] === 'password') { unset($values[$field['name']]); }
            elseif ($field['type'] === 'repeat') { foreach ($values[$field['name']] ?? [] as $i => $row) { $values[$field['name']][$i] = self::withoutPasswords($field['children'], $row); } }
            elseif (!empty($field['children'])) { $values = self::withoutPasswords($field['children'], $values); }
        }
        return $values;
    }
    public static function values(array $fields, array $input, bool $partial = false, array $outer = []): array
    {
        $values = []; $errors = []; $flat = [];
        $expand = function ($fields, array $ancestors = []) use (&$expand, &$flat) { foreach ($fields as $field) { if (in_array($field['type'], ['container', 'step', 'accordion'], true)) { $expand($field['children'], [...$ancestors, $field['condition'] ?? []]); } else { $field['_ancestors'] = $ancestors; $flat[] = $field; } } };
        $expand($fields);
        foreach ($flat as $f) {
            $name = $f['name']; $raw = $input[$name] ?? '';
            if (in_array($f['type'], ['section', 'html', 'shortcode'], true)) { continue; }
            if ($f['type'] === 'repeat') { $values[$name] = is_array($raw) ? array_slice($raw, 0, 100) : []; continue; }
            if (in_array($f['type'], ['checkbox', 'multiselect', 'ranking', 'grid', 'name', 'address'], true)) { $values[$name] = is_array($raw) ? array_map(static fn ($v) => is_scalar($v) ? sanitize_text_field((string) $v) : '', array_slice($raw, 0, 100, true)) : []; }
            elseif ($f['type'] === 'richtext') { $values[$name] = is_string($raw) ? wp_kses_post($raw) : ''; }
            else { $values[$name] = is_scalar($raw) ? sanitize_textarea_field((string) $raw) : ''; }
        }
        // Resolve dependencies in topological order so hidden input cannot affect formulas or downstream rules.
        $byName = array_column($flat, null, 'name'); $resolved = []; $active = [];
        $resolve = function (string $name) use (&$resolve, &$values, &$errors, &$resolved, &$active, $byName, $partial, $outer): void {
            if (isset($resolved[$name]) || !isset($byName[$name])) { return; }
            if (isset($active[$name])) { $errors[$name] = 'Circular field dependency.'; return; }
            $active[$name] = true; $f = $byName[$name];
            $deps = array_column($f['condition']['rules'] ?? [], 'field');
            foreach ($f['_ancestors'] as $ancestor) { $deps = array_merge($deps, array_column($ancestor['rules'] ?? [], 'field')); }
            if ($f['type'] === 'calculation') { preg_match_all('/\{([a-zA-Z][\w]*)\}/', $f['formula'], $matches); $deps = array_merge($deps, $matches[1]); }
            foreach ($deps as $dep) { $resolve($dep); }
            $visible = Logic::matches($f['condition'] ?? null, array_merge($outer, $values));
            foreach ($f['_ancestors'] as $ancestor) { $visible = $visible && Logic::matches($ancestor, array_merge($outer, $values)); }
            if (!$visible) { unset($values[$name]); }
            elseif ($f['type'] === 'calculation') { try { $values[$name] = Logic::calculate($f['formula'], array_merge($outer, $values)); } catch (\Throwable $e) { $errors[$name] = $e->getMessage(); } }
            $resolved[$name] = true; unset($active[$name]);
        };
        foreach (array_keys($byName) as $name) { $resolve($name); }
        // Container conditions suppress every descendant, including required validation.
        $prune = function ($fields) use (&$prune, &$values, $outer) { foreach ($fields as $f) { if (in_array($f['type'], ['container', 'step', 'accordion'], true)) { if (!Logic::matches($f['condition'] ?? null, array_merge($outer, $values))) { foreach (Definition::flatten($f['children']) as $child) { unset($values[$child['name']]); } } else { $prune($f['children']); } } } };
        $prune($fields);
        foreach ($flat as $f) {
            $name = $f['name']; if (!array_key_exists($name, $values)) { continue; } $value = $values[$name]; $type = $f['type'];
            $empty = is_array($value) ? !array_filter($value, static fn ($v) => $v !== '') : ($type === 'richtext' ? trim(wp_strip_all_tags($value)) === '' : $value === '');
            if (!$partial && !empty($f['required']) && $empty) { $errors[$name] = $f['label'] . ' is required.'; continue; }
            if ($empty) { continue; }
            if ($extension = Extensions::field($type)) { $result = $extension->validate($f, $value, array_merge($outer, $values), $partial); $values[$name] = $result['value'] ?? ''; if (!empty($result['error'])) { $errors[$name] = (string) $result['error']; } continue; }
            if (in_array($type, ['name', 'address'], true)) {
                $parts = $type === 'name' ? ['first', 'last'] : ['street', 'city', 'state', 'postal', 'country'];
                $values[$name] = array_intersect_key($value, array_flip($parts));
                if (!$partial && $f['required']) { foreach ($parts as $part) { if (empty($value[$part])) { $errors[$name] = 'Complete every part of ' . $f['label'] . '.'; break; } } }
            }
            if ($type === 'repeat') {
                $rows = [];
                foreach ($value as $index => $row) { $validated = self::values($f['children'], is_array($row) ? $row : [], $partial, array_merge($outer, $values)); $rows[] = $validated['values']; foreach ($validated['errors'] as $key => $error) { $errors[$name . '.' . $index . '.' . $key] = $error; } }
                $values[$name] = $rows;
            }
            if ($type === 'email' && !is_email($value)) { $errors[$name] = 'Enter a valid email address.'; }
            if ($type === 'url' && !wp_http_validate_url($value)) { $errors[$name] = 'Enter a valid public HTTP or HTTPS URL.'; }
            if ($type === 'color' && !preg_match('/^#[0-9a-f]{6}$/i', $value)) { $errors[$name] = 'Choose a valid color.'; }
            if (in_array($type, ['number', 'calculation', 'rating', 'nps', 'slider'], true)) {
                if (!is_numeric($value) || !is_finite((float) $value)) { $errors[$name] = 'Enter a valid number.'; }
                else { $values[$name] = (float) $value; if (isset($f['min']) && (float) $value < $f['min']) { $errors[$name] = 'Value is below the minimum.'; } if (isset($f['max']) && (float) $value > $f['max']) { $errors[$name] = 'Value exceeds the maximum.'; } }
            }
            if (in_array($type, ['select', 'radio', 'checkbox', 'multiselect', 'ranking'], true)) {
                $allowed = array_column($f['options'], 'value');
                foreach (is_array($value) ? $value : [$value] as $choice) { if (!in_array($choice, $allowed, true)) { $errors[$name] = 'Select one of the available options.'; } }
            }
            if (in_array($type, ['terms', 'gdpr'], true) && $value !== '1') { $errors[$name] = 'Please accept this agreement.'; }
            if ($type === 'date') { $format = match ($f['dateMode'] ?? 'date') { 'time' => 'H:i', 'datetime-local' => 'Y-m-d\TH:i', default => 'Y-m-d' }; $date = \DateTimeImmutable::createFromFormat('!' . $format, (string) $value); if (!$date || $date->format($format) !== $value) { $errors[$name] = 'Enter a valid date or time.'; } }
            if ($type === 'country' && !array_key_exists((string) $value, Country::all())) { $errors[$name] = 'Choose a valid country.'; }
            if ($type === 'ranking' && count(array_unique($value)) !== count($value)) { $errors[$name] = 'Each option may appear only once.'; }
            if ($type === 'grid') {
                foreach ($value as $row => $answer) { if (!array_key_exists($row, $f['rows']) || !in_array($answer, array_column($f['options'], 'value'), true)) { $errors[$name] = 'Choose a valid answer for each row.'; } }
                if (!$partial && $f['required'] && count(array_filter($value, static fn ($v) => $v !== '')) !== count($f['rows'])) { $errors[$name] = 'Complete every row.'; }
            }
            if ($type === 'signature' && (!str_starts_with((string) $value, 'data:image/png;base64,') || !($bytes = base64_decode(substr($value, 22), true)) || !str_starts_with($bytes, "\x89PNG\r\n\x1a\n"))) { $errors[$name] = 'Please draw a valid signature.'; }
            if ($type === 'mask') { $pattern = ''; foreach (str_split($f['mask'] ?? '999-999-9999') as $character) { $pattern .= match ($character) { '9' => '[0-9]', 'a' => '[A-Za-z]', '*' => '[A-Za-z0-9]', default => preg_quote($character, '/') }; } if (!preg_match('/^' . $pattern . '$/D', $value)) { $errors[$name] = 'Use the expected format: ' . $f['mask']; } }
            if (in_array($type, ['rating', 'nps'], true) && ((float) $value !== floor((float) $value) || (float) $value < ($type === 'rating' ? 1 : 0) || (float) $value > ($type === 'rating' ? ($f['max'] ?? 5) : 10))) { $errors[$name] = 'Choose one of the available scores.'; }
            if (is_string($value)) { $length = function_exists('mb_strlen') ? mb_strlen(wp_strip_all_tags($value)) : strlen(strip_tags($value)); if (isset($f['minLength']) && $length < $f['minLength']) { $errors[$name] = 'This answer is too short.'; } if (isset($f['maxLength']) && $length > $f['maxLength']) { $errors[$name] = 'This answer is too long.'; } }
            if (is_string($value) && strlen($value) > 100000) { $errors[$name] = 'This value is too long.'; }
        }
        return ['values' => $values, 'errors' => $errors];
    }
}
