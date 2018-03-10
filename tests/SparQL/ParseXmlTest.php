<?php

namespace SparQL;

use PHPUnit\Framework\TestCase;

class ParseXmlTest extends TestCase
{

    /**
     * @var ParseXml
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new ParseXml('file://' . __DIR__ . '/sample.rdf');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function testParse()
    {
        $rows = [
            "0" =>
            [
                "person" =>
                [
                    "type" => "bnode",
                    "value" => "b52272200000000"
                ],
                "name" =>
                [
                    "type" => "literal",
                    "value" => "A Tarazona",
                    "datatype" => "http://www.w3.org/2001/XMLSchema#string"
                ],
            ],
            "1" =>
            [
                "person" =>
                [
                    "type" => "bnode",
                    "value" => "b52272200000002"
                ],
                "name" =>
                [
                    "type" => "literal",
                    "value" => "Goran Z Mashanovich",
                    "datatype" => "http://www.w3.org/2001/XMLSchema#string"
                ],
            ],
            "2" =>
            [
                "person" =>
                [
                    "type" => "bnode",
                    "value" => "b52272200000080"
                ],
                "name" =>
                [
                    "type" => "literal",
                    "value" => "Dr Olivia Bragg",
                    "datatype" => "http://www.w3.org/2001/XMLSchema#string"
                ],
            ],
            "3" =>
            [
                "person" =>
                [
                    "type" => "bnode",
                    "value" => "b8f362200000082"
                ],
                "name" =>
                [
                    "type" => "literal",
                    "value" => "Dr Mike Surridge",
                    "datatype" => "http://www.w3.org/2001/XMLSchema#string"
                ],
            ],
            "4" =>
            [
                "person" =>
                [
                    "type" => "bnode",
                    "value" => "bf4120a00000000"
                ],
                "name" =>
                [
                    "type" => "literal",
                    "value" => "Judith Joseph",
                    "datatype" => "http://www.w3.org/2001/XMLSchema#string"
                ],
            ],
        ];

        $fields = [
            "0" => "person",
            "1" => "name"
        ];


        $this->assertEquals($rows, $this->object->rows);
        $this->assertEquals($fields, $this->object->fields);
    }

    /**
     * @expectedException \SparQL\Exception
     */
    public function testParseError()
    {
        $objError = new ParseXml('some invalid text');
    }

    /**
     * @expectedException \SparQL\Exception
     */
    public function testParseInvalidFile()
    {
        $objError = new ParseXml('file:///some/path/not/found');
    }

    /**
     * @expectedException \SparQL\Exception
     */
    public function testParseErrorHttp()
    {
        $objError = new ParseXml('http://www.xmlnuke.com/not-found-page');
    }

    /**
     * @expectedException \SparQL\Exception
     */
    public function testParseErrorValidUrlInvalidXml()
    {
        $objError = new ParseXml('http://example.com');
    }
}
