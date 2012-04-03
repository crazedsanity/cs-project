--
-- SVN INFORMATION:::
-- SVN Signature::::::: $Id$
-- Last Committted::::: $Date$
-- Last Committed Path: $HeadURL$
--

ALTER TABLE log_table 
	DROP COLUMN record_type_id;

ALTER TABLE log_table
	DROP COLUMN record_id;