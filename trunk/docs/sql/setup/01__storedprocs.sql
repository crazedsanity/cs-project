--
-- SVN INFORMATION:::
-- SVN Signature: $Id$
-- Last Committted Date: $Date$
-- Last Committed Path: $HeadURL$
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