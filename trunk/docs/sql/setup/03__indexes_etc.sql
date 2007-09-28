--
-- SVN INFORMATION:::
-- SVN Signature: $Id$
-- Last Committted Date: $Date$
-- Last Committed Path: $HeadURL$
--

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
-- Name: helpdesk_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY helpdesk_table
    ADD CONSTRAINT helpdesk_table_pkey PRIMARY KEY (helpdesk_id);


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
-- Name: project_contact_link_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY project_contact_link_table
    ADD CONSTRAINT project_contact_link_table_pkey PRIMARY KEY (project_contact_link_id);


--
-- Name: project_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY project_table
    ADD CONSTRAINT project_table_pkey PRIMARY KEY (project_id);


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
-- Name: group_table_leader_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY group_table
    ADD CONSTRAINT group_table_leader_uid_fkey FOREIGN KEY (leader_uid) REFERENCES user_table(uid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: helpdesk_note_table_helpdesk_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY helpdesk_note_table
    ADD CONSTRAINT helpdesk_note_table_helpdesk_id_fkey FOREIGN KEY (helpdesk_id) REFERENCES helpdesk_table(helpdesk_id);


--
-- Name: helpdesk_table_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY helpdesk_table
    ADD CONSTRAINT helpdesk_table_project_id_fkey FOREIGN KEY (project_id) REFERENCES project_table(project_id);


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
-- Name: log_table_record_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_record_type_id_fkey FOREIGN KEY (record_type_id) REFERENCES record_type_table(record_type_id);


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
-- Name: pref_option_table_pref_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY pref_option_table
    ADD CONSTRAINT pref_option_table_pref_type_id_fkey FOREIGN KEY (pref_type_id) REFERENCES pref_type_table(pref_type_id);


--
-- Name: project_contact_link_table_contact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY project_contact_link_table
    ADD CONSTRAINT project_contact_link_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id);


--
-- Name: project_contact_link_table_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY project_contact_link_table
    ADD CONSTRAINT project_contact_link_table_project_id_fkey FOREIGN KEY (project_id) REFERENCES project_table(project_id);


--
-- Name: project_note_table_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY project_note_table
    ADD CONSTRAINT project_note_table_project_id_fkey FOREIGN KEY (project_id) REFERENCES project_table(project_id);


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
-- Name: todo_table_project_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_project_id_fkey FOREIGN KEY (project_id) REFERENCES project_table(project_id);


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

