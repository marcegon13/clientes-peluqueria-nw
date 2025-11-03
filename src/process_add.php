<?php
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $trabajo = $_POST['trabajo_realizado'];
    $estilista = $_POST['estilista'];
    $fecha = $_POST['fecha'];

    $stmt = $conn->prepare("
        INSERT INTO trabajos (cliente_id, trabajo_realizado, estilista, fecha)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $cliente_id, $trabajo, $estilista, $fecha);
    $stmt->execute();

    header("Location: index.php?success=1");
    exit;
}
?>