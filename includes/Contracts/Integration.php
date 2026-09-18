<?php
namespace GitPress\Forms\Contracts;

interface Integration
{
    /** id, name, category, description, credentials (key => label), fields (key => label). */
    public function metadata(): array;
    public function validateCredentials(array $credentials): array;
    /** Throw on failure. Use the stable delivery key for provider-side idempotency. */
    public function deliver(array $mapping, array $credentials, array $entry, string $deliveryKey): void;
}
