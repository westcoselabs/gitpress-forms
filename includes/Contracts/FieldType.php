<?php
namespace GitPress\Forms\Contracts;

interface FieldType
{
    /** Stable identifier, label, group, and container flag for the field palette. */
    public function metadata(): array;
    /** JSON Schema properties for extension-specific builder settings. */
    public function settingsSchema(): array;
    /** Return ['value' => mixed, 'error' => string|null]. Never trust client values. */
    public function validate(array $field, mixed $value, array $context, bool $partial): array;
    /** Return escaped public HTML. The surrounding field label and error container are provided. */
    public function render(array $field, string $inputName, string $elementId): string;
    public function enqueueAssets(): void;
}
