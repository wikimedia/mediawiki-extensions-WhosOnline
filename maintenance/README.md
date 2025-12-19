# WhosOnline Maintenance Scripts

This directory contains maintenance scripts for the WhosOnline extension.

## deleteAnonymousRecords.php

### Purpose

Removes all anonymous user records (userid = 0) from the `online` table. This script is useful for cleaning up databases that accumulated anonymous user records before the extension was modified to track only authenticated users.

### Usage

Run from the MediaWiki root directory:

```bash
# Preview what would be deleted (recommended first run)
php maintenance/run.php extensions/WhosOnline/maintenance/deleteAnonymousRecords.php --dry-run

# Actually delete the anonymous records
php maintenance/run.php extensions/WhosOnline/maintenance/deleteAnonymousRecords.php
```

### Options

- `--dry-run` - Shows how many records would be deleted without actually deleting them

### When to Use

- After upgrading from an older version of WhosOnline that tracked anonymous users
- When you want to remove all anonymous visitor data from this extension's database table.

### Output Example

```
Found 1523 anonymous user record(s) in the online table.
Successfully deleted 1523 anonymous user record(s) from the online table.
```

### Notes

- This script requires the WhosOnline extension to be installed
- It will check if the `online` table exists before attempting deletion
- The operation is permanent and cannot be undone
- No backup is created automatically - consider backing up your database first if needed
