--
-- SVN INFORMATION:::
-- SVN Signature: $Id:::: 02__tables.sql 253 2007-09-29 19:48:37Z crazedsanity $
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--

SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: attribute_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attribute_table (
    attribute_id integer NOT NULL,
    name text NOT NULL,
    clean_as text DEFAULT 'sql'::text NOT NULL,
    display_name text NOT NULL
);


ALTER TABLE public.attribute_table OWNER TO postgres;

--
-- Name: attribute_table_attribute_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE attribute_table_attribute_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.attribute_table_attribute_id_seq OWNER TO postgres;

--
-- Name: attribute_table_attribute_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE attribute_table_attribute_id_seq OWNED BY attribute_table.attribute_id;


--
-- Name: contact_attribute_link_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE contact_attribute_link_table (
    contact_attribute_link_id integer NOT NULL,
    contact_id integer NOT NULL,
    attribute_id integer NOT NULL,
    attribute_value text
);


ALTER TABLE public.contact_attribute_link_table OWNER TO postgres;

--
-- Name: contact_attribute_link_table_contact_attribute_link_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE contact_attribute_link_table_contact_attribute_link_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.contact_attribute_link_table_contact_attribute_link_id_seq OWNER TO postgres;

--
-- Name: contact_attribute_link_table_contact_attribute_link_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE contact_attribute_link_table_contact_attribute_link_id_seq OWNED BY contact_attribute_link_table.contact_attribute_link_id;


SET default_with_oids = false;

--
-- Name: contact_email_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE contact_email_table (
    contact_email_id integer NOT NULL,
    contact_id integer NOT NULL,
    email text NOT NULL,
    CONSTRAINT contact_email_table_email_check CHECK ((email = lower(email)))
);


ALTER TABLE public.contact_email_table OWNER TO postgres;

--
-- Name: contact_email_table_contact_email_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE contact_email_table_contact_email_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.contact_email_table_contact_email_id_seq OWNER TO postgres;

--
-- Name: contact_email_table_contact_email_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE contact_email_table_contact_email_id_seq OWNED BY contact_email_table.contact_email_id;


SET default_with_oids = true;

--
-- Name: contact_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE contact_table (
    contact_id integer NOT NULL,
    fname text NOT NULL,
    lname text NOT NULL,
    company text,
    contact_email_id integer NOT NULL
);


ALTER TABLE public.contact_table OWNER TO postgres;

--
-- Name: contact_table_contact_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE contact_table_contact_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.contact_table_contact_id_seq OWNER TO postgres;

--
-- Name: contact_table_contact_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE contact_table_contact_id_seq OWNED BY contact_table.contact_id;


--
-- Name: group_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE group_table (
    group_id integer NOT NULL,
    name text NOT NULL,
    short_name character varying(15) NOT NULL,
    leader_uid integer NOT NULL
);


ALTER TABLE public.group_table OWNER TO postgres;

--
-- Name: group_table_group_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE group_table_group_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.group_table_group_id_seq OWNER TO postgres;

--
-- Name: group_table_group_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE group_table_group_id_seq OWNED BY group_table.group_id;


--
-- Name: internal_data_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE internal_data_table (
    internal_data_id integer NOT NULL,
    internal_name text NOT NULL,
    internal_value text NOT NULL
);


ALTER TABLE public.internal_data_table OWNER TO postgres;

--
-- Name: internal_data_table_internal_data_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE internal_data_table_internal_data_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.internal_data_table_internal_data_id_seq OWNER TO postgres;

--
-- Name: internal_data_table_internal_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE internal_data_table_internal_data_id_seq OWNED BY internal_data_table.internal_data_id;


