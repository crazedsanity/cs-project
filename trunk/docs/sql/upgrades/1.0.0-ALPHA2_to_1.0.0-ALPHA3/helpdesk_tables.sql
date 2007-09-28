--
-- SVN INFORMATION:::
--
-- SVN Signature::::::::: $Id:helpdesk_tables.sql 245 2007-09-28 17:58:55Z crazedsanity $
-- Last Committted Date:: $Date:2007-09-28 12:58:55 -0500 (Fri, 28 Sep 2007) $
-- Last Committed Path::: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/docs/sql/upgrades/1.0.0-ALPHA2_to_1.0.0-ALPHA3/helpdesk_tables.sql $
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



CREATE TABLE helpdesk_note_table (
    helpdesk_note_id serial NOT NULL,
    title text NOT NULL,
    body text NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone,
    creator_contact_id integer NOT NULL,
    helpdesk_id integer NOT NULL REFERENCES helpdesk_table(helpdesk_id),
    is_solution boolean DEFAULT false
);

