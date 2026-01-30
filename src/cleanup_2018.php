<?php
include 'db/connection.php';

$cutoff_date = '2018-12-31';

// 1. Count records
$sql_count = "SELECT COUNT(*) as count FROM trabajos WHERE fecha <= ? AND fecha != '0000-00-00'";
$stmt = $conn->prepare($sql_count);
$stmt->bind_param("s", $cutoff_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$count = $row['count'];

$message = "";

// 2. Handle Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    
    // 2a. Backup first
    $sql_select = "SELECT * FROM trabajos WHERE fecha <= ? AND fecha != '0000-00-00'";
    $stmt_sel = $conn->prepare($sql_select);
    $stmt_sel->bind_param("s", $cutoff_date);
    $stmt_sel->execute();
    $res_sel = $stmt_sel->get_result();
    
    $data = [];
    while ($row_data = $res_sel->fetch_assoc()) {
        $data[] = $row_data;
    }
    
    $backup_file = 'backup_deleted_' . date('Y-m-d_H-i-s') . '.json';
    if (file_put_contents($backup_file, json_encode($data))) {
        
        // 2b. Delete
        $sql_delete = "DELETE FROM trabajos WHERE fecha <= ? AND fecha != '0000-00-00'";
        $stmt_del = $conn->prepare($sql_delete);
        $stmt_del->bind_param("s", $cutoff_date);
        
        if ($stmt_del->execute()) {
            $deleted_count = $stmt_del->affected_rows;
            $message = "<div class='alert alert-success'>xito: Se han eliminado $deleted_count registros. <br> Copia de seguridad guardada en: <strong>$backup_file</strong></div>";
            $count = 0; // Update count
        } else {
            $message = "<div class='alert alert-danger'>Error al eliminar: " . $conn->error . "</div>";
        }
        
    } else {
        $message = "<div class='alert alert-danger'>Error: No se pudo crear la copia de seguridad. Eliminacin cancelada.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Limpieza de Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0">Limpieza de Datos Antiguos (Hasta 2018)</h3>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                
                <p class="lead">Esta herramienta eliminar automticamente todos los trabajos realizados <strong>hasta el 31 de Diciembre de 2018</strong>.</p>
                <div class="alert alert-info">
                    <strong>Registros encontrados para eliminar:</strong> <?php echo $count; ?>
                </div>

                <?php if ($count > 0): ?>
                    <div class="alert alert-warning">
                        Antes de eliminar, se crear automticamente un archivo de respaldo (.json) en la carpeta del servidor.
                    </div>
                
                    <form method="POST">
                        <input type="hidden" name="confirm" value="yes">
                        <button type="submit" class="btn btn-danger btn-lg w-100" onclick="return confirm('Ests seguro? Esta accin borrar los datos de la base de datos.')">
                            🗑️ Eliminar <?php echo $count; ?> Registros Antiguos
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">La base de datos est limpia. No hay registros antiguos.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
