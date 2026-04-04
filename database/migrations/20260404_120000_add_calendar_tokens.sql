-- Add calendar tokens to users table
-- ical_token: URL secret for the read-only iCal feed
-- caldav_token: password for CalDAV HTTP Basic Auth (user authenticates with email + this token)
ALTER TABLE users
    ADD COLUMN ical_token VARCHAR(64) NULL UNIQUE,
    ADD COLUMN caldav_token VARCHAR(64) NULL UNIQUE;

CREATE INDEX idx_users_ical_token ON users (ical_token);
CREATE INDEX idx_users_caldav_token ON users (caldav_token);
