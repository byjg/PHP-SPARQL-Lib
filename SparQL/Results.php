<?php

namespace SparQL;

class Results extends \ArrayIterator
{
	protected $fields;

	public function setFields($fields)
	{
		$this->fields = $fields;
	}
	
	public function getFields()
	{
		return $this->fields;
	}

	public function toHtml()
	{
		$html = "<table class='sparql-results'><tr>";
		foreach( $this->fields as $i=>$field )
		{
			$html .= "<th>?$field</th>";
		}
		$html .= "</tr>";
		foreach( $this as $row )
		{
			$html.="<tr>";
			foreach( $row as $cell )
			{
				$html .= "<td>".htmlspecialchars( $cell )."</td>";
			}
			$html.="</tr>";
		}
		$html.="</table>
<style>
table.sparql-results { border-collapse: collapse; }
table.sparql-results tr td { border: solid 1px #000 ; padding:4px;vertical-align:top}
table.sparql-results tr th { border: solid 1px #000 ; padding:4px;vertical-align:top ; background-color:#eee;}
</style>
";
		return $html;
	}

}
