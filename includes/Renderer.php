<?php
namespace GitPress\Forms;

final class Renderer
{
    private static int $instance = 0;
    public static function shortcode(array|string $atts = []): string
    {
        $atts = shortcode_atts(['id' => 0, 'popup' => ''], $atts, 'gitpress_form');
        $form = Repository::form(absint($atts['id']));
        if (!$form || $form['status'] !== 'published') { return current_user_can('manage_gitpress_forms') ? '<p class="gpf-notice">GitPress Forms: publish this form before embedding it.</p>' : ''; }
        $html = self::render($form);
        if ($atts['popup'] !== '') {
            $dialog = 'gpf-dialog-' . $form['id'] . '-' . self::$instance;
            return '<button type="button" class="gpf-popup-trigger" data-gpf-open="' . esc_attr($dialog) . '" aria-haspopup="dialog">' . esc_html($atts['popup']) . '</button><dialog id="' . esc_attr($dialog) . '" class="gpf-popup" aria-labelledby="' . esc_attr($dialog) . '-title"><div class="gpf-popup-heading"><h2 id="' . esc_attr($dialog) . '-title">' . esc_html($form['title']) . '</h2><button type="button" data-gpf-close aria-label="Close form">&#215;</button></div>' . $html . '</dialog>';
        }
        return $html;
    }
    public static function render(array $form, bool $preview = false): string
    {
        $def = $form['definition']; $settings = $def['settings']; $id = 'gpf-' . $form['id'] . '-' . (++self::$instance);
        if ($settings['requireLogin'] && !is_user_logged_in()) { return '<p class="gpf-notice">Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">sign in</a> to complete this form.</p>'; }
        wp_enqueue_script('gitpress-forms-frontend', GPF_URL . 'build/frontend.js', [], GPF_VERSION, true);
        // A nested GitPress shortcode may be discovered after wp_head. A scoped stylesheet
        // link in the fragment keeps that case styled without globally loading form assets.
        $html = '<link rel="stylesheet" href="' . esc_url(GPF_URL . 'build/frontend.css?ver=' . GPF_VERSION) . '">';
        $style = $def['style'];
        $vars = '--gpf-primary:' . $style['primary'] . ';--gpf-text:' . $style['text'] . ';--gpf-bg:' . $style['background'] . ';--gpf-border:' . $style['border'] . ';--gpf-radius:' . $style['radius'] . 'px;--gpf-gap:' . $style['gap'] . 'px;--gpf-font-size:' . $style['fontSize'] . 'px;max-width:' . $style['maxWidth'] . 'px';
        $html .= '<form id="' . esc_attr($id) . '" class="gpf-form gpf-label-' . esc_attr($style['labelPosition']) . '" style="' . esc_attr($vars) . '" data-gpf-form="' . (int) $form['id'] . '" novalidate>';
        $config = ['id' => $form['id'], 'instance' => $id, 'api' => rest_url('gitpress-forms/v1/'), 'sessionUrl' => admin_url('admin-ajax.php?action=gitpress_forms_session'), 'fields' => $def['fields'], 'settings' => array_intersect_key($settings, array_flip(['saveResume', 'trackPartial', 'mode', 'submitLabel'])), 'preview' => $preview];
        $html .= '<script type="application/json" class="gpf-config">' . wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
        $html .= '<div class="gpf-alert" role="status" aria-live="polite" tabindex="-1" hidden></div><div class="gpf-progress" hidden><span></span></div><div class="gpf-fields">';
        $html .= self::fields($def['fields'], $id);
        $html .= '</div><div class="gpf-trap" aria-hidden="true"><label>Leave this empty<input name="_gpf_website" type="text" tabindex="-1" autocomplete="off"></label></div>';
        $html .= '<div class="gpf-actions"><button type="button" class="gpf-back gpf-secondary" hidden>Back</button><button type="button" class="gpf-next" hidden>Next</button><button type="submit" class="gpf-submit">' . esc_html($settings['submitLabel']) . '</button>';
        if ($settings['saveResume']) { $html .= '<button type="button" class="gpf-save gpf-secondary">Save &amp; Resume</button>'; }
        $html .= '<span class="gpf-step-label" aria-live="polite"></span></div><noscript><p>Please enable JavaScript to submit this form.</p></noscript></form>';
        if (!empty($settings['css'])) { $html .= '<style>' . str_ireplace('</style', '<\/style', $settings['css']) . '</style>'; }
        // Scripts are authored only by users with unfiltered_html; never evaluated from submissions.
        if (!empty($settings['js'])) { $html .= '<script>(function(form){' . str_ireplace('</script', '<\/script', $settings['js']) . '})(document.getElementById(' . wp_json_encode($id) . '));</script>'; }
        if (did_action('wp_print_footer_scripts') && !wp_script_is('gitpress-forms-frontend', 'done')) { $html .= '<script defer src="' . esc_url(GPF_URL . 'build/frontend.js?ver=' . GPF_VERSION) . '"></script>'; }
        return $html;
    }
    private static function fields(array $fields, string $instance, string $prefix = ''): string
    {
        $html = '';
        foreach ($fields as $field) {
            $type = $field['type']; $name = $prefix ? $prefix . '[' . $field['name'] . ']' : $field['name']; $id = $instance . '-' . $field['id'];
            $wrapper = '<div class="gpf-field gpf-type-' . esc_attr($type) . '" data-field="' . esc_attr($field['name']) . '" data-type="' . esc_attr($type) . '" style="grid-column:span ' . (int) $field['width'] . '"';
            if ($type === 'hidden') { $wrapper .= ' hidden'; }
            $html .= $wrapper . '>';
            if (in_array($type, ['container', 'step', 'accordion', 'repeat'], true)) {
                if ($type === 'accordion') { $html .= '<details open><summary>' . esc_html($field['label']) . '</summary>'; }
                elseif ($field['label']) { $html .= '<h3 class="gpf-section-title">' . esc_html($field['label']) . '</h3>'; }
                if ($type === 'repeat') {
                    $placeholder = '__INDEX_' . $field['id'] . '__';
                    $html .= '<div class="gpf-repeat-rows"></div><template class="gpf-repeat-template" data-index-placeholder="' . esc_attr($placeholder) . '"><div class="gpf-repeat-row"><div class="gpf-fields">' . self::fields($field['children'], $id . '-' . $placeholder, $name . '[' . $placeholder . ']') . '</div><button type="button" class="gpf-remove-row gpf-secondary">Remove row</button></div></template><button type="button" class="gpf-add-row gpf-secondary">Add row</button>';
                } else { $html .= '<div class="gpf-fields">' . self::fields($field['children'], $id, $prefix) . '</div>'; }
                if ($type === 'accordion') { $html .= '</details>'; }
                $html .= '</div>'; continue;
            }
            if ($type === 'section') { $html .= '<h3 class="gpf-section-title">' . esc_html($field['label']) . '</h3><p>' . esc_html($field['help']) . '</p></div>'; continue; }
            if ($type === 'html') { $html .= wp_kses_post($field['html'] ?? '') . '</div>'; continue; }
            if ($type === 'shortcode') {
                // Only explicitly registered safe inner shortcodes may run; forms cannot run arbitrary plugin actions.
                $allowed = apply_filters('gitpress_forms/allowed_field_shortcodes', []);
                $text = $field['default'];
                if ($allowed && preg_match('/^\[([a-zA-Z0-9_-]+)\b/', $text, $m) && in_array($m[1], $allowed, true)) { $html .= do_shortcode($text); }
                $html .= '</div>'; continue;
            }
            if ($type !== 'hidden') { $html .= '<label class="gpf-label" for="' . esc_attr($id) . '">' . esc_html($field['label']) . ($field['required'] ? ' <span class="gpf-required" aria-hidden="true">*</span>' : '') . '</label>'; }
            $inputName = $prefix ? $prefix . '[' . $field['name'] . ']' : $field['name'];
            $attrs = ' id="' . esc_attr($id) . '" name="' . esc_attr($inputName) . '" aria-describedby="' . esc_attr($id) . '-help ' . esc_attr($id) . '-error" placeholder="' . esc_attr($field['placeholder']) . '"' . ($field['required'] ? ' required aria-required="true"' : '');
            $default = $field['default'];
            if (preg_match('/^\{query:([a-zA-Z0-9_-]+)\}$/', $default, $m)) { $default = isset($_GET[$m[1]]) && is_scalar($_GET[$m[1]]) ? sanitize_text_field(wp_unslash($_GET[$m[1]])) : ''; }
            if ($default === '{user.email}') { $default = wp_get_current_user()->user_email; }
            if ($type === 'recaptcha') {
                $siteKey = Recaptcha::siteKey();
                $theme = ($field['extension']['theme'] ?? '') === 'dark' ? 'dark' : 'light';
                $size = ($field['extension']['size'] ?? '') === 'compact' ? 'compact' : 'normal';
                $html .= '<div id="' . esc_attr($id) . '" class="gpf-recaptcha" role="group" aria-label="' . esc_attr($field['label']) . '" data-sitekey="' . esc_attr($siteKey) . '" data-theme="' . esc_attr($theme) . '" data-size="' . esc_attr($size) . '" tabindex="-1"></div><input type="hidden" name="' . esc_attr($inputName) . '">';
                if ($siteKey === '') { $html .= '<p class="gpf-recaptcha-config">reCAPTCHA needs a site key in GitPress Forms Global Settings.</p>'; }
            }
            elseif ($extension = Extensions::field($type)) { $extension->enqueueAssets(); $html .= $extension->render($field, $inputName, $id); }
            elseif ($type === 'richtext') {
                $html .= '<div class="gpf-rich-toolbar" role="toolbar" aria-label="Text formatting"><button type="button" data-format="bold" aria-label="Bold"><strong>B</strong></button><button type="button" data-format="italic" aria-label="Italic"><em>I</em></button><button type="button" data-format="insertUnorderedList" aria-label="Bullet list">List</button><button type="button" data-format="removeFormat">Clear formatting</button></div><div id="' . esc_attr($id) . '" class="gpf-rich-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="' . esc_attr($field['label']) . '">' . wp_kses_post($default) . '</div><textarea name="' . esc_attr($inputName) . '" hidden>' . esc_textarea($default) . '</textarea>';
            }
            elseif ($type === 'textarea') { $html .= '<textarea' . $attrs . ' rows="4">' . esc_textarea($default) . '</textarea>'; }
            elseif ($type === 'ranking') {
                $html .= '<ol class="gpf-ranking" aria-label="' . esc_attr($field['label']) . '">';
                foreach ($field['options'] as $option) { $html .= '<li draggable="true" data-value="' . esc_attr($option['value']) . '"><span>' . esc_html($option['label']) . '</span><button type="button" data-rank="up" aria-label="Move ' . esc_attr($option['label']) . ' up">↑</button><button type="button" data-rank="down" aria-label="Move ' . esc_attr($option['label']) . ' down">↓</button></li>'; }
                $html .= '</ol>';
            }
            elseif (in_array($type, ['rating', 'nps'], true)) {
                $html .= '<div class="gpf-scores ' . ($type === 'rating' ? 'gpf-stars' : '') . '" role="radiogroup" aria-label="' . esc_attr($field['label']) . '">';
                for ($score = $type === 'rating' ? 1 : 0; $score <= ($type === 'rating' ? ($field['max'] ?? 5) : 10); $score++) { $html .= '<label><input type="radio" name="' . esc_attr($inputName) . '" value="' . $score . '" aria-label="' . $score . '"' . checked($default, $score, false) . '><span aria-hidden="true">' . ($type === 'rating' ? '★' : $score) . '</span></label>'; }
                $html .= '</div>';
            }
            elseif (in_array($type, ['select', 'multiselect', 'country'], true)) {
                $multiple = $type === 'multiselect';
                if ($multiple) { $attrs = str_replace('name="' . esc_attr($inputName) . '"', 'name="' . esc_attr($inputName) . '[]"', $attrs); }
                $html .= '<select' . $attrs . ($multiple ? ' multiple' : '') . '><option value="">' . esc_html($field['placeholder'] ?: 'Select an option') . '</option>';
                $options = $field['options'];
                if ($type === 'country') { $options = Country::options(); }
                foreach ($options as $option) { $html .= '<option value="' . esc_attr($option['value']) . '"' . selected($default, $option['value'], false) . '>' . esc_html($option['label']) . '</option>'; }
                $html .= '</select>';
            } elseif (in_array($type, ['radio', 'checkbox', 'terms', 'gdpr'], true)) {
                $options = in_array($type, ['terms', 'gdpr'], true) ? [['value' => '1', 'label' => $field['help'] ?: 'I agree']] : $field['options'];
                $html .= '<div class="gpf-options" role="group" aria-label="' . esc_attr($field['label']) . '">';
                foreach ($options as $index => $option) { $html .= '<label><input type="' . ($type === 'radio' ? 'radio' : 'checkbox') . '" id="' . esc_attr($id . ($index ? '-' . $index : '')) . '" name="' . esc_attr($inputName . ($type === 'checkbox' ? '[]' : '')) . '" value="' . esc_attr($option['value']) . '"' . checked($default, $option['value'], false) . '> <span>' . esc_html($option['label']) . '</span></label>'; }
                $html .= '</div>';
            } elseif (in_array($type, ['name', 'address'], true)) {
                $parts = $type === 'name' ? ['first' => 'First name', 'last' => 'Last name'] : ['street' => 'Street address', 'city' => 'City', 'state' => 'State / Region', 'postal' => 'Postal code', 'country' => 'Country'];
                $html .= '<div class="gpf-compound">';
                foreach ($parts as $key => $label) { $html .= '<label><span>' . esc_html($label) . '</span><input type="text" id="' . esc_attr($id . ($key === array_key_first($parts) ? '' : '-' . $key)) . '" name="' . esc_attr($inputName . '[' . $key . ']') . '"' . ($field['required'] ? ' required' : '') . '></label>'; }
                $html .= '</div>';
            } elseif ($type === 'grid') {
                $html .= '<div class="gpf-table-scroll"><table><thead><tr><th></th>';
                foreach ($field['options'] as $option) { $html .= '<th scope="col">' . esc_html($option['label']) . '</th>'; }
                $html .= '</tr></thead><tbody>';
                foreach ($field['rows'] as $index => $label) { $html .= '<tr><th scope="row">' . esc_html($label) . '</th>'; foreach ($field['options'] as $option) { $html .= '<td><input type="radio" aria-label="' . esc_attr($label . ': ' . $option['label']) . '" name="' . esc_attr($inputName . '[' . $index . ']') . '" value="' . esc_attr($option['value']) . '"></td>'; } $html .= '</tr>'; }
                $html .= '</tbody></table></div>';
            } elseif (in_array($type, ['file', 'image'], true)) {
                $html .= '<input type="file" id="' . esc_attr($id) . '" data-upload="' . esc_attr($inputName) . '" accept="' . esc_attr($type === 'image' ? '.jpg,.jpeg,.png,.webp' : $field['accept']) . '"><input type="hidden" name="' . esc_attr($inputName) . '"><span class="gpf-upload-status" aria-live="polite"></span>';
            } elseif ($type === 'signature') {
                $html .= '<canvas class="gpf-signature" width="600" height="150" aria-label="Draw your signature"></canvas><input type="hidden" name="' . esc_attr($inputName) . '"><button type="button" class="gpf-clear-signature gpf-secondary">Clear signature</button>';
            } else {
                $inputType = match ($type) { 'email' => 'email', 'phone' => 'tel', 'url' => 'url', 'password' => 'password', 'hidden' => 'hidden', 'number', 'calculation' => 'number', 'slider' => 'range', 'date' => $field['dateMode'] ?? 'date', 'color' => 'color', default => 'text' };
                $html .= '<input type="' . $inputType . '"' . $attrs . ' value="' . esc_attr($default) . '"' . ($type === 'calculation' ? ' readonly' : '');
                if (in_array($inputType, ['number', 'range'], true)) { $html .= ' step="any"'; foreach (['min', 'max'] as $key) { if (isset($field[$key])) { $html .= ' ' . $key . '="' . esc_attr($field[$key]) . '"'; } } }
                if ($type === 'mask') { $html .= ' data-mask="' . esc_attr($field['mask'] ?? '999-999-9999') . '"'; }
                foreach (['minLength', 'maxLength'] as $key) { if (isset($field[$key])) { $html .= ' ' . strtolower($key) . '="' . (int) $field[$key] . '"'; } }
                $html .= '>';
            }
            $html .= '<small id="' . esc_attr($id) . '-help" class="gpf-help">' . esc_html($field['help']) . '</small><span id="' . esc_attr($id) . '-error" class="gpf-field-error" aria-live="polite"></span></div>';
        }
        return $html;
    }
}
