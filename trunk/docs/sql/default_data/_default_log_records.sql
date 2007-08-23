
SELECT pg_catalog.setval('log_class_table_log_class_id_seq', 1, false);

INSERT INTO log_class_table VALUES (1, 'Error');
INSERT INTO log_class_table VALUES (2, 'Information');
INSERT INTO log_class_table VALUES (3, 'Create');
INSERT INTO log_class_table VALUES (4, 'Update');
INSERT INTO log_class_table VALUES (5, 'Delete');
INSERT INTO log_class_table VALUES (6, 'REPORT');


--
-- Data for Name: log_category_table; Type: TABLE DATA; Schema: public; Owner: postgres
--
SELECT pg_catalog.setval('log_category_table_log_category_id_seq', 10, true);

INSERT INTO log_category_table VALUES (1, 'Database');
INSERT INTO log_category_table VALUES (2, 'Authentication');
INSERT INTO log_category_table VALUES (3, 'Users');
INSERT INTO log_category_table VALUES (4, 'General');
INSERT INTO log_category_table VALUES (5, 'Project');
INSERT INTO log_category_table VALUES (6, 'Helpdesk');
INSERT INTO log_category_table VALUES (7, 'Todo');
INSERT INTO log_category_table VALUES (8, 'Tags');
INSERT INTO log_category_table VALUES (9, 'Estimates');
INSERT INTO log_category_table VALUES (10, 'Navigation');


SELECT pg_catalog.setval('log_event_table_log_event_id_seq', 28, true);


--
-- Data for Name: log_event_table; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO log_event_table VALUES (1, 3, 5, 'Project: created record');
INSERT INTO log_event_table VALUES (2, 5, 5, 'Project: deleted record');
INSERT INTO log_event_table VALUES (3, 4, 5, 'Project: updated record');
INSERT INTO log_event_table VALUES (4, 1, 5, 'Project: ERROR');
INSERT INTO log_event_table VALUES (5, 3, 6, 'Helpdesk: Created record');
INSERT INTO log_event_table VALUES (6, 4, 6, 'Helpdesk: Updated record');
INSERT INTO log_event_table VALUES (25, 4, 9, 'Update: Estimates (auto-generated)');
INSERT INTO log_event_table VALUES (8, 1, 6, 'Helpdesk: ERROR');
INSERT INTO log_event_table VALUES (9, 6, 6, 'Helpdesk: Report');
INSERT INTO log_event_table VALUES (10, 3, 7, 'Todo: created record');
INSERT INTO log_event_table VALUES (11, 5, 7, 'Todo: deleted record');
INSERT INTO log_event_table VALUES (12, 4, 7, 'Todo: updated record');
INSERT INTO log_event_table VALUES (13, 1, 1, 'Database Error');
INSERT INTO log_event_table VALUES (14, 6, 5, 'Project: Activity Report');
INSERT INTO log_event_table VALUES (15, 6, 7, 'Todo: Activity Report');
INSERT INTO log_event_table VALUES (16, 3, 2, 'User logged-in');
INSERT INTO log_event_table VALUES (17, 5, 2, 'User logged-out');
INSERT INTO log_event_table VALUES (18, 6, 2, 'Login/Logout Report');
INSERT INTO log_event_table VALUES (19, 3, 8, 'Tags: created record');
INSERT INTO log_event_table VALUES (20, 5, 8, 'Tags: deleted record');
INSERT INTO log_event_table VALUES (21, 4, 8, 'Tags: updated record');
INSERT INTO log_event_table VALUES (22, 6, 8, 'Tags: Activity Report');
INSERT INTO log_event_table VALUES (23, 1, 2, 'Authentication: ERROR');
INSERT INTO log_event_table VALUES (24, 2, 10, 'Navigation: Viewed page');
INSERT INTO log_event_table VALUES (7, 2, 6, 'Helpdesk: Information');
INSERT INTO log_event_table VALUES (26, 1, 9, 'Error: Estimates (auto-generated)');
INSERT INTO log_event_table VALUES (27, 2, 5, 'Information: Project (auto-generated)');
INSERT INTO log_event_table VALUES (28, 4, 3, 'Update: Users (auto-generated)');


--
-- PostgreSQL database dump complete
--

