<?php
namespace GitPress\Forms;

use GitPress\Forms\Contracts\{FieldType, Integration, PaymentProvider};

final class Extensions
{
    private static array $fields = [];
    private static array $integrations = [];
    private static array $payments = [];

    public static function registerField(FieldType $field): void
    {
        $id = self::identifier($field->metadata()['type'] ?? '');
        $builtins = json_decode(file_get_contents(GPF_DIR . 'schema/fields.json'), true);
        if (in_array($id, array_column($builtins, 'type'), true) || isset(self::$fields[$id])) { throw new \InvalidArgumentException('Field identifier already registered.'); }
        self::$fields[$id] = $field;
    }
    public static function registerIntegration(Integration $integration): void
    {
        $id = self::identifier($integration->metadata()['id'] ?? '');
        if (isset(self::$integrations[$id])) { throw new \InvalidArgumentException('Integration identifier already registered.'); }
        self::$integrations[$id] = $integration;
    }
    public static function registerPayment(PaymentProvider $provider): void
    {
        $id = self::identifier($provider->metadata()['id'] ?? '');
        if (isset(self::$payments[$id])) { throw new \InvalidArgumentException('Payment identifier already registered.'); }
        self::$payments[$id] = $provider;
    }
    public static function field(string $id): ?FieldType { return self::$fields[$id] ?? null; }
    public static function integration(string $id): ?Integration { return self::$integrations[$id] ?? null; }
    public static function payments(): array { return self::$payments; }
    public static function fields(): array
    {
        return array_map(static fn ($field) => $field->metadata() + ['group' => 'Advanced', 'container' => false, 'settingsSchema' => $field->settingsSchema()], array_values(self::$fields));
    }
    public static function integrations(): array
    {
        $result = []; foreach (self::$integrations as $id => $integration) { $result[$id] = $integration->metadata(); } return $result;
    }
    private static function identifier(string $id): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]{2,60}$/D', $id)) { throw new \InvalidArgumentException('Use a stable lowercase extension identifier.'); }
        return $id;
    }
}
