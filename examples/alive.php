<?php
require_once( "../vendor/autoload.php" );

$endpoints = array(
	"http://rdf.ecs.soton.ac.uk/sparql/"=>"Real endpoint",
	"http://"=>"Bad URL",
	"http://graphite.ecs.soton.ac.uk/not-real"=>"404 URL",
	"http://graphite.ecs.soton.ac.uk/sparqllib/examples/not-an-endpoint.txt"=>"Valid URL, but not an endpoint" );
foreach( $endpoints as $endpoint=>$desc)
{
	$db = new SparQL\Connection( $endpoint );

	print "<h3 style='border-top:solid 1px #666; padding-top:8px;margin-top:8px'>$desc</h3>";
	print "<p>$endpoint</p>";
	if( $db->alive() )
	{
		print "<p>OK</p>";
	}
	else
	{
		print "<p>Not alive</p>";
	}
}
