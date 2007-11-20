--
-- SVN INFORMATION:::
-- SVN Signature: $Id:::: 03__indexes_etc.sql 253 2007-09-29 19:48:37Z crazedsanity $
-- Last Committted Date:: $Date:2007-11-20 11:02:38 -0600 (Tue, 20 Nov 2007) $
-- Last Committed Path::: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/docs/sql/setup/03__indexes_etc.sql $
--

--
-- Name: user_table_uid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_table_uid_seq OWNED BY user_table.uid;


--
-- Name: attribute_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE attribute_table ALTER COLUMN attribute_id SET DEFAULT nextval('attribute_table_attribute_id_seq'::regclass);


--
-- Name: contact_attribute_link_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE contact_attribute_link_table ALTER COLUMN contact_attribute_link_id SET DEFAULT nextval('contact_attribute_link_table_contact_attribute_link_id_seq'::regclass);


--
-- Name: contact_email_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE contact_email_table ALTER COLUMN contact_email_id SET DEFAULT nextval('contact_email_table_contact_email_id_seq'::regclass);


--
-- Name: contact_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE contact_table ALTER COLUMN contact_id SET DEFAULT nextval('contact_table_contact_id_seq'::regclass);


--
-- Name: group_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE group_table ALTER COLUMN group_id SET DEFAULT nextval('group_table_group_id_seq'::regclass);


--
-- Name: internal_data_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE internal_data_table ALTER COLUMN internal_data_id SET DEFAULT nextval('internal_data_table_internal_data_id_seq'::regclass);


--
-- Name: log_category_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE log_category_table ALTER COLUMN log_category_id SET DEFAULT nextval('log_category_table_log_category_id_seq'::regclass);


--
-- Name: log_class_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE log_class_table ALTER COLUMN log_class_id SET DEFAULT nextval('log_class_table_log_class_id_seq'::regclass);


--
-- Name: log_estimate_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE log_estimate_table ALTER COLUMN log_estimate_id SET DEFAULT nextval('log_estimate_table_log_estimate_id_seq'::regclass);


--
-- Name: log_event_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE log_event_table ALTER COLUMN log_event_id SET DEFAULT nextval('log_event_table_log_event_id_seq'::regclass);


--
-- Name: log_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE log_table ALTER COLUMN log_id SET DEFAULT nextval('log_table_log_id_seq'::regclass);


--
-- Name: note_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE note_table ALTER COLUMN note_id SET DEFAULT nextval('note_table_note_id_seq'::regclass);


--
-- Name: pref_option_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE pref_option_table ALTER COLUMN pref_option_id SET DEFAULT nextval('pref_option_table_pref_option_id_seq'::regclass);


--
-- Name: pref_type_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE pref_type_table ALTER COLUMN pref_type_id SET DEFAULT nextval('pref_type_table_pref_type_id_seq'::regclass);


--
-- Name: record_contact_link_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE record_contact_link_table ALTER COLUMN record_contact_link_id SET DEFAULT nextval('record_contact_link_table_record_contact_link_id_seq'::regclass);


--
-- Name: record_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE record_table ALTER COLUMN record_id SET DEFAULT nextval('record_table_record_id_seq'::regclass);


--
-- Name: record_type_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE record_type_table ALTER COLUMN record_type_id SET DEFAULT nextval('record_type_table_record_type_id_seq'::regclass);


--
-- Name: status_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE status_table ALTER COLUMN status_id SET DEFAULT nextval('status_table_status_id_seq'::regclass);


--
-- Name: tag_name_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE tag_name_table ALTER COLUMN tag_name_id SET DEFAULT nextval('tag_name_table_tag_name_id_seq'::regclass);


--
-- Name: tag_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE tag_table ALTER COLUMN tag_id SET DEFAULT nextval('tag_table_tag_id_seq'::regclass);


--
-- Name: todo_comment_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE todo_comment_table ALTER COLUMN todo_comment_id SET DEFAULT nextval('todo_comment_table_todo_comment_id_seq'::regclass);


