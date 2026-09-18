# Provider verification

No live provider account has been verified in this build. Configuration saved successfully means the credentials were stored and passed local format checks; it does not mean the provider accepted them.

| Handler | Current code | Live verification |
| --- | --- | --- |
| Generic webhook | HTTPS JSON, HMAC signature, stable delivery header | Pending; does not replace native integrations |
| Slack | Incoming-webhook message | Pending |
| Discord | Webhook message; mentions disabled | Pending |
| Telegram | Bot sendMessage | Pending |
| Mailchimp | Audience member upsert, pending opt-in | Pending |
| Brevo | Contact create/update and list mapping | Pending |
| HubSpot | Contact batch upsert by email | Pending |
| WordPress registration | Subscriber creation after workflow acceptance | Full behavior test pending |
| WordPress posts | Draft post creation for review | Full behavior test pending |
| Other reference connectors | Not implemented | Blocked on implementation and controlled accounts |
| All eight payment gateways | Contract/schema only; no checkout | Blocked on implementation and sandbox accounts |

The exact native connector inventory lives in `parity.json`, including the pinned documentation targets. UI controls are dedicated to the implemented handler's credentials and fields; the current field/operation sets still require expansion to match reference behavior.

For each provider, record: implementation version; sandbox/test account label (never its secret); provider API version; mapped fields and operation; successful request/resource identifiers; rejected credentials/fields; retry and duplicate behavior; conditional-feed behavior; deletion/disconnection; redacted log evidence. Payment records also need verified signed callbacks, duplicate/out-of-order events, renewals and the gateway's supported refunds/cancellations. Keep private evidence outside release ZIPs and reference a redacted report.

Official implementation references include Mailchimp's Marketing API audience member endpoints, Brevo's contacts API, HubSpot's CRM v3 contact upsert API, and the providers' incoming-webhook/Bot API documentation. Recheck current API contracts when credentials become available. No simulated provider response is counted as a completed native integration.
