# Webhooks

Userli supports webhooks to notify external services about user-related events.
This allows for seamless integration with other systems and automation of workflows.

!!! info

    We provide a webhook listener to inform Nextcloud and Synapse about user changes.
    See the `userli-webhook-listener` repository for more information: <https://github.com/systemli/userli-webhook-listener/>

## Supported Events

The following events can trigger webhooks:

- `user.created`: Triggered when a new user is created.
- `user.deleted`: Triggered when a user is deleted.
- `user.reset`: Triggered when a user is reset (password, MailCrypt keys, recovery token, and 2FA settings).

## Configuring Webhooks

To configure webhooks create an endpoint via UI: Settings → Webhooks (`/settings/webhooks`).
You need to provide the following information:

- **URL**: The endpoint URL where the webhook payload will be sent.
- **Secret**: A secret key used to sign the webhook payload for security purposes.
- **Events**: Select at least one event that should trigger the webhook (at least one is required by validation).
- **Domains**: Optionally restrict the webhook to specific domains. If no domains are selected, the webhook will fire for events from all domains.
- **Enabled**: Enable or disable the webhook.

## Domain Filtering

Webhook endpoints can optionally be scoped to one or more domains. This is useful when you have multiple domains and want to send events for only a subset of them to a particular endpoint.

- **No domains selected** (default): The endpoint receives events for users on **all** domains. This is backward compatible and suitable for global integrations (e.g., Dovecot).
- **One or more domains selected**: The endpoint only receives events for users belonging to those domains. For example, you might configure the `userli-webhook-listener` for Nextcloud/Matrix to only receive events for a single domain.

!!! warning

    If a domain is deleted and it was the only domain assigned to a webhook endpoint, that endpoint will be **automatically disabled** to prevent it from silently becoming global.

## Webhook Payload

The webhook payload is sent as a JSON object in the body of the POST request.
Here is an example payload for a `user.deleted` event:

```json
{
    "type": "user.deleted",
    "timestamp": "2025-09-10T11:36:14+00:00",
    "data": {
        "email": "user@example.org"
    }
}
```

## Webhook Headers

Each webhook request includes the following headers:

- `Content-Type: application/json`
- `X-Webhook-Signature`: HMAC SHA256 signature of the request body (see Security section).
- `X-Webhook-Id`: Unique identifier for the webhook request.
- `X-Webhook-Attempt`: Number of attempts made to deliver the webhook (starts at 1).

## Delivery & Retry Behavior

### Timeout

Webhook delivery requests have a hard timeout of 10 seconds (including connection establishment and waiting for the HTTP response).
If your endpoint does not return a response within this window, the attempt is considered failed and the retry mechanism (described below) is triggered.

Design your endpoint to:

- Respond quickly (perform slow work asynchronously if possible)
- Return a 2xx status code only after the payload has been durably accepted
- Avoid long-running synchronous processing during the request

### Retry Policy

Failed deliveries are retried up to 3 additional times using an incremental backoff schedule:

1. 1st retry after 10 seconds
2. 2nd retry after 60 seconds
3. 3rd retry after 360 seconds (6 minutes)

After the final failed attempt, the delivery is marked as permanently failed and no further retries are scheduled.

The `X-Webhook-Attempt` header starts at `1` for the initial attempt and increments with each retry.
Your receiver should treat webhook handling as idempotent; use `X-Webhook-Id` to de‑duplicate if you receive the same event more than once (e.g. due to retries near a race condition with your processing logic).

### Manual Retries

You can manually trigger a retry for a specific webhook delivery from the Userli admin interface.
This is useful if you have fixed an issue with your endpoint and want to reprocess a failed webhook event.

### Success Criteria

A delivery is considered successful if your endpoint returns any 2xx HTTP status code within the 10 second timeout.
All other status codes (3xx, 4xx, 5xx) or network/errors/timeouts trigger a retry (if remaining attempts exist).

### Recommendations for Consumers

- Immediately validate the signature before processing
- Persist (or log) `X-Webhook-Id` to guarantee idempotency
- Offload heavy work to a queue and return a 2xx quickly
- Monitor for missing events by tracking consecutive failed attempts
- Consider alerting if you observe attempt values >1 for many events

## Security

To ensure the authenticity of webhook requests, each request includes an `X-Webhook-Signature` header.
This signature is generated using HMAC with SHA256 and a secret key that you provide when configuring the webhook.
You can verify the signature on your server to confirm that the request originated from Userli.
The signature is computed as follows:

```text
hmac_sha256(secret, body)
```
