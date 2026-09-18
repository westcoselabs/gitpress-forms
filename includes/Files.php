<?php
namespace GitPress\Forms;

final class Files
{
    public static function upload(int $formId, string $fieldName, array $upload): array
    {
        global $wpdb;
        $form = Repository::form($formId); $field = null;
        foreach (Definition::flatten($form['definition']['fields']) as $candidate) { if ($candidate['name'] === $fieldName && in_array($candidate['type'], ['file', 'image'], true)) { $field = $candidate; break; } }
        if (!$field) { throw new \InvalidArgumentException('This form does not accept that upload.'); }
        if (($upload['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name']) || $upload['size'] > 10 * 1024 * 1024) { throw new \InvalidArgumentException('Upload failed. Maximum file size is 10 MB.'); }
        $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
        $safe = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'csv', 'docx', 'xlsx'];
        $allowed = array_map(static fn ($v) => ltrim(trim($v), '.'), explode(',', $field['accept']));
        if (!in_array($extension, $safe, true) || !in_array($extension, $allowed, true)) { throw new \InvalidArgumentException('This file type is not allowed.'); }
        $check = wp_check_filetype_and_ext($upload['tmp_name'], $upload['name']);
        if (!$check['type'] || !$check['ext']) { throw new \InvalidArgumentException('The file content does not match its extension.'); }
        if ($field['type'] === 'image' && !wp_get_image_mime($upload['tmp_name'])) { throw new \InvalidArgumentException('Upload a valid image.'); }
        $uploads = wp_upload_dir(); $directory = $uploads['basedir'] . '/gitpress-forms-private';
        if (!wp_mkdir_p($directory)) { throw new \RuntimeException('Private upload directory could not be created.'); }
        $token = bin2hex(random_bytes(32)); $path = $directory . '/' . bin2hex(random_bytes(24)) . '.enc';
        if (file_put_contents($path, Security::encrypt(file_get_contents($upload['tmp_name'])), LOCK_EX) === false) { throw new \RuntimeException('Could not store upload.'); }
        $ok = $wpdb->insert(Database::table('files'), ['form_id' => $formId, 'entry_id' => 0, 'token_hash' => hash('sha256', $token), 'field_name' => $fieldName, 'original_name' => sanitize_file_name($upload['name']), 'path' => $path, 'mime' => $check['type'], 'bytes' => $upload['size'], 'created_at' => Database::now()]);
        if (!$ok) { wp_delete_file($path); throw new \RuntimeException('Could not record upload.'); }
        return ['token' => $token, 'name' => sanitize_file_name($upload['name']), 'bytes' => $upload['size']];
    }
    public static function claim(int $formId, int $entryId, array $fields, array $values): void
    {
        global $wpdb;
        foreach ($fields as $field) {
            if (in_array($field['type'], ['file', 'image'], true) && !empty($values[$field['name']])) {
                $ok = $wpdb->update(Database::table('files'), ['entry_id' => $entryId], ['token_hash' => hash('sha256', $values[$field['name']]), 'field_name' => $field['name'], 'form_id' => $formId, 'entry_id' => 0]);
                if (!$ok) { throw new \InvalidArgumentException('An upload is missing or already submitted. Please upload it again.'); }
            }
            if ($field['type'] === 'repeat') { foreach ($values[$field['name']] ?? [] as $row) { self::claim($formId, $entryId, $field['children'], $row); } }
            elseif (!empty($field['children'])) { self::claim($formId, $entryId, $field['children'], $values); }
        }
    }
    public static function deleteEntry(int $id): void
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare('SELECT path FROM ' . Database::table('files') . ' WHERE entry_id=%d', $id), ARRAY_A);
        foreach ($rows as $row) { wp_delete_file($row['path']); }
        foreach (['files', 'jobs', 'logs', 'entry_revisions'] as $table) { $wpdb->delete(Database::table($table), ['entry_id' => $id]); }
        $tokens = $wpdb->get_results($wpdb->prepare('SELECT id,payload FROM ' . Database::table('tokens') . ' WHERE payload LIKE %s', '%"entryId":' . $wpdb->esc_like((string) $id) . '%'), ARRAY_A);
        foreach ($tokens as $token) { if ((int) (json_decode($token['payload'], true)['entryId'] ?? 0) === $id) { $wpdb->delete(Database::table('tokens'), ['id' => $token['id']]); } }
        $wpdb->delete(Database::table('entries'), ['id' => $id]);
    }
}
