-- Orchestra slugs are unique across organizations: every create/rename path checks findBySlug()
-- globally and /{org}/{slug} URLs resolve the orchestra by slug alone (findBySlugWithOrg()).
-- One statement on purpose: MySQL 8 DDL is atomic, so duplicate slugs make it fail with
-- "Duplicate entry" and leave the table unchanged; the migration then stays pending.
-- Check before deploying:
--   SELECT slug, COUNT(*) FROM orchestras GROUP BY slug HAVING COUNT(*) > 1;
ALTER TABLE orchestras ADD UNIQUE INDEX idx_orchestras_slug (slug);
