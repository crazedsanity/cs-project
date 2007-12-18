--
-- SVN INFORMATION:::
-- SVN Signature: $Id:::: 03__indexes_etc.sql 253 2007-09-29 19:48:37Z crazedsanity $
-- Last Committted Date:: $Date:2007-11-20 11:02:38 -0600 (Tue, 20 Nov 2007) $
-- Last Committed Path::: $HeadURL$
--

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
-- Name: attribute_get_create(text); Type: FUNCTION; Schema: public; Owner: postgres
--