--
-- Name: log_category_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_category_table (
    log_category_id integer NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.log_category_table OWNER TO postgres;

--
-- Name: log_category_table_log_category_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE log_category_table_log_category_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.log_category_table_log_category_id_seq OWNER TO postgres;

--
-- Name: log_category_table_log_category_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE log_category_table_log_category_id_seq OWNED BY log_category_table.log_category_id;


--
-- Name: log_class_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_class_table (
    log_class_id integer NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.log_class_table OWNER TO postgres;

--
-- Name: log_class_table_log_class_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE log_class_table_log_class_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.log_class_table_log_class_id_seq OWNER TO postgres;

--
-- Name: log_class_table_log_class_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE log_class_table_log_class_id_seq OWNED BY log_class_table.log_class_id;


--
-- Name: log_estimate_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_estimate_table (
    log_estimate_id integer NOT NULL,
    creation timestamp with time zone DEFAULT now() NOT NULL,
    uid integer NOT NULL,
    todo_id integer NOT NULL,
    add_elapsed numeric(10,2) NOT NULL,
    system_note text
);


ALTER TABLE public.log_estimate_table OWNER TO postgres;

--
-- Name: log_estimate_table_log_estimate_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE log_estimate_table_log_estimate_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.log_estimate_table_log_estimate_id_seq OWNER TO postgres;

--
-- Name: log_estimate_table_log_estimate_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE log_estimate_table_log_estimate_id_seq OWNED BY log_estimate_table.log_estimate_id;


--
-- Name: log_event_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_event_table (
    log_event_id integer NOT NULL,
    log_class_id integer NOT NULL,
    log_category_id integer NOT NULL,
    description text NOT NULL
);


ALTER TABLE public.log_event_table OWNER TO postgres;

--
-- Name: log_event_table_log_event_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE log_event_table_log_event_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.log_event_table_log_event_id_seq OWNER TO postgres;

--
-- Name: log_event_table_log_event_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE log_event_table_log_event_id_seq OWNED BY log_event_table.log_event_id;


--
-- Name: log_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_table (
    log_id integer NOT NULL,
    creation timestamp with time zone DEFAULT now() NOT NULL,
    log_event_id integer NOT NULL,
    group_id integer,
    uid integer NOT NULL,
    affected_uid integer NOT NULL,
    details text
);


ALTER TABLE public.log_table OWNER TO postgres;

--
-- Name: log_table_log_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE log_table_log_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.log_table_log_id_seq OWNER TO postgres;

--
-- Name: log_table_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE log_table_log_id_seq OWNED BY log_table.log_id;


--
-- Name: note_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE note_table (
    note_id integer NOT NULL,
    subject text NOT NULL,
    body text NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone,
    creator_contact_id integer NOT NULL,
    record_id integer,
    is_solution boolean DEFAULT false
);


ALTER TABLE public.note_table OWNER TO postgres;

--
-- Name: note_table_note_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE note_table_note_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.note_table_note_id_seq OWNER TO postgres;

--
-- Name: note_table_note_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE note_table_note_id_seq OWNED BY note_table.note_id;


--
-- Name: pref_option_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE pref_option_table (
    pref_option_id integer NOT NULL,
    pref_type_id integer NOT NULL,
    name text NOT NULL,
    effective_value text
);


ALTER TABLE public.pref_option_table OWNER TO postgres;

--
-- Name: pref_option_table_pref_option_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE pref_option_table_pref_option_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.pref_option_table_pref_option_id_seq OWNER TO postgres;

--
-- Name: pref_option_table_pref_option_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE pref_option_table_pref_option_id_seq OWNED BY pref_option_table.pref_option_id;


--
-- Name: pref_type_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE pref_type_table (
    pref_type_id integer NOT NULL,
    name text NOT NULL,
    default_value text,
    display_name text NOT NULL,
    description text NOT NULL
);


ALTER TABLE public.pref_type_table OWNER TO postgres;

--
-- Name: pref_type_table_pref_type_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE pref_type_table_pref_type_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.pref_type_table_pref_type_id_seq OWNER TO postgres;

--
-- Name: pref_type_table_pref_type_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE pref_type_table_pref_type_id_seq OWNED BY pref_type_table.pref_type_id;


--
-- Name: record_contact_link_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE record_contact_link_table (
    record_contact_link_id integer NOT NULL,
    record_id integer NOT NULL,
    contact_id integer NOT NULL
);


ALTER TABLE public.record_contact_link_table OWNER TO postgres;

--
-- Name: record_contact_link_table_record_contact_link_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE record_contact_link_table_record_contact_link_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.record_contact_link_table_record_contact_link_id_seq OWNER TO postgres;

--
-- Name: record_contact_link_table_record_contact_link_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE record_contact_link_table_record_contact_link_id_seq OWNED BY record_contact_link_table.record_contact_link_id;


--
-- Name: record_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE record_table (
    record_id integer NOT NULL,
    public_id integer DEFAULT currval(('record_table_record_id_seq'::text)::regclass) NOT NULL,
    ancestry text DEFAULT currval(('record_table_record_id_seq'::text)::regclass) NOT NULL,
    ancestry_level smallint DEFAULT 0 NOT NULL,
    group_id integer,
    creator_contact_id integer,
    leader_contact_id integer,
    status_id integer DEFAULT 0 NOT NULL,
    priority smallint,
    progress smallint DEFAULT 0 NOT NULL,
    start_date timestamp without time zone DEFAULT now(),
    deadline date,
    last_updated timestamp with time zone DEFAULT now(),
    name text NOT NULL,
    subject text NOT NULL,
    is_helpdesk_issue boolean NOT NULL,
    is_internal_only boolean DEFAULT false NOT NULL
);


ALTER TABLE public.record_table OWNER TO postgres;

--
-- Name: record_table_record_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE record_table_record_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.record_table_record_id_seq OWNER TO postgres;

--
-- Name: record_table_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE record_table_record_id_seq OWNED BY record_table.record_id;


--
-- Name: record_type_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE record_type_table (
    record_type_id integer NOT NULL,
    name text NOT NULL,
    module text NOT NULL
);


ALTER TABLE public.record_type_table OWNER TO postgres;

--
-- Name: record_type_table_record_type_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE record_type_table_record_type_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.record_type_table_record_type_id_seq OWNER TO postgres;

--
-- Name: record_type_table_record_type_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE record_type_table_record_type_id_seq OWNED BY record_type_table.record_type_id;


--
-- Name: session_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE session_table (
    session_id character varying(32) NOT NULL,
    uid integer NOT NULL,
    ip character varying(22) NOT NULL,
    creation timestamp with time zone DEFAULT now() NOT NULL,
    last_action timestamp with time zone DEFAULT now() NOT NULL,
    last_page_viewed text
);


ALTER TABLE public.session_table OWNER TO postgres;

--
-- Name: special__helpdesk_public_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE special__helpdesk_public_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.special__helpdesk_public_id_seq OWNER TO postgres;

--
-- Name: special__project_public_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE special__project_public_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.special__project_public_id_seq OWNER TO postgres;

--
-- Name: status_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE status_table (
    status_id integer NOT NULL,
    name character varying(50) NOT NULL,
    description text NOT NULL
);


ALTER TABLE public.status_table OWNER TO postgres;

--
-- Name: status_table_status_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE status_table_status_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.status_table_status_id_seq OWNER TO postgres;

--
-- Name: status_table_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE status_table_status_id_seq OWNED BY status_table.status_id;


--
-- Name: tag_name_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE tag_name_table (
    tag_name_id integer NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.tag_name_table OWNER TO postgres;

--
-- Name: tag_name_table_tag_name_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE tag_name_table_tag_name_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.tag_name_table_tag_name_id_seq OWNER TO postgres;

--
-- Name: tag_name_table_tag_name_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE tag_name_table_tag_name_id_seq OWNED BY tag_name_table.tag_name_id;


--
-- Name: tag_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE tag_table (
    tag_id integer NOT NULL,
    tag_name_id integer NOT NULL,
    record_id integer NOT NULL,
    "position" smallint DEFAULT 1 NOT NULL
);


ALTER TABLE public.tag_table OWNER TO postgres;

--
-- Name: tag_table_tag_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE tag_table_tag_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.tag_table_tag_id_seq OWNER TO postgres;

--
-- Name: tag_table_tag_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE tag_table_tag_id_seq OWNED BY tag_table.tag_id;


--
-- Name: todo_comment_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE todo_comment_table (
    todo_comment_id integer NOT NULL,
    todo_id integer NOT NULL,
    creator_contact_id integer NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone,
    subject text DEFAULT 'Comment'::text NOT NULL,
    body text NOT NULL
);


ALTER TABLE public.todo_comment_table OWNER TO postgres;

--
-- Name: todo_comment_table_todo_comment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE todo_comment_table_todo_comment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.todo_comment_table_todo_comment_id_seq OWNER TO postgres;

--
-- Name: todo_comment_table_todo_comment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE todo_comment_table_todo_comment_id_seq OWNED BY todo_comment_table.todo_comment_id;


--
-- Name: todo_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE todo_table (
    todo_id integer NOT NULL,
    creator_contact_id integer NOT NULL,
    name text NOT NULL,
    body text NOT NULL,
    assigned_contact_id integer,
    created timestamp with time zone DEFAULT ('now'::text)::date NOT NULL,
    updated timestamp with time zone,
    deadline date,
    started date,
    status_id integer DEFAULT 0 NOT NULL,
    priority smallint DEFAULT 50 NOT NULL,
    progress numeric DEFAULT 0 NOT NULL,
    record_id integer NOT NULL,
    estimate_original numeric(10,2) DEFAULT 1 NOT NULL,
    estimate_current numeric(10,2) DEFAULT 1 NOT NULL,
    estimate_elapsed numeric(10,2) DEFAULT 0 NOT NULL
);


ALTER TABLE public.todo_table OWNER TO postgres;

--
-- Name: todo_table_todo_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE todo_table_todo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.todo_table_todo_id_seq OWNER TO postgres;

--
-- Name: todo_table_todo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE todo_table_todo_id_seq OWNED BY todo_table.todo_id;


--
-- Name: user_group_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_group_table (
    user_group_id integer NOT NULL,
    uid integer NOT NULL,
    group_id integer NOT NULL
);


ALTER TABLE public.user_group_table OWNER TO postgres;

--
-- Name: user_group_table_user_group_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_group_table_user_group_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.user_group_table_user_group_id_seq OWNER TO postgres;

--
-- Name: user_group_table_user_group_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_group_table_user_group_id_seq OWNED BY user_group_table.user_group_id;


--
-- Name: user_pref_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_pref_table (
    user_pref_id integer NOT NULL,
    uid integer NOT NULL,
    pref_option_id integer NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.user_pref_table OWNER TO postgres;

--
-- Name: user_pref_table_user_pref_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_pref_table_user_pref_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.user_pref_table_user_pref_id_seq OWNER TO postgres;

--
-- Name: user_pref_table_user_pref_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_pref_table_user_pref_id_seq OWNED BY user_pref_table.user_pref_id;


--
-- Name: user_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_table (
    uid integer NOT NULL,
    username character varying(40) NOT NULL,
    "password" character varying(32),
    is_admin boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    group_id integer DEFAULT 1 NOT NULL,
    contact_id integer NOT NULL
);


ALTER TABLE public.user_table OWNER TO postgres;

--
-- Name: user_table_uid_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_table_uid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.user_table_uid_seq OWNER TO postgres;
