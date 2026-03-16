# Commands

This app brings custom commands:

```text
app:admin:password                      Set password of admin user
app:alias:delete                        Delete an alias
app:api-token:create                    Create a new API token with specified name and scopes
app:api-token:delete                    Delete an API token by its plain token
app:domain:delete                       Delete a domain and all associated data (users, aliases, vouchers)
app:metrics                             Global Metrics for Userli
app:openpgp:show-key                    Show OpenPGP key of email
app:users:delete                        Delete a user
app:users:mailcrypt                     Get MailCrypt values for user
app:users:quota                         Get quota of user if set
app:users:registration:mail             Send a registration mail to a user
app:users:reset                         Reset a user
app:users:restore                       Restore a user
app:voucher:count                       Get count of vouchers for a specific user
app:voucher:create                      Create voucher for a specific user
```
    
Get more information about each command by running:

```
bin/console {{ command }} --help
```
