# Update

When updating to a new userli version, please take a look at `UPGRADE.md`
to see whether manual steps are required.

To automatically update the database schema of userli, run these commands:

```shell
# Warm up cache
bin/console cache:warmup

# Execute pending migrations
bin/console doctrine:migrations:migrate
```
