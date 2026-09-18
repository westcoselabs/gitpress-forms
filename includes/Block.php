<?php
namespace GitPress\Forms;

final class Block
{
    public static function render(array $attributes): string
    {
        $form = Repository::form(absint($attributes['id'] ?? 0));
        if (!$form || $form['status'] !== 'published') { return ''; }
        foreach (['primary', 'background'] as $color) { if (!empty($attributes[$color]) && sanitize_hex_color($attributes[$color])) { $form['definition']['style'][$color] = $attributes[$color]; } }
        if (isset($attributes['radius'])) { $form['definition']['style']['radius'] = max(0, min(40, (int) $attributes['radius'])); }
        if (isset($attributes['maxWidth'])) { $form['definition']['style']['maxWidth'] = max(280, min(2000, (int) $attributes['maxWidth'])); }
        return '<div ' . get_block_wrapper_attributes() . '>' . Renderer::render($form) . '</div>';
    }
}
