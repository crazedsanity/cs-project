--
-- SVN INFORMATION:::
--
-- SVN Signature::::::::: $Id:project_tables.sql 245 2007-09-28 17:58:55Z crazedsanity $
-- Last Committted Date:: $Date:2007-09-28 12:58:55 -0500 (Fri, 28 Sep 2007) $
-- Last Committed Path::: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/docs/sql/upgrades/1.0.0-ALPHA2_to_1.0.0-ALPHA3/project_tables.sql $
--


ALTER TABLE todo_table DROP CONSTRAINT "todo_table_record_id_fkey";
ALTER TABLE record_contact_link_table DROP CONSTRAINT record_contact_link_table_record_id_fkey;
ALTER TABLE note_table DROP CONSTRAINT note_table_record_id_fkey;

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



CREATE TABLE project_note_table (
    project_note_id serial NOT NULL,
    title text NOT NULL,
    body text NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone,
    creator_contact_id integer NOT NULL,
    project_id integer NOT NULL REFERENCES project_table(project_id)
);
