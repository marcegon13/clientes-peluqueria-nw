<?php
include 'db/connection.php';

if (isset($_GET['clientId'])) {
    $clientId = intval($_GET['clientId']);
    $stmt = $conn->prepare("SELECT trabajo_realizado, estilista, fecha FROM trabajos WHERE cliente_id = ? LIMIT 1");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "No se encontraron datos para este cliente."]);
    }
}
?>