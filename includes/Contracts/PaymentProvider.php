<?php
namespace GitPress\Forms\Contracts;

interface PaymentProvider
{
    /** Explicit booleans for oneTime, recurring, refunds, cancellation and sandbox. */
    public function capabilities(): array;
    public function metadata(): array;
    public function validateCredentials(array $credentials): array;
    /** Server-priced order, never a browser-supplied amount. Return provider reference and checkout URL/client data. */
    public function createCheckout(array $order, array $credentials, string $idempotencyKey): array;
    /** Fetch authoritative provider status and amounts using private credentials. */
    public function fetchPayment(string $reference, array $credentials): array;
    /** Validate the raw callback signature and return a normalized event. Throw on invalid input. */
    public function verifyCallback(string $body, array $headers, array $credentials): array;
    public function refund(string $reference, int $minorUnits, array $credentials, string $idempotencyKey): array;
    public function cancelSubscription(string $reference, array $credentials, string $idempotencyKey): array;
}
