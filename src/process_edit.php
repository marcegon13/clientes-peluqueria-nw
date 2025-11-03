<?php
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $trabajo = $_POST['trabajo_realizado'];
    $estilista = $_POST['estilista'];
    $fecha = $_POST['fecha'];

    // Actualiza el último trabajo del cliente
    $stmt = $conn->prepare("
        UPDATE trabajos 
        SET trabajo_realizado = ?, estilista = ?, fecha = ?
        WHERE cliente_id = ?
        ORDER BY fecha DESC LIMIT 1
    ");
    $stmt->bind_param("sssi", $trabajo, $estilista, $fecha, $cliente_id);
    $stmt->execute();

    header("Location: index.php?success=1");
    exit;
}
?>