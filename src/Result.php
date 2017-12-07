<?php

namespace SparQL;

class Result
{

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var array
     */
    protected $fields;
    protected $iPos = 0;

    public function __construct($rows, $fields)
    {
        $this->rows = $rows;
        $this->fields = $fields;
    }

    /**
     * Fetch as array
     *
     * @return array|null
     */
    public function fetchArray()
    {
        if (!isset($this->rows[$this->iPos])) {
            return null;
        }

        $result = array();
        foreach ($this->rows[$this->iPos++] as $k => $v) {
            $result[$k] = $v["value"];
            $result["$k.type"] = $v["type"];
            if (isset($v["language"])) {
                $result["$k.language"] = $v["language"];
            }
            if (isset($v["datatype"])) {
                $result["$k.datatype"] = $v["datatype"];
            }
        }
        return $result;
    }

    /**
     *
     * @return Results
     */
    public function fetchAll()
    {
        $result = new Results();
        $result->setFields($this->fields);
        $this->iPos = 0;
        while ($array = $this->fetchArray()) {
            $result[] = $array;
        }
        return $result;
    }

    public function numRows()
    {
        return count($this->rows);
    }

    public function fieldArray()
    {
        return $this->fields;
    }

    public function fieldName($iPos)
    {
        return $this->fields[$iPos];
    }
}
