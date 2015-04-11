<?php
require_once( "../vendor/autoload.php" );

$db = new SparQL\Connection( "http://rdf.ecs.soton.ac.uk/sparql/" );
if( !$db ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }

$db->ns( "foaf","http://xmlns.com/foaf/0.1/" );

$sparql = "SELECT * WHERE { ?person a foaf:Person . ?person foaf:name ?name } LIMIT 5";
$result = $db->query( $sparql ); 
if( !$result ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }

$fields = $result->fieldArray();

print "<p>Number of rows: ".$result->numRows()." results.</p>";
print "<table class='example_table'>";
print "<tr>";
foreach( $fields as $field )
{
	print "<th>$field</th>";
}
print "</tr>";
while( $row = $result->fetchArray() )
{
	print "<tr>";
	foreach( $fields as $field )
	{
		print "<td>$row[$field]</td>";
	}
	print "</tr>";
}
print "</table>";


