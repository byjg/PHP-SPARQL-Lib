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

	public function __construct( $rows, $fields )
	{
		$this->rows = $rows;
		$this->fields = $fields;
	}

	public function fetchArray()
	{
		if( !@$this->rows[$this->i] ) { return; }
		$r = array();
		foreach( $this->rows[$this->i++]  as $k=>$v )
		{
			$r[$k] = $v["value"];
			$r["$k.type"] = $v["type"];
			if( isset( $v["language"] ) )
			{
				$r["$k.language"] = $v["language"];
			}
			if( isset( $v["datatype"] ) )
			{
				$r["$k.datatype"] = $v["datatype"];
			}
		}
		return $r;
	}

	/**
	 *
	 * @return Results
	 */
	public function fetchAll()
	{
		$r = new Results();
		$r->setFields($this->fields);
		foreach( $this->rows as $i=>$row )
		{
			$r[]= $this->fetchArray();
		}
		return $r;
	}

	public function numRows()
	{
		return sizeof( $this->rows );
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
