-- Clear expires_at for all member invite links (default_role_id IS NULL = member links)
UPDATE invite_links
SET expires_at = NULL
WHERE default_role_id IS NULL;