--
-- Name: todo_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE todo_table ALTER COLUMN todo_id SET DEFAULT nextval('todo_table_todo_id_seq'::regclass);


--
-- Name: user_group_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE user_group_table ALTER COLUMN user_group_id SET DEFAULT nextval('user_group_table_user_group_id_seq'::regclass);


--
-- Name: user_pref_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE user_pref_table ALTER COLUMN user_pref_id SET DEFAULT nextval('user_pref_table_user_pref_id_seq'::regclass);


--
-- Name: uid; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE user_table ALTER COLUMN uid SET DEFAULT nextval('user_table_uid_seq'::regclass);


--
-- Name: attribute_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attribute_table
    ADD CONSTRAINT attribute_table_pkey PRIMARY KEY (attribute_id);


--
-- Name: contact_attribute_link_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY contact_attribute_link_table
    ADD CONSTRAINT contact_attribute_link_table_pkey PRIMARY KEY (contact_attribute_link_id);


--
-- Name: contact_email_table_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY contact_email_table
    ADD CONSTRAINT contact_email_table_email_key UNIQUE (email);


--
-- Name: contact_email_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY contact_email_table
    ADD CONSTRAINT contact_email_table_pkey PRIMARY KEY (contact_email_id);


--
-- Name: contact_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY contact_table
    ADD CONSTRAINT contact_table_pkey PRIMARY KEY (contact_id);


--
-- Name: group_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY group_table
    ADD CONSTRAINT group_table_pkey PRIMARY KEY (group_id);


--
-- Name: internal_data_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY internal_data_table
    ADD CONSTRAINT internal_data_table_pkey PRIMARY KEY (internal_data_id);


--
-- Name: log_category_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_category_table
    ADD CONSTRAINT log_category_table_pkey PRIMARY KEY (log_category_id);


--
-- Name: log_class_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_class_table
    ADD CONSTRAINT log_class_table_pkey PRIMARY KEY (log_class_id);


--
-- Name: log_estimate_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_estimate_table
    ADD CONSTRAINT log_estimate_table_pkey PRIMARY KEY (log_estimate_id);


--
-- Name: log_event_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_event_table
    ADD CONSTRAINT log_event_table_pkey PRIMARY KEY (log_event_id);


--
-- Name: log_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_pkey PRIMARY KEY (log_id);


--
-- Name: note_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY note_table
    ADD CONSTRAINT note_table_pkey PRIMARY KEY (note_id);


--
-- Name: pref_option_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY pref_option_table
    ADD CONSTRAINT pref_option_table_pkey PRIMARY KEY (pref_option_id);


--
-- Name: pref_type_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY pref_type_table
    ADD CONSTRAINT pref_type_table_pkey PRIMARY KEY (pref_type_id);


--
-- Name: record_contact_link_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY record_contact_link_table
    ADD CONSTRAINT record_contact_link_table_pkey PRIMARY KEY (record_contact_link_id);


--
-- Name: record_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY record_table
    ADD CONSTRAINT record_table_pkey PRIMARY KEY (record_id);


--
-- Name: record_type_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY record_type_table
    ADD CONSTRAINT record_type_table_pkey PRIMARY KEY (record_type_id);


--
-- Name: session_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY session_table
    ADD CONSTRAINT session_table_pkey PRIMARY KEY (session_id);


--
-- Name: status_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY status_table
    ADD CONSTRAINT status_table_pkey PRIMARY KEY (status_id);


--
-- Name: tag_name_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY tag_name_table
    ADD CONSTRAINT tag_name_table_pkey PRIMARY KEY (tag_name_id);


--
-- Name: tag_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY tag_table
    ADD CONSTRAINT tag_table_pkey PRIMARY KEY (tag_id);


