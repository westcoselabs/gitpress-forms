<?php
namespace GitPress\Forms;

/** Converts documented export data; it does not load or execute the source plugin. */
final class FluentMigration
{
    public static function smart(string $value): string
    {
        return preg_replace('/\{inputs\.([a-zA-Z][\w]*)\}/', '{$1}', str_replace('{all_data}', '{all_fields}', $value));
    }

    public static function condition(array $input, array &$warnings, string $label): ?array
    {
        if (empty($input['status'])) { return null; }
        $operators = ['=' => 'eq', '!=' => 'neq', '>' => 'gt', '>=' => 'gte', '<' => 'lt', '<=' => 'lte', 'contains' => 'contains', 'is_null' => 'empty', 'not_null' => 'not_empty'];
        $rules = [];
        foreach ($input['conditions'] ?? [] as $rule) {
            if (!isset($operators[$rule['operator'] ?? '']) || !preg_match('/^[a-zA-Z]\w*$/', $rule['field'] ?? '')) { $warnings[] = 'Unmapped conditional rule on ' . $label . '. Review before publishing.'; continue; }
            $rules[] = ['field' => $rule['field'], 'operator' => $operators[$rule['operator']], 'value' => (string) ($rule['value'] ?? '')];
        }
        return $rules ? ['mode' => ($input['type'] ?? '') === 'any' ? 'any' : 'all', 'rules' => $rules] : null;
    }

    public static function settings(array $form, array $raw, array &$definition, array &$warnings): void
    {
        $meta = [];
        foreach ($form['metas'] ?? $form['form_meta'] ?? [] as $item) { $value = $item['value'] ?? null; $meta[$item['meta_key'] ?? ''][] = is_string($value) ? (json_decode($value, true) ?? $value) : $value; }
        $settings = $meta['formSettings'][0] ?? []; $confirmation = $settings['confirmation'] ?? [];
        $definition['settings']['submitLabel'] = $raw['submitButton']['settings']['button_ui']['text'] ?? 'Submit Form';
        $definition['settings']['confirmation'] = self::smart($confirmation['messageToShow'] ?? $definition['settings']['confirmation']);
        if (($confirmation['redirectTo'] ?? '') === 'customUrl') { $definition['settings']['redirect'] = $confirmation['customUrl'] ?? ''; }
        if (($confirmation['redirectTo'] ?? '') === 'customPage') { $warnings[] = 'Destination page IDs must be reselected on this site.'; }
        $restriction = $settings['restrictions'] ?? [];
        $definition['settings']['requireLogin'] = !empty($restriction['requireLogin']['enabled']);
        if (!empty($restriction['limitNumberOfEntries']['enabled'])) {
            if (($restriction['limitNumberOfEntries']['period'] ?? 'total') === 'total') { $definition['settings']['maxEntries'] = (int) ($restriction['limitNumberOfEntries']['numberOfEntries'] ?? 0); }
            else { $warnings[] = 'Recurring entry-limit periods need manual configuration.'; }
        }
        if (!empty($restriction['scheduleForm']['enabled'])) {
            foreach (['start' => 'opensAt', 'end' => 'closesAt'] as $from => $to) { $definition['settings'][$to] = $restriction['scheduleForm'][$from] ?? ''; }
            if (count($restriction['scheduleForm']['selectedDays'] ?? []) < 7) { $warnings[] = 'Weekday restrictions are not converted.'; }
        }
        $definition['style']['labelPosition'] = ($settings['layout']['labelPlacement'] ?? '') === 'left' ? 'left' : 'top';
        $color = $raw['submitButton']['settings']['background_color'] ?? '';
        if (sanitize_hex_color($color)) { $definition['style']['primary'] = $color; }
        $definition['settings']['doubleOptin'] = ($meta['double_optin_settings'][0]['status'] ?? '') === 'yes';
        if ($definition['settings']['doubleOptin']) { $warnings[] = 'Double opt-in enabled. Review email template and skip rules; the source template is not converted.'; }
        foreach ($meta['notifications'] ?? [] as $index => $notification) {
            if (!is_array($notification) || (isset($notification['enabled']) && !$notification['enabled'])) { continue; }
            if (($notification['sendTo']['type'] ?? 'email') !== 'email') { $warnings[] = 'Notification routing requires review; only direct recipient lists are converted.'; continue; }
            $definition['settings']['notifications'][] = ['id' => 'import_notification_' . $index, 'to' => self::smart($notification['sendTo']['email'] ?? ''), 'subject' => self::smart($notification['subject'] ?? 'Form submission'), 'message' => self::smart($notification['message'] ?? '{all_fields}'), 'condition' => self::condition($notification['conditionals'] ?? [], $warnings, 'notification')];
        }
        foreach ($meta['confirmation_settings'] ?? [] as $confirmation) {
            $condition = self::condition($confirmation['conditionals'] ?? [], $warnings, 'confirmation');
            if ($condition) { $definition['settings']['confirmations'][] = ['condition' => $condition, 'message' => self::smart($confirmation['messageToShow'] ?? ''), 'redirect' => $confirmation['customUrl'] ?? '']; }
        }
        $handled = ['formSettings', 'notifications', 'confirmation_settings', 'double_optin_settings', 'template_name', '_total_views'];
        foreach ($meta as $key => $value) {
            if (in_array($key, $handled, true)) { continue; }
            if ($key === 'advancedValidationSettings' && empty($value[0]['status'])) { continue; }
            $warnings[] = 'Unmapped setting: ' . $key . '. Source configuration remains intact.';
        }
        if (!empty($form['appearance_settings'])) { $warnings[] = 'Advanced source styling requires review; label placement and button color are converted.'; }
        $warnings[] = 'Provider credentials and feeds are not imported. Reconnect accounts and verify mappings.';
        if (!empty($form['has_payment']) || !empty($form['payments'])) { $warnings[] = 'Payment behavior and historical transactions are not imported. No charges or subscriptions will be created.'; }
    }
}
