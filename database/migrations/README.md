# Database Migrations

This directory contains database migrations to update the schema as the application evolves.

## How to Run Migrations

To run a migration:

1. Navigate to the database directory:
   ```
   cd database
   ```

2. Execute the migration script:
   ```
   php run_migration.php migrations/migration_file.sql
   ```

   For example, to add the is_small_group column:
   ```
   php run_migration.php migrations/add_is_small_group.sql
   ```

## Available Migrations

- `add_is_small_group.sql` - Adds the `is_small_group` column to the users table for small group tracking
