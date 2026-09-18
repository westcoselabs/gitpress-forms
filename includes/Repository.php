<?php
namespace GitPress\Forms;

final class Repository
{
    public static function formForEntry(array $entry): ?array
    {
        global $wpdb; $form = self::form($entry['form_id']);
        if (!$form) { return null; }
        $definition = $wpdb->get_var($wpdb->prepare('SELECT definition FROM ' . Database::table('revisions') . ' WHERE form_id=%d AND version=%d', $entry['form_id'], $entry['form_version']));
        if ($definition) { $form['definition'] = json_decode($definition, true); }
        return $form;
    }
    public static function form(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Database::table('forms') . ' WHERE id=%d', $id), ARRAY_A);
        if (!$row) { return null; }
        $row['id'] = (int) $row['id']; $row['version'] = (int) $row['version']; $row['definition'] = json_decode($row['definition'], true);
        return $row;
    }
    public static function forms(string $search = ''): array
    {
        global $wpdb;
        $f = Database::table('forms'); $e = Database::table('entries');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT f.id,f.title,f.slug,f.status,f.version,f.created_at,f.updated_at,(SELECT COUNT(*) FROM $e e WHERE e.form_id=f.id AND e.status NOT IN ('trash','spam','partial')) AS entries_count FROM $f f WHERE f.title LIKE %s ORDER BY f.updated_at DESC LIMIT 500", '%' . $wpdb->esc_like($search) . '%'), ARRAY_A);
        return array_map(static function ($r) { $r['id'] = (int) $r['id']; $r['version'] = (int) $r['version']; $r['entries_count'] = (int) $r['entries_count']; return $r; }, $rows);
    }
    public static function save(array $input, int $id = 0): array
    {
        global $wpdb;
        $definition = Definition::validate($input['definition'] ?? Definition::empty());
        $oldAccess = $id ? (self::form($id)['definition']['settings']['access'] ?? ['restricted' => false, 'manageUsers' => [], 'entryUsers' => []]) : ['restricted' => false, 'manageUsers' => [], 'entryUsers' => []];
        if (!current_user_can('manage_options') && $definition['settings']['access'] !== $oldAccess) { throw new \RuntimeException('Only site administrators may change form access.', 403); }
        $title = sanitize_text_field($input['title'] ?? 'Untitled Form');
        if ($title === '') { throw new \InvalidArgumentException('Enter a form title.'); }
        $status = in_array($input['status'] ?? '', ['draft', 'published', 'trash'], true) ? $input['status'] : 'draft';
        return Database::transaction(static function () use ($wpdb, $definition, $title, $status, $id, $input) {
            $table = Database::table('forms'); $now = Database::now();
            if ($id) {
                $old = self::form($id);
                if (!$old) { throw new \InvalidArgumentException('Form not found.'); }
                if ((int) ($input['version'] ?? 0) !== $old['version']) { throw new \RuntimeException('This form was updated elsewhere. Reload before saving.', 409); }
                $version = $old['version'] + 1;
                $changed = $wpdb->update($table, ['title' => $title, 'status' => $status, 'definition' => Database::json($definition), 'version' => $version, 'updated_at' => $now], ['id' => $id, 'version' => $old['version']]);
                if ($changed !== 1) { throw new \RuntimeException('Concurrent form update. Reload before saving.', 409); }
            } else {
                $version = 1;
                $slug = sanitize_title($title) . '-' . substr(wp_generate_uuid4(), 0, 8);
                if (!$wpdb->insert($table, ['title' => $title, 'slug' => $slug, 'status' => $status, 'definition' => Database::json($definition), 'version' => 1, 'created_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now])) { throw new \RuntimeException('Could not create form.'); }
                $id = (int) $wpdb->insert_id;
            }
            if (!$wpdb->insert(Database::table('revisions'), ['form_id' => $id, 'version' => $version, 'definition' => Database::json($definition), 'created_by' => get_current_user_id(), 'created_at' => $now])) { throw new \RuntimeException('Could not save form revision.'); }
            Database::log('form_saved', 'Saved form ' . $id . ' revision ' . $version);
            Database::afterCommit(static fn () => do_action('gitpress_forms/form_saved', $id, $definition));
            return self::form($id);
        });
    }
    public static function entry(int $id): ?array
    {
        global $wpdb;
        $entry = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Database::table('entries') . ' WHERE id=%d', $id), ARRAY_A);
        if ($entry) { $entry['id'] = (int) $entry['id']; $entry['version'] = (int) $entry['version']; $entry['form_id'] = (int) $entry['form_id']; $entry['response'] = json_decode($entry['response'], true); $entry['notes'] = json_decode($entry['notes'], true) ?: []; }
        return $entry;
    }
    public static function entries(int $formId = 0, string $search = '', string $status = '', int $page = 1, bool $checkAccess = false): array
    {
        global $wpdb;
        $table = Database::table('entries'); $where = '1=1'; $args = [];
        if ($checkAccess) { $where .= ' AND ' . Permissions::sql('form_id'); }
        if ($formId) { $where .= ' AND form_id=%d'; $args[] = $formId; }
        if ($status !== '') { $where .= ' AND status=%s'; $args[] = $status; }
        if ($search !== '') { $where .= ' AND response LIKE %s'; $args[] = '%' . $wpdb->esc_like($search) . '%'; }
        $query = "SELECT id FROM $table WHERE $where ORDER BY id DESC LIMIT 50 OFFSET %d";
        $ids = $wpdb->get_col($wpdb->prepare($query, ...[...$args, max(0, $page - 1) * 50]));
        $totalQuery = "SELECT COUNT(*) FROM $table WHERE $where";
        $total = (int) $wpdb->get_var($args ? $wpdb->prepare($totalQuery, ...$args) : $totalQuery);
        return ['items' => array_map([self::class, 'entry'], $ids), 'total' => $total, 'page' => $page];
    }
}
