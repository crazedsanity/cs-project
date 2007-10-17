--
-- SVN INFORMATION:::
-- SVN Signature::::::: $Id$
-- Last Committted::::: $Date$
-- Last Committed Path: $HeadURL$
--


CREATE TABLE contact_email_table (
	contact_email_id SERIAL NOT NULL PRIMARY KEY,
	contact_id integer NOT NULL REFERENCES contact_table(contact_id),
	email text NOT NULL UNIQUE CHECK (email = lower(email))
);


ALTER TABLE contact_table ADD COLUMN company text;
ALTER TABLE contact_table ADD COLUMN email NOT NULL CHECK (email NOT LIKE lower(email));
