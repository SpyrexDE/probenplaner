-- Allow orchestras to control whether members can edit promises for past rehearsals
ALTER TABLE orchestras
ADD COLUMN allow_past_edit TINYINT(1) DEFAULT 1;