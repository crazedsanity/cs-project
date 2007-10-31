--
-- SVN INFORMATION:::
-- SVN Signature: $Id:::: 00__connectSettings.sql 185 2007-09-15 23:42:34Z crazedsanity $
-- Last Committted Date:: $Date$
-- Last Committed Path::: $HeadURL$
--


SET client_encoding = 'SQL_ASCII';
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


SET search_path = public, pg_catalog;






CREATE FUNCTION _test_plpgsql_exists() RETURNS integer
    AS $_$
DECLARE
BEGIN
	RETURN NULL;
END;
$_$
    LANGUAGE plpgsql;