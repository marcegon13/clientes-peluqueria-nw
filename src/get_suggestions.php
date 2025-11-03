<?php
include 'db/connection.php';

$term = $_GET['term'] ?? '';
if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT nombre, apellido 
    FROM clientes 
    WHERE nombre LIKE ? OR apellido LIKE ?
    LIMIT 10
");
$searchTerm = "%$term%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}

echo json_encode($suggestions);
?>