<?php
include 'db/connection.php';
require_once 'classes/ClientManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager = new ClientManager($conn);
    $cliente_id = isset($_POST['delete_id']) ? intval($_POST['delete_id']) : 0;
    
    $manager->deleteClient($cliente_id);
} else {
    header("Location: index.php");
}
?>
