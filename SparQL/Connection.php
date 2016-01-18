<?php

namespace SparQL;

class Connection
{

    protected $db;
    protected $debug = false;
    protected $ns = array();
    protected $params = null;

    # capabilities are either true, false or null if not yet tested.

    public function __construct($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function ns($short, $long)
    {
        $this->ns[$short] = $long;
    }

    public function cgiParams($params = null)
    {
        if ($params === null) {
            return $this->params;
        }
        if ($params === "") {
            $this->params = null;
            return;
        }
        $this->params = $params;
    }

    /**
     *
     * @param type $query
     * @param type $timeout
     * @return \SparQL\Result
     */
    public function query($query, $timeout = null)
    {
        $prefixes = "";
        foreach ($this->ns as $k => $v) {
            $prefixes .= "PREFIX $k: <$v>\n";
        }
        $output = $this->dispatchQuery($prefixes . $query, $timeout);

        $parser = new ParseXml($output, 'contents');

        return new Result($parser->rows, $parser->fields);
    }

    public function alive($timeout = 3)
    {
        try {
            $this->query("SELECT * WHERE { ?s ?p ?o } LIMIT 1", $timeout);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function dispatchQuery($sparql, $timeout = null)
    {
        $url = $this->endpoint . "?query=" . urlencode($sparql);
        if ($this->params !== null) {
            $url .= "&" . $this->params;
        }
        if ($this->debug) {
            print "<div class='debug'><a href='" . htmlspecialchars($url) . "'>" . htmlspecialchars($prefixes . $query) . "</a></div>\n";
        }

        $ch = curl_init($url);
        if ($timeout !== null) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/sparql-results+xml"
        ));

        $errno = null;
        $error = null;

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        if (curl_errno($ch)) {
            $errno = curl_errno($ch);
            $error = 'Curl error: ' . curl_error($ch);
        }
        if ($output === '') {
            $errno = "-1";
            $error = 'URL returned no data';
        }
        if ($info['http_code'] != 200) {
            $errno = $info['http_code'];
            $error = 'Bad response, ' . $info['http_code'] . ': ' . $output;
        }
        curl_close($ch);

        if (!is_null($errno)) {
            throw new Exception($error, $errno);
        }

        return $output;
    }

    /**
     *
     * @param string $endpoint
     * @param string $sparql
     * @param array $ns
     * @return Results
     */
    public static function get($endpoint, $sparql, $ns = null)
    {
        $db = new Connection($endpoint);
        foreach ((array) $ns as $key => $value) {
            $db->ns($key, $value);
        }

        $result = $db->query($sparql);
        if (!$result) {
            return;
        }

        return $result->fetchAll();
    }
    ####################################
    # Endpoint Capability Testing
    ####################################
    # This section is very limited right now. I plan, in time, to
    # caching so it can save results to a cache to save re-doing them
    # and many more capability options (suggestions to cjg@ecs.soton.ac.uk)

    protected $caps = array();
    protected $caps_desc = array(
        "select" => "Basic SELECT",
        "constant_as" => "SELECT (\"foo\" AS ?bar)",
        "math_as" => "SELECT (2+3 AS ?bar)",
        "count" => "SELECT (COUNT(?a) AS ?n) ?b ... GROUP BY ?b",
        "max" => "SELECT (MAX(?a) AS ?n) ?b ... GROUP BY ?b",
        "sample" => "SELECT (SAMPLE(?a) AS ?n) ?b ... GROUP BY ?b",
        "load" => "LOAD <...>",
    );
    protected $caps_cache;
    protected $caps_anysubject;

    public function capabilityCache($filename, $dba_type = 'db4')
    {
        $handlers = dba_handlers(true);
        if (array_key_exists($dba_type, $handlers)) { // it is possible to save cache?
            $this->caps_cache = array($filename, $dba_type);
        }
    }

    public function capabilityCodes()
    {
        return array_keys($this->caps_desc);
    }

    public function capabilityDescription($code)
    {
        return $this->caps_desc[$code];
    }
    # return true if the endpoint supports a capability
    # nb. returns false if connecion isn't authoriased to use the feature, eg LOAD

    public function supports($code)
    {
        if (isset($this->caps[$code])) {
            return $this->caps[$code];
        }
        $was_cached = false;
        if (isset($this->caps_cache)) {
            $dbaCache = dba_open($this->caps_cache[0], "c", $this->caps_cache[1]);

            $CACHE_TIMEOUT_SECONDS = 7 * 24 * 60 * 60;
            $db_key = $this->endpoint . ";" . $code;
            $db_val = dba_fetch($db_key, $dbaCache);
            if ($db_val !== false) {
                list( $result, $when ) = preg_split('/;/', $db_val);
                if ($when + $CACHE_TIMEOUT_SECONDS > time()) {
                    return $result;
                }
                $was_cached = true;
            }

            dba_close($dbaCache);
        }
        $r = null;

        if ($code == "select") {
            $r = $this->testSelect();
        } elseif ($code == "constant_as") {
            $r = $this->testConstantAs();
        } elseif ($code == "math_as") {
            $r = $this->testMathAs();
        } elseif ($code == "count") {
            $r = $this->testCount();
        } elseif ($code == "max") {
            $r = $this->testMax();
        } elseif ($code == "load") {
            $r = $this->testLoad();
        } elseif ($code == "sample") {
            $r = $this->testSample();
        } else {
            print "<p>Unknown capability code: '$code'</p>";
            return false;
        }
        $this->caps[$code] = $r;
        if (isset($this->caps_cache)) {
            $dbaCache = dba_open($this->caps_cache[0], "c", $this->caps_cache[1]);

            $db_key = $this->endpoint . ";" . $code;
            $db_val = $r . ";" . time();
            if ($was_cached) {
                dba_replace($db_key, $db_val, $dbaCache);
            } else {
                dba_insert($db_key, $db_val, $dbaCache);
            }

            dba_close($dbaCache);
        }
        return $r;
    }

    public function anySubject()
    {
        if (!isset($this->caps_anysubject)) {
            $results = $this->query(
                "SELECT * WHERE { ?s ?p ?o } LIMIT 1");
            if (sizeof($results)) {
                $row = $results->fetchArray();
                $this->caps_anysubject = $row["s"];
            }
        }
        return $this->caps_anysubject;
    }
    # return true if the endpoint supports SELECT

    public function testSelect()
    {
        try {
            $this->dispatchQuery("SELECT ?s ?p ?o WHERE { ?s ?p ?o } LIMIT 1");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
    # return true if the endpoint supports AS

    public function testMathAs()
    {
        try {
            $this->dispatchQuery("SELECT (1+2 AS ?bar) WHERE { ?s ?p ?o } LIMIT 1");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
    # return true if the endpoint supports AS

    public function testConstantAs()
    {
        try {
            $this->dispatchQuery("SELECT (\"foo\" AS ?bar) WHERE { ?s ?p ?o } LIMIT 1");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
    # return true if the endpoint supports SELECT (COUNT(?x) as ?n) ... GROUP BY

    public function testCount()
    {
        try {
            # assumes at least one rdf:type predicate
            $s = $this->anySubject();
            if (!isset($s)) {
                return false;
            }
            $this->dispatchQuery("SELECT (COUNT(?p) AS ?n) ?o WHERE { <$s> ?p ?o } GROUP BY ?o");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function testMax()
    {
        try {
            $s = $this->anySubject();
            if (!isset($s)) {
                return false;
            }
            $this->dispatchQuery("SELECT (MAX(?p) AS ?max) ?o WHERE { <$s> ?p ?o } GROUP BY ?o");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function testSample()
    {
        try {
            $s = $this->anySubject();
            if (!isset($s)) {
                return false;
            }
            $this->dispatchQuery("SELECT (SAMPLE(?p) AS ?sam) ?o WHERE { <$s> ?p ?o } GROUP BY ?o");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function testLoad()
    {
        try {
            $this->dispatchQuery("LOAD <http://graphite.ecs.soton.ac.uk/sparqllib/examples/loadtest.rdf>");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
}
