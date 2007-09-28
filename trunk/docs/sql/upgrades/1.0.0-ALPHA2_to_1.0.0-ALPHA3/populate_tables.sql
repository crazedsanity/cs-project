--
-- SVN INFORMATION:::
--
-- SVN Signature::::::::: $Id$
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--


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