--
-- Name: todo_comment_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY todo_comment_table
    ADD CONSTRAINT todo_comment_table_pkey PRIMARY KEY (todo_comment_id);


--
-- Name: todo_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_pkey PRIMARY KEY (todo_id);


--
-- Name: user_group_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_group_table
    ADD CONSTRAINT user_group_table_pkey PRIMARY KEY (user_group_id);


--
-- Name: user_pref_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_pref_table
    ADD CONSTRAINT user_pref_table_pkey PRIMARY KEY (user_pref_id);


--
-- Name: user_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_table
    ADD CONSTRAINT user_table_pkey PRIMARY KEY (uid);


--
-- Name: contact_attrbute_link_table_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX contact_attrbute_link_table_uidx ON contact_attribute_link_table USING btree (contact_id, attribute_id);


--
-- Name: internal_data_table_internal_name_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX internal_data_table_internal_name_uidx ON internal_data_table USING btree (internal_name);


--
-- Name: log_class_name_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX log_class_name_uidx ON log_class_table USING btree (lower(name));


--
-- Name: log_event__class_category__uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX log_event__class_category__uidx ON log_event_table USING btree (log_class_id, log_category_id);


--
-- Name: tag_name__uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX tag_name__uidx ON tag_name_table USING btree (name);


--
-- Name: user_table_username_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX user_table_username_uidx ON user_table USING btree (lower((username)::text));


--
-- Name: contact_attribute_link_table_attribute_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY contact_attribute_link_table
    ADD CONSTRAINT contact_attribute_link_table_attribute_id_fkey FOREIGN KEY (attribute_id) REFERENCES attribute_table(attribute_id);


--
-- Name: contact_attribute_link_table_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY contact_attribute_link_table
    ADD CONSTRAINT contact_attribute_link_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id);


--
-- Name: contact_email_table_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY contact_email_table
    ADD CONSTRAINT contact_email_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id)
	DEFERRABLE INITIALLY DEFERRED;


