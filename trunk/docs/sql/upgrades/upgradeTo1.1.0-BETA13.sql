--
-- SVN INFORMATION:::
--
-- SVN Signature::::::: $Id$
-- Last Committted::::: $Date$
-- Last Committed Path: $HeadURL$
--


CREATE TABLE auth_token_table (
	auth_token_id serial NOT NULL PRIMARY KEY,
	contact_id int NOT NULL REFERENCES contact_table(contact_id),
	checksum text NOT NULL DEFAULT currval('auth_token_table_auth_token_id_seq'::text),
	token varchar(32) NOT NULL,
	creation date NOT NULL DEFAULT CURRENT_DATE,
	expiration interval NOT NULL DEFAULT '1 day'::interval
);

SELECT setval('auth_token_table_auth_token_id_seq'::text, 1000)
