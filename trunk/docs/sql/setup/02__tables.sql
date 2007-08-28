--
-- SVN INFORMATION:::
-- SVN Signature: $Id$
-- Last Committted Date: $Date$
-- Last Committed Path: $HeadURL$
--

SET default_tablespace = '';

SET default_with_oids = true;

--
-- Name: attribute_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attribute_table (
    attribute_id serial NOT NULL,
    name text NOT NULL,
    clean_as text DEFAULT 'sql'::text NOT NULL
);


ALTER TABLE public.attribute_table OWNER TO postgres;

--
-- Name: contact_attribute_link_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE contact_attribute_link_table (
    contact_attribute_link_id serial NOT NULL,
    contact_id integer NOT NULL,
    attribute_id integer NOT NULL,
    attribute_value text
);


ALTER TABLE public.contact_attribute_link_table OWNER TO postgres;

--
-- Name: contact_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE contact_table (
    contact_id serial NOT NULL,
    fname text NOT NULL,
    lname text NOT NULL
);


ALTER TABLE public.contact_table OWNER TO postgres;

--
-- Name: group_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE group_table (
    group_id serial NOT NULL,
    name text NOT NULL,
    short_name character varying(15) NOT NULL,
    leader_uid integer NOT NULL
);


ALTER TABLE public.group_table OWNER TO postgres;

--
-- Name: internal_data_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE internal_data_table (
    internal_data_id serial NOT NULL,
    internal_name text NOT NULL,
    internal_value text NOT NULL
);


ALTER TABLE public.internal_data_table OWNER TO postgres;

--
-- Name: log_category_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_category_table (
    log_category_id serial NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.log_category_table OWNER TO postgres;

--
-- Name: log_class_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_class_table (
    log_class_id serial NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.log_class_table OWNER TO postgres;

--
-- Name: log_estimate_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_estimate_table (
    log_estimate_id serial NOT NULL,
    creation timestamp with time zone DEFAULT now() NOT NULL,
    uid integer NOT NULL,
    todo_id integer NOT NULL,
    add_elapsed numeric(10,2) NOT NULL,
    system_note text
);


ALTER TABLE public.log_estimate_table OWNER TO postgres;

--
-- Name: log_event_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_event_table (
    log_event_id serial NOT NULL,
    log_class_id integer NOT NULL,
    log_category_id integer NOT NULL,
    description text NOT NULL
);


ALTER TABLE public.log_event_table OWNER TO postgres;

--
-- Name: log_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE log_table (
    log_id serial NOT NULL,
    creation timestamp with time zone DEFAULT now() NOT NULL,
    log_event_id integer NOT NULL,
    group_id integer,
    uid integer NOT NULL,
    affected_uid integer NOT NULL,
    details text,
    record_type_id integer,
    record_id integer
);


ALTER TABLE public.log_table OWNER TO postgres;

--
-- Name: note_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE note_table (
    note_id serial NOT NULL,
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
-- Name: pref_option_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE pref_option_table (
    pref_option_id serial NOT NULL,
    pref_type_id integer NOT NULL,
    name text NOT NULL,
    effective_value text
);


ALTER TABLE public.pref_option_table OWNER TO postgres;

--
-- Name: pref_type_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE pref_type_table (
    pref_type_id serial NOT NULL,
    name text NOT NULL,
    default_value text,
    display_name text NOT NULL,
    description text NOT NULL
);


ALTER TABLE public.pref_type_table OWNER TO postgres;

--
-- Name: record_contact_link_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE record_contact_link_table (
    record_contact_link_id serial NOT NULL,
    record_id integer NOT NULL,
    contact_id integer NOT NULL
);


ALTER TABLE public.record_contact_link_table OWNER TO postgres;

--
-- Name: record_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE record_table (
    record_id serial NOT NULL,
    public_id integer DEFAULT currval('record_table_record_id_seq'::text) NOT NULL,
    ancestry text DEFAULT currval('record_table_record_id_seq'::text) NOT NULL,
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
    name text NOT NULL,
    subject text NOT NULL,
    is_helpdesk_issue boolean NOT NULL,
    is_internal_only boolean DEFAULT false NOT NULL
);


ALTER TABLE public.record_table OWNER TO postgres;

--
-- Name: record_type_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE record_type_table (
    record_type_id serial NOT NULL,
    name text NOT NULL,
    module text NOT NULL
);


ALTER TABLE public.record_type_table OWNER TO postgres;

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
    status_id serial NOT NULL,
    name character varying(50) NOT NULL,
    description text NOT NULL
);


ALTER TABLE public.status_table OWNER TO postgres;

--
-- Name: tag_name_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE tag_name_table (
    tag_name_id serial NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.tag_name_table OWNER TO postgres;

--
-- Name: tag_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE tag_table (
    tag_id serial NOT NULL,
    tag_name_id integer NOT NULL,
    record_id integer NOT NULL,
    "position" smallint DEFAULT 1 NOT NULL
);


ALTER TABLE public.tag_table OWNER TO postgres;

--
-- Name: todo_comment_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE todo_comment_table (
    todo_comment_id serial NOT NULL,
    todo_id integer NOT NULL,
    creator_contact_id integer NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone,
    subject text DEFAULT 'Comment'::text NOT NULL,
    body text NOT NULL
);


ALTER TABLE public.todo_comment_table OWNER TO postgres;

--
-- Name: todo_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE todo_table (
    todo_id serial NOT NULL,
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
-- Name: user_group_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_group_table (
    user_group_id serial NOT NULL,
    uid integer NOT NULL,
    group_id integer NOT NULL
);


ALTER TABLE public.user_group_table OWNER TO postgres;

--
-- Name: user_pref_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_pref_table (
    user_pref_id serial NOT NULL,
    uid integer NOT NULL,
    pref_option_id integer NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    updated timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.user_pref_table OWNER TO postgres;

--
-- Name: user_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_table (
    uid serial NOT NULL,
    username character varying(40) NOT NULL,
    "password" character varying(32),
    is_admin boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    group_id integer NOT NULL,
    contact_id integer NOT NULL
);


ALTER TABLE public.user_table OWNER TO postgres;