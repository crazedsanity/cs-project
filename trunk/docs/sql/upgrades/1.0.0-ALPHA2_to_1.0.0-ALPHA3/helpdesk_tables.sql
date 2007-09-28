--
-- SVN INFORMATION:::
--
-- SVN Signature::::::::: $Id$
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--

CREATE TABLE helpdesk_table (
	helpdesk_id serial NOT NULL PRIMARY KEY,
    group_id integer,
    creator_contact_id integer,
    leader_contact_id integer,
    status_id integer DEFAULT 0 NOT NULL,
    priority smallint,
    progress smallint DEFAULT 0 NOT NULL,
    start_date timestamp without time zone DEFAULT NOW(),
    deadline date,
    last_updated timestamp without time zone DEFAULT now(),
    title text NOT NULL,
    body text NOT NULL,
	project_id integer REFERENCES project_table(project_id),
    is_internal_only boolean DEFAULT false NOT NULL
);
