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
    protected $i = 0;

    public function __construct($rows, $fields)
    {
        $this->rows = $rows;
        $this->fields = $fields;
    }

    public function fetchArray()
    {
        if (!isset($this->rows[$this->i])) {
            return;
        }

        $result = array();
        foreach ($this->rows[$this->i++] as $k => $v) {
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
        foreach ($this->rows as $i => $row) {
            $result[] = $this->fetchArray();
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

    public function fieldName($i)
    {
        return $this->fields[$i];
    }
}
