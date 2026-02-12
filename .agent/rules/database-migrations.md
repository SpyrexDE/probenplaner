---
trigger: model_decision
description: When database migrations are needed
---

If you need to do a schema change on the db create a corresponding migration file using the command './probenplaner.sh migrate:create [name]'. After running that, you can edit the file accordingly. Make sure to replace [Description] with a short description of what is done. Only add comments as titles for larger sections or if the SQL is complex.

When the migrations are ready, apply them using the command './probenplaner.sh migrate:up'.

Do never edit existing migration files.

When doing migrations you need to make sure to update ALL existing code to the new schema.