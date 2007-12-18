--
-- SVN INFORMATION:::
-- 
-- SVN Signature::::::::: $Id$
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--


CREATE TABLE invoice_status_table (
	invoice_status_id integer NOT NULL PRIMARY KEY,
	name text NOT NULL UNIQUE,
	description text NOT NULL UNIQUE,
	is_updateable boolean NOT NULL DEFAULT FALSE
);

-- NOTE: by setting the invoice_status_id, we ensure they always get set properly, and new ones can't be readily inserted without specifying that too.
INSERT INTO invoice_status_table (invoice_status_id, name, description, is_updateable) VALUES (-1, 'W/0', 'Write Off', FALSE);
INSERT INTO invoice_status_table (invoice_status_id, name, description, is_updateable) VALUES (0, 'New', 'Open, pending changes', TRUE);
INSERT INTO invoice_status_table (invoice_status_id, name, description, is_updateable) VALUES (1, 'OK', 'Completed', FALSE);

CREATE TABLE invoice_table (
    invoice_id integer NOT NULL PRIMARY KEY,
	poc text,
    company text,
	address1 text,
	address2 text,
	phone text,
	fax text,
	city text,
	state text,
	zip text,
	invoice_status_id integer NOT NULL DEFAULT 0 REFERENCES invoice_status_table(invoice_status_id),
	creator_contact_id integer NOT NULL REFERENCES contact_table(contact_id),
	billing_contact_id integer NOT NULL REFERENCES contact_table(contact_id),
	is_proforma boolean NOT NULL DEFAULT FALSE,
	date_created date NOT NULL DEFAULT CURRENT_DATE
);


CREATE TABLE invoice_item_table (
	invoice_item_id integer NOT NULL PRIMARY KEY,
	invoice_id integer NOT NULL REFERENCES invoice_table(invoice_id),
	description text NOT NULL,
	unit_price decimal(10,2),
	quantity integer NOT NULL DEFAULT 1
);



CREATE TABLE invoice_transaction_table (
	invoice_transaction_id integer NOT NULL PRIMARY KEY,
	invoice_id integer NOT NULL REFERENCES invoice_table(invoice_id),
	auth_string text NOT NULL,
	number text NOT NULL,
	date_created date NOT NULL DEFAULT CURRENT_DATE
);


