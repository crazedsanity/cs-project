--
-- SVN INFORMATION:::
-- SVN Signature::::::: $Id$
-- Last Committted::::: $Date$
-- Last Committed Path: $HeadURL$
--

ALTER TABLE contact_table 
	ALTER COLUMN contact_email_id 
	DROP NOT NULL;

ALTER TABLE contact_table 
	DROP CONSTRAINT "contact_table_contact_email_id_fkey";

ALTER TABLE ONLY contact_table 
	ADD CONSTRAINT contact_table_contact_email_id_fkey 
	FOREIGN KEY (contact_email_id) REFERENCES contact_email_table(contact_email_id) DEFERRABLE INITIALLY DEFERRED;