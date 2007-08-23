ALTER TABLE group_table ALTER COLUMN leader_uid DROP NOT NULL;
insert into group_table (name, short_name, leader_uid) VALUES ('Default', 'default', 0);
INSERT INTO group_table (group_id,name,short_name, leader_uid) VALUES (0, 'invalid', 'invalid', 0);
INSERT INTO user_table (uid,username,group_id) VALUES (0,'Anonymous', 0);
ALTER TABLE group_table ALTER COLUMN leader_uid SET NOT NULL;
