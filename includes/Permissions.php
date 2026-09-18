<?php
namespace GitPress\Forms;

final class Permissions
{
    public static function can(int $formId, string $operation = 'edit'): bool
    {
        $capability = $operation === 'entries' ? 'view_gitpress_entries' : 'manage_gitpress_forms';
        if (!current_user_can($capability)) { return false; }
        $form = Repository::form($formId);
        if (!$form) { return false; }
        if (current_user_can('manage_options')) { return true; }
        $access = $form['definition']['settings']['access'] ?? [];
        if (empty($access['restricted'])) { return true; }
        $users = array_map('intval', $access[$operation === 'entries' ? 'entryUsers' : 'manageUsers'] ?? []);
        return in_array(get_current_user_id(), $users, true);
    }

    public static function ids(string $operation = 'edit'): array
    {
        return array_values(array_map(static fn ($form) => $form['id'], array_filter(Repository::forms(), static fn ($form) => self::can($form['id'], $operation))));
    }

    public static function sql(string $column, string $operation = 'entries'): string
    {
        $ids = self::ids($operation);
        return $column . ' IN (' . ($ids ? implode(',', array_map('intval', $ids)) : '0') . ')';
    }

    public static function request(\WP_REST_Request $request, string $capability): bool
    {
        if ($capability === 'public') { return true; }
        if (!current_user_can($capability)) { return false; }
        $path = $request->get_route(); $formId = 0;
        if (preg_match('~/forms/(\d+)~', $path, $match)) { $formId = (int) $match[1]; }
        elseif (preg_match('~/entries/(\d+)~', $path, $match)) { $formId = Repository::entry((int) $match[1])['form_id'] ?? -1; }
        elseif (preg_match('~/(feeds|jobs)/(\d+)~', $path, $match)) {
            global $wpdb;
            $table = Database::table($match[1]);
            if ($match[1] === 'feeds') { $formId = (int) $wpdb->get_var($wpdb->prepare("SELECT form_id FROM $table WHERE id=%d", $match[2])); }
            else { $formId = (int) $wpdb->get_var($wpdb->prepare("SELECT e.form_id FROM $table j JOIN " . Database::table('entries') . ' e ON e.id=j.entry_id WHERE j.id=%d', $match[2])); }
            if (!$formId) { return false; }
        } elseif (str_ends_with($path, '/entries') && !empty($request['form'])) { $formId = (int) $request['form']; }
        return !$formId || self::can($formId, in_array($capability, ['view_gitpress_entries', 'manage_gitpress_entries'], true) ? 'entries' : 'edit');
    }

    public static function roles(): array
    {
        $result = [];
        foreach (wp_roles()->roles as $slug => $role) {
            $result[] = ['id' => $slug, 'name' => translate_user_role($role['name']), 'forms' => !empty($role['capabilities']['manage_gitpress_forms']), 'entries' => !empty($role['capabilities']['view_gitpress_entries']), 'editEntries' => !empty($role['capabilities']['manage_gitpress_entries']), 'integrations' => !empty($role['capabilities']['manage_gitpress_integrations'])];
        }
        return $result;
    }

    public static function saveRoles(array $input): array
    {
        foreach ($input['roles'] ?? [] as $item) {
            $role = get_role(sanitize_key($item['id'] ?? ''));
            if (!$role || $item['id'] === 'administrator') { continue; }
            foreach (['forms' => 'manage_gitpress_forms', 'entries' => 'view_gitpress_entries', 'editEntries' => 'manage_gitpress_entries', 'integrations' => 'manage_gitpress_integrations'] as $key => $capability) { !empty($item[$key]) ? $role->add_cap($capability) : $role->remove_cap($capability); }
        }
        Database::log('permissions_changed', 'Role permissions changed by user ' . get_current_user_id());
        return self::roles();
    }
}
