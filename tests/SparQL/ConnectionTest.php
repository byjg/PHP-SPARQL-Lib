<?php

namespace SparQL;

use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{

    const SPARQL_URL = 'http://dbpedia.org/sparql';

    protected static $SPARQL_NS = [
        'dbpedia-owl' => 'http://dbpedia.org/ontology/',
        'dbpprop' => 'http://dbpedia.org/property/'
    ];

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    public function testQueryOk()
    {
        $connection = new Connection(self::SPARQL_URL);
        foreach (self::$SPARQL_NS as $key => $value) {
            $connection->ns($key, $value);
        }

        $result = $connection->query("select distinct ?Concept where {[] a ?Concept} LIMIT 5");

        $this->assertEquals(5, $result->numRows());
    }

    /**
     * @throws \SparQL\ConnectionException
     * @throws \SparQL\Exception
     */
    public function testGetOk()
    {
        $result = Connection::get(
            self::SPARQL_URL,
            "select distinct ?Concept where {[] a ?Concept} LIMIT 5",
            self::$SPARQL_NS
        );
        $this->assertEquals(5, count($result));
    }

    /**
     * @expectedException \SparQL\ConnectionException
     */
    public function testWrongSparQLDataset()
    {
        Connection::get(
            "http://invaliddomain:9812/",
            "select distinct ?Concept where {[] a ?Concept} LIMIT 5",
            self::$SPARQL_NS
        );
    }

    /**
     * @expectedException \SparQL\Exception
     */
    function test_wrongSparQLDataset2()
    {
        Connection::get(self::SPARQL_URL, "?Concept  {[] a ?Concept} LIMIT 5");  // Without NS
    }

    function test_navigateSparQLDataset()
    {
        $result = Connection::get(
            self::SPARQL_URL,
            'SELECT  ?name ?meaning
                WHERE 
                {
                    ?s a  dbpedia-owl:Name;
                    dbpprop:name  ?name;
                    dbpprop:meaning  ?meaning 
                    . FILTER (str(?name) = "Garrick")
                }',
            self::$SPARQL_NS
        );

        $this->assertEquals(1, count($result));



        $this->assertEquals($result[0]["name"], "Garrick");
        $this->assertEquals($result[0]["name.type"], "literal");
        $this->assertEquals($result[0]["meaning"], "\"spear king\"");
        $this->assertEquals($result[0]["meaning.type"], "literal");
    }

    function testSupports()
    {
        $connection = new Connection(self::SPARQL_URL);
        $this->assertTrue($connection->supports('select'));
        $this->assertTrue($connection->supports('constant_as'));
        $this->assertTrue($connection->supports('math_as'));
        $this->assertTrue($connection->supports('count'));
        $this->assertTrue($connection->supports('max'));
        $this->assertFalse($connection->supports('load'));
        $this->assertTrue($connection->supports('sample'));
    }
}
