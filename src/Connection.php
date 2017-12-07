<?php

namespace SparQL;

use ByJG\Util\CurlException;
use ByJG\Util\WebRequest;

class Connection
{

    protected $connection;
    protected $debug = false;
    protected $namespace = array();
    protected $params = null;
    protected $endpoint;

    # capabilities are either true, false or null if not yet tested.

    public function __construct($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function ns($short, $long)
    {
        $this->namespace[$short] = $long;
    }

    /**
     * @param null $params
     * @return null
     */
    public function cgiParams($params = null)
    {
        if ($params === null) {
            return $this->params;
        }
        if ($params === "") {
            $this->params = null;
            return null;
        }
        $this->params = $params;

        return $this->params;
    }

    /**
     * @param string $query
     * @param int|null $timeout
     * @return Result
     * @throws \SparQL\ConnectionException
     * @throws \SparQL\Exception
     */
    public function query($query, $timeout = null)
    {
        $prefixes = "";
        foreach ($this->namespace as $k => $v) {
            $prefixes .= "PREFIX $k: <$v>\n";
        }
        $output = $this->dispatchQuery($prefixes . $query, $timeout);

        $parser = new ParseXml($output);

        return new Result($parser->rows, $parser->fields);
    }

    /**
     * @param int $timeout
     * @return bool
     */
    public function alive($timeout = 3000)
    {
        try {
            $this->query("SELECT * WHERE { ?s ?p ?o } LIMIT 1", $timeout);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     *
     * @param string $sparql
     * @param int $timeout Timeout in mileseconds
     * @return string
     * @throws ConnectionException
     * @throws \SparQL\Exception
     */
    public function dispatchQuery($sparql, $timeout = null)
    {
        $url = $this->endpoint . "?query=" . urlencode($sparql);
        if ($this->params !== null) {
            $url .= "&" . $this->params;
        }
        if ($this->debug) {
            print "<div class='debug'>"
                . "<a href='" . htmlspecialchars($url) . "'>"
                . htmlspecialchars($sparql)
                . "</a></div>\n"
            ;
        }

        $webRequest = new WebRequest($url);
        
        if (!empty($timeout)) {
            $webRequest->setCurlOption(CURLOPT_TIMEOUT_MS, $timeout);
        }

        try {
            $output = $webRequest->get();
        } catch (CurlException $ex) {
            throw new ConnectionException($ex->getMessage());
        }

        if ($output === '') {
            throw new ConnectionException('URL returned no data', -1);
        }
        if ($webRequest->getLastStatus() != 200) {
            throw new Exception(
                'Bad response, ' . $webRequest->getLastStatus() . ': ' . $output,
                $webRequest->getLastStatus()
            );
        }

        return $output;
    }

    /**
     * @param string $endpoint
     * @param string $sparql
     * @param array $namespace
     * @return Results
     * @throws \SparQL\ConnectionException
     * @throws \SparQL\Exception
     */
    public static function get($endpoint, $sparql, $namespace = null)
    {
        $connection = new Connection($endpoint);
        foreach ((array) $namespace as $key => $value) {
            $connection->ns($key, $value);
        }

        $result = $connection->query($sparql);
        if (!$result) {
            return null;
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
    protected $capsDesc = array(
        "select" => "Basic SELECT",
        "constant_as" => "SELECT (\"foo\" AS ?bar)",
        "math_as" => "SELECT (2+3 AS ?bar)",
        "count" => "SELECT (COUNT(?a) AS ?n) ?b ... GROUP BY ?b",
        "max" => "SELECT (MAX(?a) AS ?n) ?b ... GROUP BY ?b",
        "sample" => "SELECT (SAMPLE(?a) AS ?n) ?b ... GROUP BY ?b",
        "load" => "LOAD <...>",
    );
    protected $capsCache;
    protected $capsAnysubject;

    /**
     * @param $filename
     * @param string $dbaType
     */
    public function capabilityCache($filename, $dbaType = 'db4')
    {
        $handlers = dba_handlers(true);
        if (array_key_exists($dbaType, $handlers)) { // it is possible to save cache?
            $this->capsCache = array($filename, $dbaType);
        }
    }

    public function capabilityCodes()
    {
        return array_keys($this->capsDesc);
    }

    public function capabilityDescription($code)
    {
        return $this->capsDesc[$code];
    }
    # return true if the endpoint supports a capability
    # nb. returns false if connecion isn't authoriased to use the feature, eg LOAD

    /**
     * @param $code
     * @return bool|mixed|null
     * @throws \SparQL\Exception
     */
    public function supports($code)
    {
        if (isset($this->caps[$code])) {
            return $this->caps[$code];
        }
        $wasCached = false;
        if (isset($this->capsCache)) {
            $dbaCache = dba_open($this->capsCache[0], "c", $this->capsCache[1]);

            $cacheTimeoutSeconds = 7 * 24 * 60 * 60;
            $dbKey = $this->endpoint . ";" . $code;
            $dbVal = dba_fetch($dbKey, $dbaCache);
            if ($dbVal !== false) {
                list( $result, $when ) = preg_split('/;/', $dbVal);
                if ($when + $cacheTimeoutSeconds > time()) {
                    return $result;
                }
                $wasCached = true;
            }

            dba_close($dbaCache);
        }

        $result = null;
        
        $data = [
            "select" => function () {
                 return $this->testSelect();
            },
            "constant_as" => function () {
                 return $this->testConstantAs();
            },
            "math_as" => function () {
                 return $this->testMathAs();
            },
            "count" => function () {
                 return $this->testCount();
            },
            "max" => function () {
                 return $this->testMax();
            },
            "load" => function () {
                 return $this->testLoad();
            },
            "sample" => function () {
                 return $this->testSample();
            }
        ];

        if (!isset($data[$code])) {
            throw new \SparQL\Exception("Unknown capability code: '$code'");
        }

        $this->caps[$code] = $data[$code]();
        if (isset($this->capsCache)) {
            $dbaCache = dba_open($this->capsCache[0], "c", $this->capsCache[1]);

            $dbKey = $this->endpoint . ";" . $code;
            $dbVal = $result . ";" . time();
            if ($wasCached) {
                dba_replace($dbKey, $dbVal, $dbaCache);
            } else {
                dba_insert($dbKey, $dbVal, $dbaCache);
            }

            dba_close($dbaCache);
        }
        return $this->caps[$code];
    }

    /**
     * @return mixed
     * @throws \SparQL\ConnectionException
     * @throws \SparQL\Exception
     */
    public function anySubject()
    {
        if (!isset($this->capsAnysubject)) {
            $results = $this->query(
                "SELECT * WHERE { ?s ?p ?o } LIMIT 1"
            );
            if (sizeof($results)) {
                $row = $results->fetchArray();
                $this->capsAnysubject = $row["s"];
            }
        }
        return $this->capsAnysubject;
    }
    # return true if the endpoint supports SELECT

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
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

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
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

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
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

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
    public function testCount()
    {
        try {
            # assumes at least one rdf:type predicate
            $subject = $this->anySubject();
            if (!isset($subject)) {
                return false;
            }
            $this->dispatchQuery("SELECT (COUNT(?p) AS ?n) ?o WHERE { <$subject> ?p ?o } GROUP BY ?o");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
    public function testMax()
    {
        try {
            $subject = $this->anySubject();
            if (!isset($subject)) {
                return false;
            }
            $this->dispatchQuery("SELECT (MAX(?p) AS ?max) ?o WHERE { <$subject> ?p ?o } GROUP BY ?o");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
    public function testSample()
    {
        try {
            $subject = $this->anySubject();
            if (!isset($subject)) {
                return false;
            }
            $this->dispatchQuery("SELECT (SAMPLE(?p) AS ?sam) ?o WHERE { <$subject> ?p ?o } GROUP BY ?o");
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws \SparQL\ConnectionException
     */
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
