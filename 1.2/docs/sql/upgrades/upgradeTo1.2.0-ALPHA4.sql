--
-- SVN INFORMATION:::
-- 
-- SVN Signature::::::::: $Id$
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--






CREATE TABLE task_table (
    task_id serial NOT NULL PRIMARY KEY,
    creator_contact_id integer NOT NULL REFERENCES contact_table(contact_id),
    name text NOT NULL,
    body text NOT NULL,
    assigned_contact_id integer REFERENCES contact_table(contact_id),
    created timestamp with time zone DEFAULT ('now'::text)::date NOT NULL,
    updated timestamp with time zone,
    deadline date,
    started date,
    status_id integer DEFAULT 0 NOT NULL REFERENCES status_table(status_id),
    priority smallint DEFAULT 50 NOT NULL,
    progress numeric DEFAULT 0 NOT NULL,
    record_id integer NOT NULL REFERENCES record_table(record_id),
    estimate_original numeric(10,2) DEFAULT 1 NOT NULL,
    estimate_current numeric(10,2) DEFAULT 1 NOT NULL,
    estimate_elapsed numeric(10,2) DEFAULT 0 NOT NULL
);

-- Populate the table with existing todo data.


INSERT INTO task_table (task_id, creator_contact_id, name, body, assigned_contact_id, created, updated, deadline, started, status_id, priority, progress, record_id, estimate_original, estimate_current, estimate_elapsed) (SELECT todo_id, creator_contact_id, name, body, assigned_contact_id, created, updated, deadline, started, status_id, priority, progress, record_id, estimate_original, estimate_current, estimate_elapsed FROM todo_table);




CREATE TABLE task_comment_table (
    task_comment_id serial NOT NULL PRIMARY KEY,
    task_id integer NOT NULL,
    creator_contact_id integer NOT NULL REFERENCES contact_table(contact_id),
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone,
    subject text DEFAULT 'Comment'::text NOT NULL,
    body text NOT NULL
);

--Copy data into the table.
INSERT INTO task_comment_table (task_comment_id, task_id, creator_contact_id, created, updated, subject, body) (SELECT todo_comment_id, todo_id, creator_contact_id, created, updated, subject, body FROM todo_comment_table);




-- Fix foreign keys and such.
ALTER TABLE log_estimate_table RENAME todo_id TO task_id;
ALTER TABLE log_estimate_table DROP CONSTRAINT "log_estiate_table_todo_id_fkey";
ALTER TABLE ONLY log_estimate_table ADD CONSTRAINT log_estimate_table_task_id_fkey FOREIGN KEY (task_id) REFERENCES task_table(task_id);



-- GET RID OF THE OLD TABLES...
DROP TABLE todo_comment_table;
DROP TABLE todo_table;

