<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// filepath: c:\wamp64\www\clientes-peluqueria\clientes-peluquerias\src\search.php
include 'db/connection.php';

if (isset($_GET['term'])) {
    $term = trim($_GET['term']);
    $stmt = $conn->prepare("SELECT id, nombre, apellido FROM clientes WHERE nombre LIKE ? OR apellido LIKE ?");
    if ($stmt === false) {
        echo json_encode(["error" => "Error en la consulta SQL: " . $conn->error]);
        exit();
    }
    $termWildcard = "%$term%";
    $stmt->bind_param("ss", $termWildcard, $termWildcard);
    $stmt->execute();
    $result = $stmt->get_result();

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    // Enviar la respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($clients);
    exit();
}
?>
