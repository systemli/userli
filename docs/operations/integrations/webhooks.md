# Webhooks

Userli sends webhooks to notify external services about user-related events,
for example to create or remove accounts in Nextcloud or Matrix, or to delete
the mailbox of a deleted user on the mail server.

!!! info

    We provide a webhook listener to inform Nextcloud and Synapse about user
    changes. See
    [userli-webhook-listener](https://github.com/systemli/userli-webhook-listener/).
    To delete and reset mailboxes on a Dovecot server, use the
    [mailbox janitor](mailbox-janitor.md).

Webhooks are configured by admins in **Admin → Webhooks**, see
[Admin Guide → Webhooks](../../admin/webhooks.md). This page describes what a
receiver has to implement.

## Events

| Event          | Triggered when |
|----------------|----------------|
| `user.created` | A user registered with an invite code. Users created in the admin interface don't trigger this event. |
| `user.deleted` | A user was deleted – by themselves, by an admin, by the CLI or by the removal of inactive users. Not sent when a whole domain is deleted. |
| `user.reset`   | An admin set a new password for a user with MailCrypt keys, or `app:users:reset` was run. Password, MailCrypt keys, recovery token and 2FA were reset; old encrypted emails are unreadable. |

## Domain filtering

Webhook endpoints can optionally be limited to one or more domains.

* **No domains selected** (default): the endpoint receives events for users on
  **all** domains.
* **One or more domains selected**: the endpoint only receives events for users
  of those domains.

!!! warning

    If a domain is deleted and it was the only domain assigned to a webhook
    endpoint, that endpoint is **automatically disabled** to prevent it from
    silently becoming global.

## Request

Userli sends a `POST` request with a JSON body:

```json
{
    "type": "user.deleted",
    "timestamp": "2025-09-10T11:36:14+00:00",
    "data": {
        "email": "user@example.org"
    }
}
```

Headers:

| Header                | Description |
|-----------------------|-------------|
| `Content-Type`        | `application/json` |
| `X-Webhook-Signature` | HMAC-SHA256 signature of the request body, see [Security](#security). |
| `X-Webhook-Id`        | Unique identifier of the delivery. |
| `X-Webhook-Attempt`   | Number of the attempt, starting at `1`. |

## Delivery and retries

* A delivery is **successful** if your endpoint returns any `2xx` status code
  within **10 seconds** (including connection setup).
* All other status codes, network errors and timeouts are retried up to three
  times: after 10 seconds, 60 seconds and 6 minutes. After that, the delivery
  is marked as failed.
* Admins can retry a delivery manually in the admin interface.
* Successful deliveries are removed after 14 days, failed ones are kept.

Webhooks are sent by the [worker](../background-services.md). If it isn't
running, no webhooks are sent.

Recommendations for receivers:

* Validate the signature before processing.
* Respond quickly. Offload slow work to a queue and return `2xx` once the
  event is stored.
* Make processing idempotent. Use `X-Webhook-Id` to detect duplicates.
* Alert if many events need more than one attempt.

## Security

Each request is signed with the secret configured for the endpoint:

```text
X-Webhook-Signature = hex(hmac_sha256(secret, body))
```

Compute the signature over the **raw** request body and compare it in constant
time. Example in Python:

```python
import hashlib
import hmac

def is_valid(secret: str, body: bytes, signature: str) -> bool:
    expected = hmac.new(secret.encode(), body, hashlib.sha256).hexdigest()
    return hmac.compare_digest(expected, signature)
```

Only accept webhooks via HTTPS.
