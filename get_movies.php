<?php
include "db.php";

$res = $conn->query("SELECT * FROM archives ORDER BY date DESC");

$data = [];

while ($row = $res->fetch_assoc()) {
    $att = $row["attendees"];
    $row["attendees"] = ($att && $att !== "") ? explode(",", $att) : [];
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);