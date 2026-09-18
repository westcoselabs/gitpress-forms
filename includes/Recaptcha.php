<?php
namespace GitPress\Forms;

final class Recaptcha
{
    private const SITE_KEY_OPTION = 'gitpress_forms_recaptcha_site_key';
    private const SECRET_OPTION = 'gitpress_forms_recaptcha_secret';

    public static function settings(): array
    {
        $siteKey = (string) get_option(self::SITE_KEY_OPTION, '');
        return [
            'recaptchaSiteKey' => $siteKey,
            'recaptchaConfigured' => $siteKey !== '' && (string) get_option(self::SECRET_OPTION, '') !== '',
        ];
    }

    public static function save(array $input): array
    {
        if (!empty($input['clearRecaptcha'])) {
            delete_option(self::SITE_KEY_OPTION);
            delete_option(self::SECRET_OPTION);
            return self::settings();
        }
        if (array_key_exists('recaptchaSiteKey', $input)) {
            $siteKey = trim(sanitize_text_field($input['recaptchaSiteKey']));
            if ($siteKey !== '' && !preg_match('/^[A-Za-z0-9_-]{20,255}$/D', $siteKey)) {
                throw new \InvalidArgumentException('Enter a valid reCAPTCHA site key.');
            }
            update_option(self::SITE_KEY_OPTION, $siteKey, false);
        }
        $secret = trim((string) ($input['recaptchaSecret'] ?? ''));
        if ($secret !== '') {
            if (!preg_match('/^[A-Za-z0-9_-]{20,255}$/D', $secret)) { throw new \InvalidArgumentException('Enter a valid reCAPTCHA secret key.'); }
            update_option(self::SECRET_OPTION, Security::encrypt($secret), false);
        }
        return self::settings();
    }

    public static function siteKey(): string
    {
        return (string) get_option(self::SITE_KEY_OPTION, '');
    }

    public static function verify(array $fields, array $values, int $formId): array
    {
        $errors = [];
        self::verifyFields($fields, $values, $formId, $errors);
        return $errors;
    }

    private static function verifyFields(array $fields, array $values, int $formId, array &$errors, string $prefix = ''): void
    {
        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'repeat') {
                foreach ($values[$field['name']] ?? [] as $index => $row) { self::verifyFields($field['children'] ?? [], is_array($row) ? $row : [], $formId, $errors, $prefix . $field['name'] . '.' . $index . '.'); }
                continue;
            }
            if (!empty($field['children'])) { self::verifyFields($field['children'], $values, $formId, $errors, $prefix); continue; }
            if (($field['type'] ?? '') !== 'recaptcha' || !array_key_exists($field['name'], $values)) { continue; }
            $errorKey = $prefix . $field['name'];
            $token = is_string($values[$field['name']]) ? trim($values[$field['name']]) : '';
            if ($token === '') { $errors[$errorKey] = 'Please complete the reCAPTCHA challenge.'; continue; }
            if (strlen($token) > 4096) { $errors[$errorKey] = 'The reCAPTCHA response is invalid.'; continue; }
            $secretValue = (string) get_option(self::SECRET_OPTION, '');
            if (self::siteKey() === '' || $secretValue === '') { throw new \RuntimeException('reCAPTCHA is not configured. Ask a site administrator to add the site and secret keys.'); }
            $override = apply_filters('gitpress_forms/recaptcha_verification', null, $token, $formId, $field);
            if (is_bool($override)) {
                if (!$override) { $errors[$errorKey] = 'reCAPTCHA verification failed. Please try again.'; }
                continue;
            }
            try { $secret = Security::decrypt($secretValue); }
            catch (\Throwable) { throw new \RuntimeException('The reCAPTCHA secret could not be read. Reconnect it in Global Settings.'); }
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'timeout' => 10,
                'redirection' => 0,
                'body' => ['secret' => $secret, 'response' => $token],
            ]);
            if (is_wp_error($response)) { throw new \RuntimeException('reCAPTCHA verification is temporarily unavailable. Please try again.'); }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $status = wp_remote_retrieve_response_code($response);
            $expectedHost = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
            $verifiedHost = strtolower((string) ($body['hostname'] ?? ''));
            $testKey = self::siteKey() === '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' && defined('WP_DEBUG') && WP_DEBUG;
            if ($status !== 200 || !is_array($body) || empty($body['success']) || (!$testKey && $expectedHost !== '' && !hash_equals($expectedHost, $verifiedHost))) {
                $errors[$errorKey] = 'reCAPTCHA verification failed. Please try again.';
            }
        }
    }
}
