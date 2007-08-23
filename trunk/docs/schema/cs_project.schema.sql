--
-- PostgreSQL database dump
--

SET client_encoding = 'SQL_ASCII';
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET search_path = public, pg_catalog;

--
-- Name: plperl_call_handler(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION plperl_call_handler() RETURNS language_handler
    AS '$libdir/plperl', 'plperl_call_handler'
    LANGUAGE c;


ALTER FUNCTION public.plperl_call_handler() OWNER TO postgres;

--
-- Name: plperlu; Type: PROCEDURAL LANGUAGE; Schema: public; Owner: 
--

CREATE PROCEDURAL LANGUAGE plperlu HANDLER plperl_call_handler;


--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler
    AS '$libdir/plpgsql', 'plpgsql_call_handler'
    LANGUAGE c;


ALTER FUNCTION public.plpgsql_call_handler() OWNER TO postgres;

--
-- Name: plpgsql_validator(oid); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION plpgsql_validator(oid) RETURNS void
    AS '$libdir/plpgsql', 'plpgsql_validator'
    LANGUAGE c;


ALTER FUNCTION public.plpgsql_validator(oid) OWNER TO postgres;

--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: public; Owner: 
--

CREATE TRUSTED PROCEDURAL LANGUAGE plpgsql HANDLER plpgsql_call_handler VALIDATOR plpgsql_validator;


--
-- Name: dblink_pkey_results; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE dblink_pkey_results AS (
	"position" integer,
	colname text
);


ALTER TYPE public.dblink_pkey_results OWNER TO postgres;

--
-- Name: attribute_get_create(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION attribute_get_create(text) RETURNS integer
    AS $_$
DECLARE
	-- passed vars.
	my_attribName ALIAS FOR $1;
	
	-- internal vars.
	attributeId integer;
	myDebug text;
BEGIN
	-- retrieve data about the attribute.
	SELECT INTO attributeId attribute_id FROM attribute_table WHERE lower(name) = my_attribName;
	
	IF (attributeId IS NULL) THEN
		INSERT INTO attribute_table (name) VALUES (lower(my_attribName));
		SELECT INTO attributeId attribute_id FROM attribute_table WHERE lower(name) = my_attribName;
	END IF;
	
	myDebug := 'from attribName=(' || my_attribName || '), we get attributeId: ' || attributeId;
	--RAISE NOTICE '%', myDebug;
	
	-- Give 'em what they asked for.
	RETURN attributeId;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.attribute_get_create(text) OWNER TO postgres;

--
-- Name: cipher_exists(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION cipher_exists(text) RETURNS boolean
    AS '$libdir/pgcrypto', 'pg_cipher_exists'
    LANGUAGE c;


ALTER FUNCTION public.cipher_exists(text) OWNER TO postgres;

--
-- Name: clean_user(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION clean_user() RETURNS boolean
    AS $$
DECLARE

BEGIN
        VACUUM ANALYZE pl02_user_address_table;
        VACUUM ANALYZE pl02_user_login_table;
        VACUUM ANALYZE pl02_vendor_user_table;

        RETURN true;
END;
$$
    LANGUAGE plpgsql;


ALTER FUNCTION public.clean_user() OWNER TO postgres;

--
-- Name: contact_create_from_email(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION contact_create_from_email(text) RETURNS integer
    AS $_$
DECLARE
	-- arguments.
	my_email ALIAS FOR $1;
	
	-- internal vars.
	x_cleanEmail TEXT;
	x_retval INTEGER DEFAULT NULL;
BEGIN
	-- create a contact record.
	x_cleanEmail := trim(both ' '  from lower(my_email));
	INSERT INTO contact_table (fname,lname) VALUES (x_cleanEmail, 'From contact_create_from_email()');
	SELECT INTO x_retval currval('contact_table_contact_id_seq'::text);
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.contact_create_from_email(text) OWNER TO postgres;

--
-- Name: contact_get_attribute(integer, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION contact_get_attribute(integer, text) RETURNS text
    AS $_$
DECLARE
	-- arguments.
	my_contactId ALIAS FOR $1;
	my_attribute ALIAS FOR $2;
	
	-- internal vars.
	x_attributeId integer;
	x_record contact_attribute_link_table%ROWTYPE;
	x_counter integer DEFAULT 0;
	x_array TEXT[];
	
	x_debug TEXT;
	x_retval TEXT DEFAULT NULL;
BEGIN
	-- grab the attribute_id.
	SELECT INTO x_attributeId attribute_id FROM attribute_table WHERE lower(name) = trim(both ' ' FROM lower(my_attribute));
	
	RAISE NOTICE '%', x_attributeId;
	
	IF x_attributeId IS NOT NULL THEN
		-- To compensate for multiple values, this concatenates each item with a comma delimiter.
		FOR x_record IN SELECT * FROM contact_attribute_link_table WHERE contact_id = my_contactId AND 
		attribute_id=x_attributeId LOOP
			
			x_array[x_counter] := x_record.attribute_value;
			x_counter := x_counter +1;
			
		END LOOP;
		
		RAISE NOTICE '%', x_array;
		x_retval := array_to_string(x_array, ', ');
	ELSE
		-- something terrible happened.
		x_debug := 'Unable to locate attribute_id for attribute=(' || my_attribute || ')';
		RAISE EXCEPTION '%', x_debug;
	END IF;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.contact_get_attribute(integer, text) OWNER TO postgres;

--
-- Name: contact_id_from_email(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION contact_id_from_email(text) RETURNS integer
    AS $_$
DECLARE
	-- passed vars.
	my_email ALIAS FOR $1;
	
	-- internal vars.
	x_debug text;
	x_retval integer;
BEGIN
	-- call the overloaded version, but do NOT create one if not found (don't do unexpected things)
	SELECT INTO x_retval contact_id_from_email(my_email, FALSE);
	
	-- Give 'em what they asked for.
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.contact_id_from_email(text) OWNER TO postgres;

--
-- Name: contact_id_from_email(text, boolean); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION contact_id_from_email(text, boolean) RETURNS integer
    AS $_$
DECLARE
	-- passed vars.
	my_email ALIAS FOR $1;
	my_createOne ALIAS FOR $2;
	
	-- internal vars.
	x_debug text;
	x_cleanEmail text;
	x_newContactId integer;
	x_retval integer;
BEGIN
	-- retrieve data about the attribute.
	x_cleanEmail := trim(both ' '  from lower(my_email));
	SELECT INTO x_retval contact_id FROM contact_attribute_link_table WHERE attribute_id=2 
	AND lower(attribute_value) = x_cleanEmail;
	
	IF x_retval IS NULL THEN
		IF (my_createOne IS TRUE) THEN
			-- oooh... we'll create one.
			SELECT INTO x_retval contact_create_from_email(x_cleanEmail);
			PERFORM contact_update_attribute(x_retval, 'email', x_cleanEmail);
		ELSE
			-- Invalid contact_id: give 'em 0.
			x_retval := 0;
		END IF;
	END IF;
	
	-- Give 'em what they asked for.
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.contact_id_from_email(text, boolean) OWNER TO postgres;

--
-- Name: contact_id_from_uid(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION contact_id_from_uid(integer) RETURNS integer
    AS $_$
DECLARE
	-- passed vars.
	my_uid ALIAS FOR $1;
	
	-- internal vars.
	x_debug text;
	x_retval integer;
BEGIN
	-- retrieve data about the attribute.
	SELECT INTO x_retval contact_id FROM user_table WHERE uid=my_uid;
	
	-- Give 'em what they asked for.
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.contact_id_from_uid(integer) OWNER TO postgres;

--
-- Name: contact_update_attribute(integer, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION contact_update_attribute(integer, text, text) RETURNS integer
    AS $_$
DECLARE
	-- data given.
	my_contactId ALIAS FOR $1;
	my_attribName ALIAS FOR $2;
	my_attribValue ALIAS FOR $3;
	
	-- internal vars.
	x_attributeId integer;
	x_contactAttributeData attribute_table%ROWTYPE;
	x_numRows integer DEFAULT 0;
	x_retval integer DEFAULT 0;
BEGIN
	
	-- First, get information about the given attribute.
	SELECT INTO x_attributeId attribute_get_create(my_attribName);
	
	-- Now act based on that.
	SELECT INTO x_numRows count(*) FROM contact_attribute_link_table WHERE contact_id=my_contactId
		AND attribute_id=x_attributeId;
	
	IF (x_numRows = 0) THEN 
		-- No data... go ahead & insert.
		INSERT INTO contact_attribute_link_table (contact_id, attribute_id, attribute_value) VALUES 
			(my_contactId, x_attributeId, my_attribValue);
		SELECT INTO x_retval currval('contact_attribute_link_table_contact_attribute_link_id_seq');
	ELSE
		-- Got data... let's update it.
		UPDATE contact_attribute_link_table SET attribute_value=my_attribValue WHERE contact_id=my_contactId
			AND attribute_id=x_attributeId;
		SELECT INTO x_retval contact_attribute_link_id FROM contact_attribute_link_table WHERE contact_id=my_contactId
			AND attribute_id=x_attributeId;
	END IF;
	
	RETURN x_retval;
	
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.contact_update_attribute(integer, text, text) OWNER TO postgres;

--
-- Name: crypt(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION crypt(text, text) RETURNS text
    AS '$libdir/pgcrypto', 'pg_crypt'
    LANGUAGE c;


ALTER FUNCTION public.crypt(text, text) OWNER TO postgres;

--
-- Name: dblink(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink(text, text) RETURNS SETOF record
    AS '$libdir/dblink', 'dblink_record'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink(text, text) OWNER TO postgres;

--
-- Name: dblink(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink(text) RETURNS SETOF record
    AS '$libdir/dblink', 'dblink_record'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink(text) OWNER TO postgres;

--
-- Name: dblink_build_sql_delete(text, int2vector, integer, text[]); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_build_sql_delete(text, int2vector, integer, text[]) RETURNS text
    AS '$libdir/dblink', 'dblink_build_sql_delete'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_build_sql_delete(text, int2vector, integer, text[]) OWNER TO postgres;

--
-- Name: dblink_build_sql_insert(text, int2vector, integer, text[], text[]); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_build_sql_insert(text, int2vector, integer, text[], text[]) RETURNS text
    AS '$libdir/dblink', 'dblink_build_sql_insert'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_build_sql_insert(text, int2vector, integer, text[], text[]) OWNER TO postgres;

--
-- Name: dblink_build_sql_update(text, int2vector, integer, text[], text[]); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_build_sql_update(text, int2vector, integer, text[], text[]) RETURNS text
    AS '$libdir/dblink', 'dblink_build_sql_update'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_build_sql_update(text, int2vector, integer, text[], text[]) OWNER TO postgres;

--
-- Name: dblink_close(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_close(text) RETURNS text
    AS '$libdir/dblink', 'dblink_close'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_close(text) OWNER TO postgres;

--
-- Name: dblink_close(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_close(text, text) RETURNS text
    AS '$libdir/dblink', 'dblink_close'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_close(text, text) OWNER TO postgres;

--
-- Name: dblink_connect(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_connect(text) RETURNS text
    AS '$libdir/dblink', 'dblink_connect'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_connect(text) OWNER TO postgres;

--
-- Name: dblink_connect(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_connect(text, text) RETURNS text
    AS '$libdir/dblink', 'dblink_connect'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_connect(text, text) OWNER TO postgres;

--
-- Name: dblink_current_query(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_current_query() RETURNS text
    AS '$libdir/dblink', 'dblink_current_query'
    LANGUAGE c;


ALTER FUNCTION public.dblink_current_query() OWNER TO postgres;

--
-- Name: dblink_disconnect(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_disconnect() RETURNS text
    AS '$libdir/dblink', 'dblink_disconnect'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_disconnect() OWNER TO postgres;

--
-- Name: dblink_disconnect(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_disconnect(text) RETURNS text
    AS '$libdir/dblink', 'dblink_disconnect'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_disconnect(text) OWNER TO postgres;

--
-- Name: dblink_exec(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_exec(text, text) RETURNS text
    AS '$libdir/dblink', 'dblink_exec'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_exec(text, text) OWNER TO postgres;

--
-- Name: dblink_exec(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_exec(text) RETURNS text
    AS '$libdir/dblink', 'dblink_exec'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_exec(text) OWNER TO postgres;

--
-- Name: dblink_fetch(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_fetch(text, integer) RETURNS SETOF record
    AS '$libdir/dblink', 'dblink_fetch'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_fetch(text, integer) OWNER TO postgres;

--
-- Name: dblink_fetch(text, text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_fetch(text, text, integer) RETURNS SETOF record
    AS '$libdir/dblink', 'dblink_fetch'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_fetch(text, text, integer) OWNER TO postgres;

--
-- Name: dblink_get_pkey(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_get_pkey(text) RETURNS SETOF dblink_pkey_results
    AS '$libdir/dblink', 'dblink_get_pkey'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_get_pkey(text) OWNER TO postgres;

--
-- Name: dblink_open(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_open(text, text) RETURNS text
    AS '$libdir/dblink', 'dblink_open'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_open(text, text) OWNER TO postgres;

--
-- Name: dblink_open(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION dblink_open(text, text, text) RETURNS text
    AS '$libdir/dblink', 'dblink_open'
    LANGUAGE c STRICT;


ALTER FUNCTION public.dblink_open(text, text, text) OWNER TO postgres;

--
-- Name: decrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION decrypt(bytea, bytea, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_decrypt'
    LANGUAGE c;


ALTER FUNCTION public.decrypt(bytea, bytea, text) OWNER TO postgres;

--
-- Name: decrypt_iv(bytea, bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION decrypt_iv(bytea, bytea, bytea, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_decrypt_iv'
    LANGUAGE c;


ALTER FUNCTION public.decrypt_iv(bytea, bytea, bytea, text) OWNER TO postgres;

--
-- Name: digest(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION digest(text, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_digest'
    LANGUAGE c;


ALTER FUNCTION public.digest(text, text) OWNER TO postgres;

--
-- Name: digest(bytea, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION digest(bytea, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_digest'
    LANGUAGE c;


ALTER FUNCTION public.digest(bytea, text) OWNER TO postgres;

--
-- Name: digest_exists(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION digest_exists(text) RETURNS boolean
    AS '$libdir/pgcrypto', 'pg_digest_exists'
    LANGUAGE c;


ALTER FUNCTION public.digest_exists(text) OWNER TO postgres;

--
-- Name: encrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION encrypt(bytea, bytea, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_encrypt'
    LANGUAGE c;


ALTER FUNCTION public.encrypt(bytea, bytea, text) OWNER TO postgres;

--
-- Name: encrypt_iv(bytea, bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION encrypt_iv(bytea, bytea, bytea, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_encrypt_iv'
    LANGUAGE c;


ALTER FUNCTION public.encrypt_iv(bytea, bytea, bytea, text) OWNER TO postgres;

--
-- Name: gen_salt(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION gen_salt(text) RETURNS text
    AS '$libdir/pgcrypto', 'pg_gen_salt'
    LANGUAGE c;


ALTER FUNCTION public.gen_salt(text) OWNER TO postgres;

--
-- Name: gen_salt(text, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION gen_salt(text, integer) RETURNS text
    AS '$libdir/pgcrypto', 'pg_gen_salt_rounds'
    LANGUAGE c;


ALTER FUNCTION public.gen_salt(text, integer) OWNER TO postgres;

--
-- Name: hmac(text, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION hmac(text, text, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_hmac'
    LANGUAGE c;


ALTER FUNCTION public.hmac(text, text, text) OWNER TO postgres;

--
-- Name: hmac(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION hmac(bytea, bytea, text) RETURNS bytea
    AS '$libdir/pgcrypto', 'pg_hmac'
    LANGUAGE c;


ALTER FUNCTION public.hmac(bytea, bytea, text) OWNER TO postgres;

--
-- Name: hmac_exists(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION hmac_exists(text) RETURNS boolean
    AS '$libdir/pgcrypto', 'pg_hmac_exists'
    LANGUAGE c;


ALTER FUNCTION public.hmac_exists(text) OWNER TO postgres;

--
-- Name: internal_data_get_value(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION internal_data_get_value(text) RETURNS text
    AS $_$
DECLARE
	-- arguments.
	my_dataName ALIAS FOR $1;
	
	-- internal vars.
	x_cleanDataName TEXT;
	x_dataId INTEGER;
	x_debug TEXT;
	x_retval TEXT;
BEGIN
	-- Retrieve the data.
	x_cleanDataName := trim(both ' ' from my_dataName);
	SELECT INTO x_dataId, x_retval internal_data_id, internal_value FROM internal_data_table WHERE internal_name = x_cleanDataName;
	
	IF x_dataId IS NULL THEN
		-- failed to retrieve data...
		x_debug := 'Failed to retrieve data for ' || x_cleanDataName;
		RAISE EXCEPTION '%', x_debug;
	END IF;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.internal_data_get_value(text) OWNER TO postgres;

--
-- Name: internal_data_set_value(text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION internal_data_set_value(text, text) RETURNS integer
    AS $_$
DECLARE
	-- arguments.
	my_dataName ALIAS FOR $1;
	my_dataValue ALIAS FOR $2;
	
	-- internal vars.
	x_cleanDataName TEXT;
	x_dataId INTEGER;
	x_retval INTEGER DEFAULT 0;
BEGIN
	-- See if the data already exists.
	x_cleanDataName := trim(both ' ' from my_dataName);
	SELECT INTO x_dataId internal_data_id FROM internal_data_table WHERE internal_name = x_cleanDataName;
	
	IF x_dataId IS NULL THEN
		-- okay, create a new record.
		INSERT INTO internal_data_table (internal_name,internal_value) VALUES (x_cleanDataName, my_dataValue);
		SELECT INTO x_retval currval('internal_data_table_internal_data_id_seq'::text);
	ELSE
		-- it's already present.  Update it.
		UPDATE internal_data_table SET internal_value=my_dataValue WHERE internal_data_id=x_dataId;
		x_retval := x_dataId;
	END IF;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.internal_data_set_value(text, text) OWNER TO postgres;

--
-- Name: record_get_num_children(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION record_get_num_children(integer) RETURNS integer
    AS $_$
DECLARE
	-- arguments
	my_recordId ALIAS FOR $1;
	
	-- internal vars
	x_isHelpdeskIssue BOOLEAN;
	x_ancestryString text;
	x_ancestryLevel integer;
	x_debug text;
	x_retval integer DEFAULT 0;
	
BEGIN
	-- retrieve some information about the record.
	SELECT INTO x_ancestryString, x_isHelpdeskIssue,  x_ancestryLevel 
				ancestry,         is_helpdesk_issue,  ancestry_level 
		FROM record_table WHERE record_id=my_recordId;
	
	IF (x_ancestryString IS NOT NULL AND x_isHelpdeskIssue IS NOT NULL) THEN
		-- got it.
		SELECT INTO x_retval count(*) FROM record_table WHERE ancestry LIKE x_ancestryString || ':%' AND 
		ancestry_level > x_ancestryLevel AND is_helpdesk_issue=x_isHelpdeskIssue;
	ELSE
		-- failed.
		x_debug := 'No records found, or invalid data in record (' || my_recordId || ')';
		RAISE EXCEPTION '%', x_debug;
	END IF;
	
	RETURN x_retval;
	
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.record_get_num_children(integer) OWNER TO postgres;

--
-- Name: record_id_from_public_id(integer, boolean); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION record_id_from_public_id(integer, boolean) RETURNS integer
    AS $_$
DECLARE
	-- arguments.
	my_publicId ALIAS FOR $1;
	my_isHelpdesk ALIAS FOR $2;
	
	-- internal vars.
	x_debug TEXT;
	x_retval INTEGER DEFAULT 0;
BEGIN
	-- Retrieve the id.
	SELECT INTO x_retval record_id FROM record_table WHERE is_helpdesk_issue=my_isHelpdesk AND public_id=my_publicId;
	
	IF x_retval IS NULL THEN
		-- tell 'em it's bad.
		x_debug := 'Invalid public_id: ' || my_publicId;
	END IF;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.record_id_from_public_id(integer, boolean) OWNER TO postgres;

--
-- Name: replace(character varying, character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION "replace"(character varying, character varying, character varying) RETURNS character varying
    AS $_$ 
DECLARE 
subject ALIAS for $1; 
match ALIAS for $2; 
replace ALIAS for $3; 
r varchar; 
matchpos int; 
remain varchar; 
rempos int; 
BEGIN 

if (char_length(match) = 0) then 
raise exception 'replace function was called with null match string. This is not permitted.'; 
end if; 

remain := subject; 
r := ''; 
matchpos := strpos(subject,match); 
WHILE (matchpos > 0 ) LOOP 
r := r || substring(remain, 0,matchpos) || replace; 
rempos := matchpos + char_length(match); 
remain := substring(remain,rempos); 
matchpos := strpos(remain,match); 
END LOOP; 

r := r || remain; 
return r; 

END; 

$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public."replace"(character varying, character varying, character varying) OWNER TO postgres;

--
-- Name: tag_add(integer, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION tag_add(integer, text) RETURNS integer
    AS $_$
DECLARE
	-- arguments.
	my_recordId ALIAS FOR $1;
	my_tagName ALIAS FOR $2;
	
	-- internal vars
	x_debug TEXT;
	x_tagNameId INTEGER;
	x_retval INTEGER;
BEGIN
	-- Get the appropriate tag_id.
	SELECT INTO x_tagNameId tag_get_id(my_tagName);
	
	IF x_tagNameId IS NOT NULL THEN
		-- got it.  Insert the record.
		INSERT INTO tag_table (record_id,tag_name_id) VALUES (my_recordId, x_tagNameId);
		SELECT INTO x_retval currval('tag_table_tag_id_seq'::text);
	ELSE
		-- something went wrong.
		x_debug := 'Unable to locate tag_name_id for (' || my_tagName || ')';
		RAISE EXCEPTION '%', x_debug;
	END IF;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.tag_add(integer, text) OWNER TO postgres;

--
-- Name: tag_get_id(text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION tag_get_id(text) RETURNS integer
    AS $_$
DECLARE
	-- arguments
	my_tagName ALIAS FOR $1;
	
	-- internal vars.
	x_tagName TEXT;
	x_debug TEXT;
	x_retval INTEGER;
BEGIN
	-- retrieve the item.
	x_tagName := trim(both ' ' from lower(my_tagName));
	SELECT INTO x_retval tag_name_id FROM tag_name_table WHERE name=x_tagName;
	
	IF x_retval IS NULL THEN
		-- create a new tag!
		INSERT INTO tag_name_table (name) VALUES (x_tagName);
		SELECT INTO x_retval currval('tag_name_table_tag_name_id_seq'::text);
	END IF;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.tag_get_id(text) OWNER TO postgres;

--
-- Name: tag_list(integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION tag_list(integer) RETURNS text
    AS $_$
DECLARE
	-- arguments
	my_recordId ALIAS FOR $1;
	
	-- internal vars
	x_counter INTEGER DEFAULT 0;
	x_record RECORD;
	x_debug TEXT;
	x_retval TEXT;
BEGIN
	-- Retrieve all tags for the given record.
	FOR x_record IN SELECT tn.name FROM tag_table AS t INNER JOIN tag_name_table AS tn USING (tag_name_id) WHERE 
	record_id=my_recordId ORDER BY tag_id ASC LOOP
		
		IF x_record.name IS NOT NULL THEN
			IF x_counter > 0 THEN
				x_retval := x_retval || ', ' || x_record.name;
			ELSE
				x_retval := x_record.name;
			END IF;
		END IF;
			x_counter := x_counter +1;
		
	END LOOP;
	
	RETURN x_retval;
END;
$_$
    LANGUAGE plpgsql;


ALTER FUNCTION public.tag_list(integer) OWNER TO postgres;

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
-- Name: estimate_table; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE estimate_table (
    estimate_id serial NOT NULL,
    record_type_id integer NOT NULL,
    record_id integer NOT NULL,
    original numeric(10,2) DEFAULT 1 NOT NULL,
    current numeric(10,2) DEFAULT 1 NOT NULL,
    elapsed numeric(10,2) DEFAULT 0 NOT NULL
);


ALTER TABLE public.estimate_table OWNER TO postgres;

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
    record_type_id integer NOT NULL,
    record_id integer NOT NULL,
    elapsed numeric(10,2) NOT NULL
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
    progress smallint DEFAULT 0 NOT NULL,
    record_id integer NOT NULL
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

--
-- Name: attribute_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attribute_table
    ADD CONSTRAINT attribute_table_pkey PRIMARY KEY (attribute_id);


ALTER INDEX public.attribute_table_pkey OWNER TO postgres;

--
-- Name: contact_attribute_link_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY contact_attribute_link_table
    ADD CONSTRAINT contact_attribute_link_table_pkey PRIMARY KEY (contact_attribute_link_id);


ALTER INDEX public.contact_attribute_link_table_pkey OWNER TO postgres;

--
-- Name: contact_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY contact_table
    ADD CONSTRAINT contact_table_pkey PRIMARY KEY (contact_id);


ALTER INDEX public.contact_table_pkey OWNER TO postgres;

--
-- Name: estimate_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY estimate_table
    ADD CONSTRAINT estimate_table_pkey PRIMARY KEY (estimate_id);


ALTER INDEX public.estimate_table_pkey OWNER TO postgres;

--
-- Name: group_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY group_table
    ADD CONSTRAINT group_table_pkey PRIMARY KEY (group_id);


ALTER INDEX public.group_table_pkey OWNER TO postgres;

--
-- Name: internal_data_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY internal_data_table
    ADD CONSTRAINT internal_data_table_pkey PRIMARY KEY (internal_data_id);


ALTER INDEX public.internal_data_table_pkey OWNER TO postgres;

--
-- Name: log_category_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_category_table
    ADD CONSTRAINT log_category_table_pkey PRIMARY KEY (log_category_id);


ALTER INDEX public.log_category_table_pkey OWNER TO postgres;

--
-- Name: log_class_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_class_table
    ADD CONSTRAINT log_class_table_pkey PRIMARY KEY (log_class_id);


ALTER INDEX public.log_class_table_pkey OWNER TO postgres;

--
-- Name: log_estimate_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_estimate_table
    ADD CONSTRAINT log_estimate_table_pkey PRIMARY KEY (log_estimate_id);


ALTER INDEX public.log_estimate_table_pkey OWNER TO postgres;

--
-- Name: log_event_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_event_table
    ADD CONSTRAINT log_event_table_pkey PRIMARY KEY (log_event_id);


ALTER INDEX public.log_event_table_pkey OWNER TO postgres;

--
-- Name: log_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY log_table
    ADD CONSTRAINT log_table_pkey PRIMARY KEY (log_id);


ALTER INDEX public.log_table_pkey OWNER TO postgres;

--
-- Name: note_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY note_table
    ADD CONSTRAINT note_table_pkey PRIMARY KEY (note_id);


ALTER INDEX public.note_table_pkey OWNER TO postgres;

--
-- Name: pref_option_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY pref_option_table
    ADD CONSTRAINT pref_option_table_pkey PRIMARY KEY (pref_option_id);


ALTER INDEX public.pref_option_table_pkey OWNER TO postgres;

--
-- Name: pref_type_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY pref_type_table
    ADD CONSTRAINT pref_type_table_pkey PRIMARY KEY (pref_type_id);


ALTER INDEX public.pref_type_table_pkey OWNER TO postgres;

--
-- Name: record_contact_link_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY record_contact_link_table
    ADD CONSTRAINT record_contact_link_table_pkey PRIMARY KEY (record_contact_link_id);


ALTER INDEX public.record_contact_link_table_pkey OWNER TO postgres;

--
-- Name: record_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY record_table
    ADD CONSTRAINT record_table_pkey PRIMARY KEY (record_id);


ALTER INDEX public.record_table_pkey OWNER TO postgres;

--
-- Name: record_type_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY record_type_table
    ADD CONSTRAINT record_type_table_pkey PRIMARY KEY (record_type_id);


ALTER INDEX public.record_type_table_pkey OWNER TO postgres;

--
-- Name: session_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY session_table
    ADD CONSTRAINT session_table_pkey PRIMARY KEY (session_id);


ALTER INDEX public.session_table_pkey OWNER TO postgres;

--
-- Name: status_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY status_table
    ADD CONSTRAINT status_table_pkey PRIMARY KEY (status_id);


ALTER INDEX public.status_table_pkey OWNER TO postgres;

--
-- Name: tag_name_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY tag_name_table
    ADD CONSTRAINT tag_name_table_pkey PRIMARY KEY (tag_name_id);


ALTER INDEX public.tag_name_table_pkey OWNER TO postgres;

--
-- Name: tag_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY tag_table
    ADD CONSTRAINT tag_table_pkey PRIMARY KEY (tag_id);


ALTER INDEX public.tag_table_pkey OWNER TO postgres;

--
-- Name: todo_comment_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY todo_comment_table
    ADD CONSTRAINT todo_comment_table_pkey PRIMARY KEY (todo_comment_id);


ALTER INDEX public.todo_comment_table_pkey OWNER TO postgres;

--
-- Name: todo_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY todo_table
    ADD CONSTRAINT todo_table_pkey PRIMARY KEY (todo_id);


ALTER INDEX public.todo_table_pkey OWNER TO postgres;

--
-- Name: user_group_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_group_table
    ADD CONSTRAINT user_group_table_pkey PRIMARY KEY (user_group_id);


ALTER INDEX public.user_group_table_pkey OWNER TO postgres;

--
-- Name: user_pref_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_pref_table
    ADD CONSTRAINT user_pref_table_pkey PRIMARY KEY (user_pref_id);


ALTER INDEX public.user_pref_table_pkey OWNER TO postgres;

--
-- Name: user_table_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_table
    ADD CONSTRAINT user_table_pkey PRIMARY KEY (uid);


ALTER INDEX public.user_table_pkey OWNER TO postgres;

--
-- Name: contact_attrbute_link_table_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX contact_attrbute_link_table_uidx ON contact_attribute_link_table USING btree (contact_id, attribute_id);


ALTER INDEX public.contact_attrbute_link_table_uidx OWNER TO postgres;

--
-- Name: estimates_table_unique_recid_rectypeid_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX estimates_table_unique_recid_rectypeid_idx ON estimate_table USING btree (record_type_id, record_id);


ALTER INDEX public.estimates_table_unique_recid_rectypeid_idx OWNER TO postgres;

--
-- Name: internal_data_table_internal_name_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX internal_data_table_internal_name_uidx ON internal_data_table USING btree (internal_name);


ALTER INDEX public.internal_data_table_internal_name_uidx OWNER TO postgres;

--
-- Name: log_class_name_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX log_class_name_uidx ON log_class_table USING btree (lower(name));


ALTER INDEX public.log_class_name_uidx OWNER TO postgres;

--
-- Name: log_event__class_category__uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX log_event__class_category__uidx ON log_event_table USING btree (log_class_id, log_category_id);


ALTER INDEX public.log_event__class_category__uidx OWNER TO postgres;

--
-- Name: tag_name__uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX tag_name__uidx ON tag_name_table USING btree (name);


ALTER INDEX public.tag_name__uidx OWNER TO postgres;

--
-- Name: user_table_username_uidx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX user_table_username_uidx ON user_table USING btree (lower((username)::text));


ALTER INDEX public.user_table_username_uidx OWNER TO postgres;

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
-- Name: estimate_table_record_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY estimate_table
    ADD CONSTRAINT estimate_table_record_type_id_fkey FOREIGN KEY (record_type_id) REFERENCES record_type_table(record_type_id);


--
-- Name: group_table_leader_uid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY group_table
    ADD CONSTRAINT group_table_leader_uid_fkey FOREIGN KEY (leader_uid) REFERENCES user_table(uid);


--
-- Name: log_estimate_table_record_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY log_estimate_table
    ADD CONSTRAINT log_estimate_table_record_type_id_fkey FOREIGN KEY (record_type_id) REFERENCES record_type_table(record_type_id);


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
    ADD CONSTRAINT user_table_contact_id_fkey FOREIGN KEY (contact_id) REFERENCES contact_table(contact_id);


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


--
-- PostgreSQL database dump complete
--
