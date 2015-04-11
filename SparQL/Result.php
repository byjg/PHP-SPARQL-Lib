<?php

namespace SparQL;

class Result
{
	var $rows;
	var $fields;
	var $db;
	var $i = 0;
	function __construct( $db, $rows, $fields )
	{
		$this->rows = $rows;
		$this->fields = $fields;
		$this->db = $db;
	}

	function fetchArray()
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
	function fetchAll()
	{
		$r = new Results();
		$r->fields = $this->fields;
		foreach( $this->rows as $i=>$row )
		{
			$r[]= $this->fetchArray();
		}
		return $r;
	}

	function numRows()
	{
		return sizeof( $this->rows );
	}

	function fieldArray()
	{
		return $this->fields;
	}

	function fieldName($i)
	{
		return $this->fields[$i];
	}
}
