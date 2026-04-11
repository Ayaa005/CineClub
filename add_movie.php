<?php
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$title = $data["title"] ?? "";
$year = $data["year"] ?? "";
$genre = $data["genre"] ?? "";
$date = $data["date"] ?? "";
$rating = $data["rating"] ?? "";
$comment = $data["comment"] ?? "";
$attendees = implode(",", $data["attendees"] ?? []);

$stmt = $conn->prepare("INSERT INTO archives (title, year, genre, date, rating, comment, attendees) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sisssss", $title, $year, $genre, $date, $rating, $comment, $attendees);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "msg" => $stmt->error]);
}