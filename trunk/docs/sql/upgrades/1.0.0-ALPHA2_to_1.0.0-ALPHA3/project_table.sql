--
-- SVN INFORMATION:::
--
-- SVN Signature::::::::: $Id$
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--

CREATE TABLE project_table (
    project_id serial NOT NULL PRIMARY KEY,
    ancestry text DEFAULT currval('project_table_project_id_seq'::text) NOT NULL,
    ancestry_level smallint DEFAULT 0 NOT NULL,
    group_id integer,
    creator_contact_id integer,
    leader_contact_id integer,
    status_id integer DEFAULT 0 NOT NULL,
    priority smallint,
    progress smallint DEFAULT 0 NOT NULL,
    start_date timestamp without time zone DEFAULT ('now'::text)::date,
    deadline date,
    last_updated timestamp with time zone DEFAULT now(),
    title text NOT NULL,
    body text NOT NULL,
    is_internal_only boolean DEFAULT false NOT NULL
);

