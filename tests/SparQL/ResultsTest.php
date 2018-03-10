<?php

namespace SparQL;

use PHPUnit\Framework\TestCase;

class ResultsTest extends TestCase
{

    /**
     * @var Results
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Results(
            [
                [
                    "person"        => "b52272200000000",
                    "person.type"   => "bnode",
                    "name"          => "A Tarazona",
                    "name.type"     => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person"        => "b52272200000002",
                    "person.type"   => "bnode",
                    "name"          => "Goran Z Mashanovich",
                    "name.type"     => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person"        => "b52272200000080",
                    "person.type"   => "bnode",
                    "name"          => "Dr Olivia Bragg",
                    "name.type"     => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person"        => "b8f362200000082",
                    "person.type"   => "bnode",
                    "name"          => "Dr Mike Surridge",
                    "name.type"     => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person"        => "bf4120a00000000",
                    "person.type"   => "bnode",
                    "name"          => "Judith Joseph",
                    "name.type"     => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
            ]
        );
        $this->object->setFields(['person', 'name']);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function testSetFields()
    {
        $this->object->setFields(['a', 'b']);

        $this->assertEquals(['a', 'b'], $this->object->getFields());
    }

    public function testGetFields()
    {
        $this->assertEquals(['person', 'name'], $this->object->getFields());
    }

    public function testArrayIterator()
    {
        $result1 = $this->object[0];

        $this->assertEquals(
            [
                "person"        => "b52272200000000",
                "person.type"   => "bnode",
                "name"          => "A Tarazona",
                "name.type"     => "literal",
                "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
            ], $result1
        );

        $result2 = $this->object[4];

        $this->assertEquals(
            [
                "person"        => "bf4120a00000000",
                "person.type"   => "bnode",
                "name"          => "Judith Joseph",
                "name.type"     => "literal",
                "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
            ], $result2
        );
    }
}
