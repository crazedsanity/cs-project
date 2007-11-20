--
-- SVN INFORMATION:::
--
-- SVN Signature::::::: $Id$
-- Last Committted::::: $Date$
-- Last Committed Path: $HeadURL$
--


ALTER TABLE tag_name_table ADD COLUMN modifier int;
UPDATE tag_name_table SET modifier = -1;
ALTER TABLE tag_name_table ALTER COLUMN SET DEFAULT -1;
ALTER TABLE tag_name_table SET NOT NULL;
