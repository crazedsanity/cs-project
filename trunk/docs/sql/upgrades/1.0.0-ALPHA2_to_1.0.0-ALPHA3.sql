--
-- SVN INFORMATION:::
--
-- SVN Signature::::::::: $Id:project_tables.sql 245 2007-09-28 17:58:55Z crazedsanity $
-- Last Committted Date:: $Date:2007-09-28 12:58:55 -0500 (Fri, 28 Sep 2007) $
-- Last Committed Path::: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/docs/sql/upgrades/1.0.0-ALPHA2_to_1.0.0-ALPHA3/project_tables.sql $
--

--begin;

ALTER TABLE todo_table DROP CONSTRAINT "todo_table_record_id_fkey";
ALTER TABLE note_table DROP CONSTRAINT "note_table_record_id_fkey";

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



-- copy data into the two tables.
INSERT INTO project_table (
	project_id, ancestry, ancestry_level, group_id, creator_contact_id, leader_contact_id, status_id,
	priority, progress, start_date, deadline, last_updated, title, body, is_internal_only
)
	SELECT public_id, ancestry, ancestry_level, group_id, creator_contact_id, leader_contact_id, status_id,
	priority, progress, start_date, deadline, last_updated, name, subject, is_internal_only 
	FROM 
		record_table
	WHERE
		is_helpdesk_issue IS FALSE;

INSERT INTO helpdesk_table (
	helpdesk_id, group_id, creator_contact_id, leader_contact_id, status_id,
	priority, progress, start_date, deadline, last_updated, title, body, is_internal_only
)
	SELECT public_id, group_id, creator_contact_id, leader_contact_id, status_id,
	priority, progress, start_date, deadline, last_updated, name, subject, is_internal_only 
	FROM 
		record_table
	WHERE
		is_helpdesk_issue IS TRUE;



CREATE TABLE project_contact_link_table (
	project_contact_link_id serial PRIMARY KEY,
	project_id integer NOT NULL REFERENCES project_table(project_id),
	contact_id integer NOT NULL REFERENCES contact_table(contact_id)
);


ALTER TABLE todo_table RENAME COLUMN record_id TO project_id;
ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_project_id_fkey FOREIGN KEY (project_id) REFERENCES project_table(project_id);


INSERT INTO project_contact_link_table (project_id, contact_id) 
	SELECT r.public_id, rcl.contact_id 
	FROM
		record_table AS r
	INNER JOIN 
		record_contact_link_table AS rcl USING (record_id);

DROP TABLE record_contact_link_table;
DROP TABLE record_table;



--abort;