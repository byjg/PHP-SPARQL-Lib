<?php
require_once("../vendor/autoload.php");

$data = SparQL\Connection::get(
    "http://rdf.ecs.soton.ac.uk/sparql/",
    "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
SELECT * WHERE { ?person a foaf:Person . ?person foaf:name ?name } LIMIT 5
");
if (!isset($data)) {
    print "<p>Error</p>";
}

print "<table class='example_table'>";
print "<tr>";
foreach ($data->getFields() as $field) {
    print "<th>$field</th>";
    print "<th>$field.type</th>";
    print "<th>$field.datatype</th>";
    print "<th>$field.language</th>";
}
print "</tr>";
foreach ($data as $row) {
    print "<tr>";
    foreach ($data->getFields() as $field) {
        print "<td>$row[$field]</td>";
        print "<td>" . $row["$field.type"] . "</td>";
        print "<td>" . @$row["$field.datatype"] . "</td>";
        print "<td>" . @$row["$field.language"] . "</td>";
    }
    print "</tr>";
}
print "</table>";


