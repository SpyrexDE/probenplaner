-- Ensure clean state
DELETE FROM users WHERE email IN ('leitung@jsobremen.de', 'mitglied@jsobremen.de');

-- Insert the Leitung user
INSERT INTO users (email, password, display_name) VALUES ('leitung@jsobremen.de', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JSO Leitung');

SET @leader_id = (SELECT id FROM users WHERE email = 'leitung@jsobremen.de');

-- Link the user to the JSO Bremen orchestra (ID 13)
INSERT INTO user_orchestras (user_id, orchestra_id, type) 
VALUES (@leader_id, 13, 'Dirigenten');

SET @uo_id = (SELECT id FROM user_orchestras WHERE user_id = @leader_id AND orchestra_id = 13 LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE orchestra_id = 13 AND name = 'Leitung');

-- Assign the Leitung role using the modern junction table
INSERT INTO user_orchestra_roles (user_orchestra_id, role_id)
VALUES (@uo_id, @role_id);
