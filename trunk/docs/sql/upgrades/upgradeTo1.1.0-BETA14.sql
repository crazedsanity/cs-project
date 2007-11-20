--
-- SVN INFORMATION:::
--
-- SVN Signature::::::: $Id$
-- Last Committted::::: $Date$
-- Last Committed Path: $HeadURL$
--

ALTER TABLE contact_email_table DROP CONSTRAINT "contact_email_table_contact_id_fkey";
ALTER TABLE ONLY contact_email_table
	ADD CONSTRAINT contact_email_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id)
	DEFERRABLE INITIALLY DEFERRED;
