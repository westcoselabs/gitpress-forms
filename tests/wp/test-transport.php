<?php
// Installed as an MU plugin ONLY by the isolated test setup. Never included in the ZIP.
if (!is_file(ABSPATH . '.gitpress-test-sandbox')) { return; }
add_filter('pre_wp_mail', static fn () => true);
add_filter('wp_is_application_passwords_available', '__return_true');
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if (str_contains($url, 'gitpress-forms-test/fixture')) { return new WP_Error('test_offline', 'Controlled GitHub outage for stale-cache acceptance test.'); }
    return $pre;
}, 10, 3);
