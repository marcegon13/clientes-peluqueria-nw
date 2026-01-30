<?php
include 'db/connection.php';
require_once 'classes/ClientManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager = new ClientManager($conn);
    
    $cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    $trabajo = $_POST['trabajo_realizado'] ?? '';
    $estilista = $_POST['estilista'] ?? '';
    $fecha = $_POST['fecha'] ?? '';

    $manager->addWork($cliente_id, $trabajo, $estilista, $fecha);
} else {
    header("Location: index.php");
}
?>
