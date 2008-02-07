--
-- SVN INFORMATION:::
-- SVN Signature: $Id:::: 03__indexes_etc.sql 253 2007-09-29 19:48:37Z crazedsanity $
-- Last Committted Date:: $Date:2007-11-20 11:02:38 -0600 (Tue, 20 Nov 2007) $
-- Last Committed Path::: $HeadURL$
--



CREATE FUNCTION _test_plpgsql_exists() RETURNS integer
    AS $_$
DECLARE
BEGIN
	RETURN NULL;
END;
$_$
    LANGUAGE plpgsql;