<?php

namespace SparQL;

use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{

    /**
     * @var Result
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Result(
            [
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
                        "value" => "b8f362200000082",
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
                ]
            ], [
                "0" => "person",
                "1" => "name"
            ]
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function testFetchArray()
    {
        $result1 = $this->object->fetchArray();

        $this->assertEquals(
            [
                "person" => "b52272200000000",
                "person.type" => "bnode",
                "name" => "A Tarazona",
                "name.type" => "literal",
                "name.datatype" => "http://www.w3.org/2001/XMLSchema#string"
            ], $result1
        );

        $result2 = $this->object->fetchArray();

        $this->assertEquals(
            [
                "person" => "b52272200000002",
                "person.type" => "bnode",
                "name" => "Goran Z Mashanovich",
                "name.type" => "literal",
                "name.datatype" => "http://www.w3.org/2001/XMLSchema#string"
            ],
            $result2
        );
    }

    public function testFetchAll()
    {
        $results = new Results(
            [
                [
                    "person" => "b52272200000000",
                    "person.type" => "bnode",
                    "name" => "A Tarazona",
                    "name.type" => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person" => "b52272200000002",
                    "person.type" => "bnode",
                    "name" => "Goran Z Mashanovich",
                    "name.type" => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person" => "b52272200000080",
                    "person.type" => "bnode",
                    "name" => "Dr Olivia Bragg",
                    "name.type" => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person" => "b8f362200000082",
                    "person.type" => "bnode",
                    "name" => "Dr Mike Surridge",
                    "name.type" => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ],
                [
                    "person" => "bf4120a00000000",
                    "person.type" => "bnode",
                    "name" => "Judith Joseph",
                    "name.type" => "literal",
                    "name.datatype" => "http://www.w3.org/2001/XMLSchema#string",
                ]
            ]
        );

        $this->assertEquals(
            $results,
            $this->object->fetchAll()
        );
    }

    public function testNumRows()
    {
        $this->assertEquals(5, $this->object->numRows());
    }

    public function testFieldArray()
    {
        $this->assertEquals(
            [
                "0" => "person",
                "1" => "name"
            ], $this->object->fieldArray()
        );
    }

    public function testFieldName()
    {
        $this->assertEquals('person', $this->object->fieldName(0));
        $this->assertEquals('name', $this->object->fieldName(1));
    }
}
