# Update

When updating to a new userli version, please take a look at `UPGRADE.md`
to see whether manual steps are required.

To automatically update the database schema of userli, run these commands:

```shell
# Warm up cache
bin/console cache:warmup

# Show database schema updates
bin/console doctrine:schema:update --dump-sql

# If necessary update the database schema
bin/console doctrine:schema:update --force
```