--
-- Name: contact_table_contact_email_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY contact_table
    ADD CONSTRAINT contact_table_contact_email_id_fkey FOREIGN KEY (contact_email_id) REFERENCES contact_email_table(contact_email_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: group_table_leader_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY group_table
    ADD CONSTRAINT group_table_leader_uid_fkey FOREIGN KEY (leader_uid) REFERENCES user_table(uid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: log_estiate_table_todo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_estimate_table
    ADD CONSTRAINT log_estiate_table_todo_id_fkey FOREIGN KEY (todo_id) REFERENCES todo_table(todo_id);


--
-- Name: log_estimate_table_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_estimate_table
    ADD CONSTRAINT log_estimate_table_uid_fkey FOREIGN KEY (uid) REFERENCES user_table(uid);


--
-- Name: log_event_table_log_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_event_table
    ADD CONSTRAINT log_event_table_log_category_id_fkey FOREIGN KEY (log_category_id) REFERENCES log_category_table(log_category_id);


--
-- Name: log_event_table_log_class_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_event_table
    ADD CONSTRAINT log_event_table_log_class_id_fkey FOREIGN KEY (log_class_id) REFERENCES log_class_table(log_class_id);


--
-- Name: log_table_affected_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_affected_uid_fkey FOREIGN KEY (affected_uid) REFERENCES user_table(uid);


--
-- Name: log_table_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_group_id_fkey FOREIGN KEY (group_id) REFERENCES group_table(group_id);


--
-- Name: log_table_log_event_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_log_event_id_fkey FOREIGN KEY (log_event_id) REFERENCES log_event_table(log_event_id);


--
-- Name: log_table_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_uid_fkey FOREIGN KEY (uid) REFERENCES user_table(uid);


--
-- Name: note_table_creator_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY note_table
    ADD CONSTRAINT note_table_creator_contact_id_fkey FOREIGN KEY (creator_contact_id) REFERENCES contact_table(contact_id);


--
-- Name: note_table_record_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY note_table
    ADD CONSTRAINT note_table_record_id_fkey FOREIGN KEY (record_id) REFERENCES record_table(record_id);


--
-- Name: pref_option_table_pref_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY pref_option_table
    ADD CONSTRAINT pref_option_table_pref_type_id_fkey FOREIGN KEY (pref_type_id) REFERENCES pref_type_table(pref_type_id);


--
-- Name: record_contact_link_table_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY record_contact_link_table
    ADD CONSTRAINT record_contact_link_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id);


--
-- Name: record_contact_link_table_record_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY record_contact_link_table
    ADD CONSTRAINT record_contact_link_table_record_id_fkey FOREIGN KEY (record_id) REFERENCES record_table(record_id);


--
-- Name: record_table_creator_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY record_table
    ADD CONSTRAINT record_table_creator_contact_id_fkey FOREIGN KEY (creator_contact_id) REFERENCES contact_table(contact_id);


--
-- Name: record_table_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY record_table
    ADD CONSTRAINT record_table_group_id_fkey FOREIGN KEY (group_id) REFERENCES group_table(group_id);


--
-- Name: record_table_leader_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY record_table
    ADD CONSTRAINT record_table_leader_contact_id_fkey FOREIGN KEY (leader_contact_id) REFERENCES contact_table(contact_id);


--
-- Name: record_table_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY record_table
    ADD CONSTRAINT record_table_status_id_fkey FOREIGN KEY (status_id) REFERENCES status_table(status_id);


--
-- Name: session_table_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY session_table
    ADD CONSTRAINT session_table_uid_fkey FOREIGN KEY (uid) REFERENCES user_table(uid);


--
-- Name: tag_table_tag_name_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY tag_table
    ADD CONSTRAINT tag_table_tag_name_id_fkey FOREIGN KEY (tag_name_id) REFERENCES tag_name_table(tag_name_id);


--
-- Name: todo_comment_table_creator_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_comment_table
    ADD CONSTRAINT todo_comment_table_creator_contact_id_fkey FOREIGN KEY (creator_contact_id) REFERENCES contact_table(contact_id);


--
-- Name: todo_comment_table_todo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_comment_table
    ADD CONSTRAINT todo_comment_table_todo_id_fkey FOREIGN KEY (todo_id) REFERENCES todo_table(todo_id);


--
-- Name: todo_table_assigned_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_assigned_contact_id_fkey FOREIGN KEY (assigned_contact_id) REFERENCES contact_table(contact_id);


--
-- Name: todo_table_creator_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_creator_contact_id_fkey FOREIGN KEY (creator_contact_id) REFERENCES contact_table(contact_id);


--
-- Name: todo_table_record_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_record_id_fkey FOREIGN KEY (record_id) REFERENCES record_table(record_id);


--
-- Name: todo_table_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_status_id_fkey FOREIGN KEY (status_id) REFERENCES status_table(status_id);


--
-- Name: user_group_table_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_group_table
    ADD CONSTRAINT user_group_table_group_id_fkey FOREIGN KEY (group_id) REFERENCES group_table(group_id);


--
-- Name: user_group_table_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_group_table
    ADD CONSTRAINT user_group_table_uid_fkey FOREIGN KEY (uid) REFERENCES user_table(uid);


--
-- Name: user_pref_table_pref_option_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_pref_table
    ADD CONSTRAINT user_pref_table_pref_option_id_fkey FOREIGN KEY (pref_option_id) REFERENCES pref_option_table(pref_option_id);


--
-- Name: user_pref_table_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_pref_table
    ADD CONSTRAINT user_pref_table_uid_fkey FOREIGN KEY (uid) REFERENCES user_table(uid);


--
-- Name: user_table_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_table
    ADD CONSTRAINT user_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: user_table_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_table
    ADD CONSTRAINT user_table_group_id_fkey FOREIGN KEY (group_id) REFERENCES group_table(group_id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;