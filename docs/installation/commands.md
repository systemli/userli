# Commands

This app brings custom commands:

```text
app:admin:password                      Set password of admin user
app:alias:delete                        Delete an alias
app:metrics                             Global Metrics for Userli
app:openpgp:delete-key                  Delete OpenPGP key for email
app:openpgp:import-key                  Import OpenPGP key for email
app:openpgp:show-key                    Show OpenPGP key of email
app:report:weekly                       Send weekly report to all admins
app:users:delete                        Delete a user
app:users:list                          List users
app:users:mailcrypt                     Get MailCrypt values for user
app:users:quota                         Get quota of user if set
app:users:registration:mail             Send a registration mail to a user
app:users:remove                        Removes all mailboxes from deleted users
app:users:reset                         Reset a user
app:users:restore                       Reset a user
app:voucher:count                       Get count of vouchers for a specific user
app:voucher:create                      Create voucher for a specific user
app:voucher:unlink                      Remove connection between vouchers and accounts after 3 months
app:wkd:export-keys                     Export all OpenPGP keys to WKD directory
```
    
Get more information about each command by running:

```
bin/console {{ command }} --help
```
