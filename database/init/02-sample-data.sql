-- JSO-Planer sample data

-- Create default orchestra
INSERT INTO orchestras (name, token, leader_pw, conductor_username) VALUES
('Jugendsinfonieorchester Bremen', 'jso', 'duces', 'Martin');

-- Add admin user for testing (do not use in production)
-- Password: Bremen-Mitte
INSERT INTO users (username, password, type, orchestra_id, role) VALUES
('Martin', '$2y$10$Dj2xBGEUSERMDOTwfm9hnOZEjXlqG/auIfQYJ9vjFbN9Q5Xg8bLDu', 'Dirigent', 1, 'conductor');

-- Add sample users for testing (do not use in production)
-- Password: test1234
INSERT INTO users (username, password, type, orchestra_id, role) VALUES
('Anna', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Violine_1', 1, 'member'),
('Max', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Violine_2', 1, 'leader'),
('Sophie', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Bratsche', 1, 'member'),
('David', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Cello', 1, 'member'),
('Julia', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Flöte', 1, 'leader'),
('Marc', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Trompete', 1, 'member'),
('Lena', '$2y$10$kQJY5bOqYwQf4jCa3DXA.OE8TJgJQCQYEoFvNf/f0JfzIVf0pYxYa', 'Klarinette', 1, 'member');

-- Add sample rehearsals
INSERT INTO rehearsals (date, time, location, description, color, orchestra_id) VALUES
('2023-08-16', '19:00 - 21:30', 'Musiksaal Bremen', 'Erste Probe nach den Sommerferien', '#ffffff', 1),
('2023-08-23', '19:00 - 21:30', 'Musiksaal Bremen', 'Stimmprobe Streicher', '#ffffff', 1),
('2023-08-30', '19:00 - 21:30', 'Musiksaal Bremen', 'Vollprobe', '#ffffff', 1),
('2023-09-02', '13:00 - 17:00', 'Großer Saal Bremen', 'Generalprobe vor dem Konzert', '#ffebae', 1),
('2023-09-03', '17:00 (Konzert 19:00)', 'Großer Saal Bremen', 'Konzert Herbst 2023', '#ffb3b3', 1),
('2023-09-13', '19:00 - 21:30', 'Musiksaal Bremen', 'Stimmprobe Bläser', '#ffffff', 1),
('2023-09-20', '19:00 - 21:30', 'Musiksaal Bremen', 'Vollprobe', '#ffffff', 1),
('2023-09-27', '19:00 - 21:30', 'Musiksaal Bremen', 'Vollprobe', '#ffffff', 1),
('2023-10-04', 'Ganztägig', 'Berlin', 'Konzertreise nach Berlin', '#add8e6', 1);

-- Add rehearsal groups
INSERT INTO rehearsal_groups (rehearsal_id, group_name) VALUES
(1, 'Tutti'),
(2, 'Streicher'),
(3, 'Tutti'),
(4, 'Generalprobe'),
(5, 'Konzert'),
(6, 'Bläser'),
(7, 'Tutti'),
(8, 'Tutti'),
(9, 'Konzertreise');

-- Add sample user promises
INSERT INTO user_promises (user_id, rehearsal_id, attending, note) VALUES
(2, 2, 0, 'Leider krank'),
(2, 5, 1, NULL),
(3, 2, 1, NULL),
(3, 3, 1, NULL),
(3, 6, 1, NULL),
(3, 7, 1, NULL),
(3, 8, 1, NULL),
(3, 9, 1, NULL),
(5, 1, 1, NULL),
(5, 3, 1, NULL),
(5, 4, 1, NULL),
(5, 5, 1, NULL),
(6, 1, 1, NULL),
(6, 3, 1, NULL),
(6, 4, 0, 'Familienfeier'),
(7, 1, 1, NULL),
(7, 3, 1, NULL),
(7, 4, 1, NULL),
(7, 5, 1, NULL),
(7, 9, 0, 'Studienbeginn'),
(8, 1, 1, NULL),
(8, 2, 0, NULL),
(8, 3, 1, NULL),
(8, 4, 1, NULL),
(8, 5, 1, NULL); 