<?php
namespace GitPress\Forms;

final class Security
{
    public static function origin(): bool
    {
        $origin = get_http_origin();
        if (!$origin) { return true; }
        $normalize = static function (string $url): string { $parts = wp_parse_url($url); return strtolower(($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '')) . (isset($parts['port']) ? ':' . $parts['port'] : ''); };
        return in_array($normalize($origin), [$normalize(home_url()), $normalize(site_url())], true);
    }
    public static function token(int $formId): string
    {
        $body = $formId . '.' . (time() + 3600) . '.' . bin2hex(random_bytes(12));
        return $body . '.' . hash_hmac('sha256', $body, wp_salt('nonce'));
    }
    public static function checkToken(string $token, int $formId): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 4 || (int) $parts[0] !== $formId || (int) $parts[1] < time() || (int) $parts[1] > time() + 3600) { return false; }
        return hash_equals(hash_hmac('sha256', implode('.', array_slice($parts, 0, 3)), wp_salt('nonce')), $parts[3]);
    }
    public static function rateLimit(string $action, int $limit = 40): void
    {
        global $wpdb;
        $key = hash_hmac('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . floor(time() / 60), wp_salt('auth'));
        $table = Database::table('tokens');
        // Each minute has exactly $limit unique slots. INSERT IGNORE atomically claims a slot.
        for ($slot = 0; $slot < $limit; $slot++) {
            $claimed = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (token_hash,purpose,form_id,payload,expires_at,created_at) VALUES (%s,'rate',0,'',%s,%s)", hash('sha256', $key . '|' . $slot), gmdate('Y-m-d H:i:s', time() + 120), Database::now()));
            if ($claimed === 1) { return; }
            if ($claimed === false) { throw new \RuntimeException('Could not establish a submission session. Please retry.'); }
        }
        throw new \RuntimeException('Too many requests. Please wait a minute.', 429);
    }
    public static function issue(string $purpose, int $formId, array $payload, int $seconds): string
    {
        global $wpdb;
        $token = bin2hex(random_bytes(32));
        if (!$wpdb->insert(Database::table('tokens'), ['token_hash' => hash('sha256', $token), 'purpose' => $purpose, 'form_id' => $formId, 'payload' => Database::json($payload), 'expires_at' => gmdate('Y-m-d H:i:s', time() + $seconds), 'created_at' => Database::now()])) { throw new \RuntimeException('Could not create secure link.'); }
        return $token;
    }
    public static function lookup(string $token, string $purpose, int $formId): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Database::table('tokens') . ' WHERE token_hash=%s AND purpose=%s AND form_id=%d AND expires_at>%s', hash('sha256', $token), $purpose, $formId, Database::now()), ARRAY_A);
        if ($row) { $row['payload'] = json_decode($row['payload'], true); }
        return $row;
    }
    public static function encrypt(string $plaintext): string
    {
        $key = hash('sha256', wp_salt('auth'), true);
        if (function_exists('sodium_crypto_secretbox')) { $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); return 's1:' . base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, $key)); }
        if (!function_exists('openssl_encrypt')) { throw new \RuntimeException('Sodium or OpenSSL is required to protect connection credentials.'); }
        $nonce = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) { throw new \RuntimeException('Could not protect connection credentials.'); }
        return 'g1:' . base64_encode($nonce . $tag . $cipher);
    }
    public static function decrypt(string $encrypted): string
    {
        $key = hash('sha256', wp_salt('auth'), true); $data = base64_decode(substr($encrypted, 3), true);
        if ($data === false) { throw new \RuntimeException('Invalid stored credentials.'); }
        if (str_starts_with($encrypted, 's1:') && function_exists('sodium_crypto_secretbox_open')) { $plain = sodium_crypto_secretbox_open(substr($data, 24), substr($data, 0, 24), $key); }
        elseif (str_starts_with($encrypted, 'g1:') && function_exists('openssl_decrypt')) { $plain = openssl_decrypt(substr($data, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($data, 0, 12), substr($data, 12, 16)); }
        else { throw new \RuntimeException('Stored credential cipher is not available.'); }
        if ($plain === false) { throw new \RuntimeException('Connection credentials must be reconnected after changing WordPress salts.'); }
        return $plain;
    }
}
