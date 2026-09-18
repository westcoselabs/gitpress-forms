<?php
namespace GitPress\Forms;

final class Integrations
{
    public static function definitions(): array
    {
        return apply_filters('gitpress_forms/integrations', [
            'webhook' => ['name' => 'Webhooks', 'category' => 'Automation', 'credentials' => ['url' => 'HTTPS endpoint', 'secret' => 'Signing secret'], 'fields' => ['payload' => 'Message / payload'], 'description' => 'POST signed JSON to your own HTTPS endpoint.'],
            'slack' => ['name' => 'Slack', 'category' => 'Communication', 'credentials' => ['url' => 'Incoming webhook URL'], 'fields' => ['message' => 'Message'], 'description' => 'Send a message through a Slack incoming webhook.'],
            'discord' => ['name' => 'Discord', 'category' => 'Communication', 'credentials' => ['url' => 'Webhook URL'], 'fields' => ['message' => 'Message'], 'description' => 'Send a message to a Discord channel webhook.'],
            'telegram' => ['name' => 'Telegram', 'category' => 'Communication', 'credentials' => ['token' => 'Bot token', 'chat_id' => 'Chat ID'], 'fields' => ['message' => 'Message'], 'description' => 'Send messages through your Telegram bot.'],
            'mailchimp' => ['name' => 'Mailchimp', 'category' => 'Email Marketing', 'credentials' => ['key' => 'API key', 'list_id' => 'Audience ID'], 'fields' => ['email' => 'Email address', 'first_name' => 'First name', 'last_name' => 'Last name'], 'description' => 'Add or update an audience member with double opt-in.'],
            'brevo' => ['name' => 'Brevo', 'category' => 'Email Marketing', 'credentials' => ['key' => 'API key', 'list_id' => 'List ID'], 'fields' => ['email' => 'Email address', 'first_name' => 'First name', 'last_name' => 'Last name'], 'description' => 'Create or update a contact in your Brevo list.'],
            'hubspot' => ['name' => 'HubSpot', 'category' => 'CRM', 'credentials' => ['token' => 'Private app access token'], 'fields' => ['email' => 'Email address', 'first_name' => 'First name', 'last_name' => 'Last name', 'phone' => 'Phone'], 'description' => 'Create or update CRM contact properties by email.'],
            'wordpress_user' => ['name' => 'User Registration', 'category' => 'WordPress', 'credentials' => [], 'fields' => ['email' => 'Email address', 'username' => 'Username', 'first_name' => 'First name', 'last_name' => 'Last name'], 'description' => 'Create subscriber accounts after confirmation and approval.'],
            'wordpress_post' => ['name' => 'Post Creation', 'category' => 'WordPress', 'credentials' => [], 'fields' => ['title' => 'Post title', 'content' => 'Post content', 'excerpt' => 'Excerpt'], 'description' => 'Create a draft post for editorial review.'],
        ] + Extensions::integrations());
    }
    public static function catalog(): array
    {
        $connections = get_option('gitpress_forms_connections', []); $items = [];
        foreach (self::definitions() as $key => $definition) { $items[] = ['id' => $key, ...array_intersect_key($definition, array_flip(['name', 'category', 'description', 'credentials', 'fields'])), 'connected' => isset($connections[$key]) || !$definition['credentials']]; }
        return $items;
    }
    public static function saveConnection(string $provider, array $credentials): array
    {
        $definition = self::definitions()[$provider] ?? null;
        if (!$definition) { throw new \InvalidArgumentException('Integration not available.'); }
        $connections = get_option('gitpress_forms_connections', []);
        if (!empty($credentials['disconnect'])) { unset($connections[$provider]); update_option('gitpress_forms_connections', $connections, false); return ['connected' => false]; }
        $values = [];
        foreach ($definition['credentials'] as $key => $label) { $value = trim((string) ($credentials[$key] ?? '')); if ($value === '' && $key !== 'secret') { throw new \InvalidArgumentException($label . ' is required.'); } $values[$key] = $value; }
        if (isset($values['url'])) {
            if (!wp_http_validate_url($values['url']) || wp_parse_url($values['url'], PHP_URL_SCHEME) !== 'https') { throw new \InvalidArgumentException('Use a public HTTPS URL.'); }
            $host = wp_parse_url($values['url'], PHP_URL_HOST);
            if ($provider === 'slack' && !in_array($host, ['hooks.slack.com', 'hooks.slack-gov.com'], true)) { throw new \InvalidArgumentException('Use a Slack incoming webhook URL.'); }
            if ($provider === 'discord' && !in_array($host, ['discord.com', 'discordapp.com'], true)) { throw new \InvalidArgumentException('Use a Discord webhook URL.'); }
        }
        if ($provider === 'mailchimp' && !preg_match('/-([a-z]+\d+)$/', $values['key'])) { throw new \InvalidArgumentException('The Mailchimp key must include its data center suffix.'); }
        if ($extension = Extensions::integration($provider)) { $values = $extension->validateCredentials($values); }
        $connections[$provider] = Security::encrypt(Database::json($values)); update_option('gitpress_forms_connections', $connections, false);
        return ['connected' => true];
    }
    public static function saveFeed(int $formId, array $input, int $id = 0): array
    {
        global $wpdb;
        if (!Repository::form($formId)) { throw new \InvalidArgumentException('Form not found.'); }
        $provider = sanitize_key($input['provider'] ?? ''); $definition = self::definitions()[$provider] ?? null;
        if (!$definition) { throw new \InvalidArgumentException('Integration not available.'); }
        $configuration = ['mapping' => [], 'condition' => Definition::rule($input['configuration']['condition'] ?? [])];
        foreach ($definition['fields'] as $key => $label) { $configuration['mapping'][$key] = sanitize_textarea_field($input['configuration']['mapping'][$key] ?? ''); }
        $data = ['form_id' => $formId, 'provider' => $provider, 'title' => sanitize_text_field($input['title'] ?? $definition['name']), 'enabled' => !empty($input['enabled']) ? 1 : 0, 'configuration' => Database::json($configuration)];
        $ok = $id ? $wpdb->update(Database::table('feeds'), $data, ['id' => $id, 'form_id' => $formId]) : $wpdb->insert(Database::table('feeds'), $data);
        if ($ok === false) { throw new \RuntimeException('Could not save integration feed.'); }
        return ['id' => $id ?: (int) $wpdb->insert_id];
    }
    public static function execute(int $feedId, array $entry): void
    {
        global $wpdb;
        $feed = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Database::table('feeds') . ' WHERE id=%d AND enabled=1', $feedId), ARRAY_A);
        if (!$feed || (int) $feed['form_id'] !== $entry['form_id']) { return; }
        $provider = $feed['provider']; $definition = self::definitions()[$provider] ?? null;
        if (!$definition) { throw new \RuntimeException('Integration no longer available.'); }
        $config = json_decode($feed['configuration'], true); $data = [];
        foreach ($config['mapping'] as $key => $template) { $data[$key] = Submissions::smart($template, $entry['response']); }
        $connection = get_option('gitpress_forms_connections', [])[$provider] ?? '';
        $credentials = $connection ? json_decode(Security::decrypt($connection), true) : [];
        if ($definition['credentials'] && !$credentials) { throw new \RuntimeException('Integration is disconnected.'); }
        $headers = ['Content-Type' => 'application/json']; $method = 'POST';
        switch ($provider) {
            case 'webhook':
                $url = $credentials['url']; $body = ['event' => 'entry.accepted', 'id' => $entry['id'], 'form_id' => $entry['form_id'], 'data' => $entry['response'], 'message' => $data['payload'] ?? ''];
                if (!empty($credentials['secret'])) { $headers['X-GitPress-Signature'] = 'sha256=' . hash_hmac('sha256', Database::json($body), $credentials['secret']); }
                $headers['Idempotency-Key'] = 'gpf-' . $entry['id'] . '-' . $feedId; break;
            case 'slack': $url = $credentials['url']; $body = ['text' => $data['message']]; break;
            case 'discord': $url = $credentials['url']; $body = ['content' => substr($data['message'], 0, 2000), 'allowed_mentions' => ['parse' => []]]; break;
            case 'telegram': $url = 'https://api.telegram.org/bot' . rawurlencode($credentials['token']) . '/sendMessage'; $body = ['chat_id' => $credentials['chat_id'], 'text' => substr($data['message'], 0, 4096)]; break;
            case 'mailchimp':
                if (!is_email($data['email'])) { throw new \RuntimeException('Map a valid email address.'); }
                preg_match('/-([a-z]+\d+)$/', $credentials['key'], $m);
                $url = 'https://' . $m[1] . '.api.mailchimp.com/3.0/lists/' . rawurlencode($credentials['list_id']) . '/members/' . md5(strtolower($data['email'])); $method = 'PUT';
                $headers['Authorization'] = 'Basic ' . base64_encode('gitpress:' . $credentials['key']);
                $body = ['email_address' => $data['email'], 'status_if_new' => 'pending', 'merge_fields' => ['FNAME' => $data['first_name'], 'LNAME' => $data['last_name']]]; break;
            case 'brevo':
                if (!is_email($data['email'])) { throw new \RuntimeException('Map a valid email address.'); }
                $url = 'https://api.brevo.com/v3/contacts'; $headers['api-key'] = $credentials['key']; $body = ['email' => $data['email'], 'attributes' => ['FIRSTNAME' => $data['first_name'], 'LASTNAME' => $data['last_name']], 'listIds' => [(int) $credentials['list_id']], 'updateEnabled' => true]; break;
            case 'hubspot':
                if (!is_email($data['email'])) { throw new \RuntimeException('Map a valid email address.'); }
                $url = 'https://api.hubapi.com/crm/v3/objects/contacts/batch/upsert'; $headers['Authorization'] = 'Bearer ' . $credentials['token']; $body = ['inputs' => [['id' => $data['email'], 'idProperty' => 'email', 'properties' => ['email' => $data['email'], 'firstname' => $data['first_name'], 'lastname' => $data['last_name'], 'phone' => $data['phone']]]]]; break;
            case 'wordpress_user':
                if (get_users(['meta_key' => '_gpf_entry_id', 'meta_value' => $entry['id'], 'number' => 1, 'fields' => 'ID'])) { return; }
                if (!is_email($data['email']) || email_exists($data['email'])) { throw new \RuntimeException('A new, valid email address is required.'); }
                $result = wp_insert_user(['user_login' => sanitize_user($data['username'] ?: $data['email'], true), 'user_email' => $data['email'], 'user_pass' => wp_generate_password(24), 'role' => 'subscriber', 'first_name' => sanitize_text_field($data['first_name']), 'last_name' => sanitize_text_field($data['last_name']), 'meta_input' => ['_gpf_entry_id' => $entry['id']]]);
                if (is_wp_error($result)) { throw new \RuntimeException('User registration failed.'); }
                wp_new_user_notification($result, null, 'user'); return;
            case 'wordpress_post':
                if (get_posts(['post_type' => 'post', 'post_status' => 'any', 'meta_key' => '_gpf_entry_id', 'meta_value' => $entry['id'], 'numberposts' => 1])) { return; }
                $form = Repository::form($entry['form_id']);
                $result = wp_insert_post(['post_title' => sanitize_text_field($data['title']), 'post_content' => wp_kses_post($data['content']), 'post_excerpt' => sanitize_textarea_field($data['excerpt']), 'post_status' => 'draft', 'post_type' => 'post', 'post_author' => $form['created_by'], 'meta_input' => ['_gpf_entry_id' => $entry['id']]], true);
                if (is_wp_error($result)) { throw new \RuntimeException('Post creation failed.'); } return;
            default:
                if ($extension = Extensions::integration($provider)) { $extension->deliver($data, $credentials, $entry, 'gpf-' . $entry['id'] . '-' . $feedId); return; }
                if (is_callable($definition['deliver'] ?? null)) { ($definition['deliver'])($data, $credentials, $entry); return; }
                throw new \RuntimeException('Integration handler not found.');
        }
        $response = wp_safe_remote_request($url, ['method' => $method, 'headers' => $headers, 'body' => Database::json($body), 'timeout' => 20, 'redirection' => 0]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) { throw new \RuntimeException('Provider did not accept the request.'); }
    }
}
