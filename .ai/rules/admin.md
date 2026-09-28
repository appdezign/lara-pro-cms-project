---
paths:
  - 'laracms/core/src/admin/**'
---

# Admin

## Custom field renames do not preserve data
Renaming a custom field only happens during development, never on production sites. A rename therefore creates a new, empty column and keeps the old column as backup column `_oldname` (restorable under Backup columns). Do not change the rename to carry the data over by renaming the column itself.
