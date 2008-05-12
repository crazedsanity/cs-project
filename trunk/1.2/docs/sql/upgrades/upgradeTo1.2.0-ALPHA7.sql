--
-- SVN INFORMATION:::
-- 
-- SVN Signature::::::::: $Id$
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--


delete from user_group_table WHERE user_group_id NOT IN (select distinct ON (uid, group_id) user_group_id FROM user_group_table);
CREATE UNIQUE INDEX user_group_table_uid_group_id_uidx ON user_group_table USING btree (uid, group_id);