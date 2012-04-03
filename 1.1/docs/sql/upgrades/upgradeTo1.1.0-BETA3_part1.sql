--
-- SVN INFORMATION:::
-- SVN Signature::::::: $Id:upgradeTo1.1.0-BETA3_part1.sql 443 2007-10-24 16:56:31Z crazedsanity $
-- Last Committted::::: $Date:2007-10-24 11:56:31 -0500 (Wed, 24 Oct 2007) $
-- Last Committed Path: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/docs/sql/upgrades/upgradeTo1.1.0-BETA3_part1.sql $
--

ALTER TABLE contact_table 
	ALTER COLUMN contact_email_id 
	DROP NOT NULL;

ALTER TABLE contact_table 
	DROP CONSTRAINT "contact_table_contact_email_id_fkey";

ALTER TABLE ONLY contact_table 
	ADD CONSTRAINT contact_table_contact_email_id_fkey 
	FOREIGN KEY (contact_email_id) REFERENCES contact_email_table(contact_email_id) DEFERRABLE INITIALLY DEFERRED;