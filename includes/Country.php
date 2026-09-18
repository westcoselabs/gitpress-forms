<?php
namespace GitPress\Forms;
final class Country
{
    public static function all(): array { return json_decode(file_get_contents(GPF_DIR . 'schema/countries.json'), true); }
    public static function options(): array
    {
        $data = self::all();
        return array_map(static fn ($code, $label) => ['value' => $code, 'label' => $label], array_keys($data), array_values($data));
    }
}
