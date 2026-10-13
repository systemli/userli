# Notifications

**Configuration → Notifications** (admins only) is a log of security notices
that Userli has shown to users. Filter by type or search by email address.

![Notifications](../assets/images/admin-notifications.png){class="screenshot"}

## Compromised passwords

Currently there is one type: **password compromised**.

Every time a user signs in – on the web or via the mail server – Userli checks
the password against the [Have I Been Pwned](https://haveibeenpwned.com/Passwords)
database, at most once every 7 days per user. Only the first characters of a
hash of the password are sent, never the password itself
([k-anonymity](https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange)).

If the password was found:

* a notification is stored and listed here,
* the user sees a red **Security Alert** banner in the web interface until they
  change their password,
* the user detail page shows a warning.

No email is sent to the user.

Notifications are removed when the user changes the password, when the account
is deleted, and automatically after 30 days (see [Maintenance](maintenance.md)).
