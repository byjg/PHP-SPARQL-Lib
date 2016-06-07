<?php
require_once("../vendor/autoload.php");

$data = SparQL\Connection::get(
    "http://rdf.ecs.soton.ac.uk/sparql/",
    "
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
SELECT * WHERE { ?person a foaf:Person . ?person foaf:name ?name } LIMIT 5
");

if (!isset($data)) {
    print "<p>Error</p>";
}

print "<table class='example_table'>";
print "<tr>";
foreach ($data->getFields() as $field) {
    print "<th>$field</th>";
}
print "</tr>";
foreach ($data as $row) {
    print "<tr>";
    foreach ($data->getFields() as $field) {
        print "<td>$row[$field]</td>";
    }
    print "</tr>";
}
print "</table>";


