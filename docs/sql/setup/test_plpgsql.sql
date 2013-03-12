--
-- SVN INFORMATION:::
-- SVN Signature: $Id$
-- Last Committted Date: $Date$
-- Last Committed Path: $HeadURL$
--



CREATE FUNCTION _test_plpgsql_exists() RETURNS integer
    AS $_$
DECLARE
BEGIN
	RETURN NULL;
END;
$_$
    LANGUAGE plpgsql;