<?php
include 'db/connection.php';

// Configuration
$cutoff_date = '2018-12-31';
$keep_start_date = '2019-01-01'; // We keep works from this date onwards

// 1. Analysis
// Count works to delete (older than 2018-12-31)
$sql_count_works = "SELECT COUNT(*) as count FROM trabajos WHERE fecha < ? AND fecha != '0000-00-00'";
$stmt = $conn->prepare($sql_count_works);
$stmt->bind_param("s", $keep_start_date);
$stmt->execute();
$count_works = $stmt->get_result()->fetch_assoc()['count'];

// Count clients to delete (clients who have NO works from 2019 onwards)
// Logic: If a client has NO works with date >= 2019-01-01, they are considered inactive/old.
$sql_count_clients = "
    SELECT COUNT(*) as count 
    FROM clientes 
    WHERE id NOT IN (
        SELECT DISTINCT cliente_id 
        FROM trabajos 
        WHERE fecha >= ?
    )
";
$stmt2 = $conn->prepare($sql_count_clients);
$stmt2->bind_param("s", $keep_start_date);
$stmt2->execute();
$count_clients = $stmt2->get_result()->fetch_assoc()['count'];

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    
    // 2. Backup (Essential!)
    $data_backup = [];
    
    // Backup Works to be deleted
    $stmt_sel_works = $conn->prepare("SELECT * FROM trabajos WHERE fecha < ? AND fecha != '0000-00-00'");
    $stmt_sel_works->bind_param("s", $keep_start_date);
    $stmt_sel_works->execute();
    $data_backup['works'] = $stmt_sel_works->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Backup Clients to be deleted
    $stmt_sel_clients = $conn->prepare("
        SELECT * FROM clientes 
        WHERE id NOT IN (
            SELECT DISTINCT cliente_id FROM trabajos WHERE fecha >= ?
        )
    ");
    $stmt_sel_clients->bind_param("s", $keep_start_date);
    $stmt_sel_clients->execute();
    $data_backup['clients'] = $stmt_sel_clients->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $backup_file = 'backup_strict_cleanup_' . date('Y-m-d_H-i-s') . '.json';
    
    if (file_put_contents($backup_file, json_encode($data_backup))) {
        
        // 3. Execution (Order matters: Works first, then Clients)
        
        // Delete Works
        $stmt_del_works = $conn->prepare("DELETE FROM trabajos WHERE fecha < ? AND fecha != '0000-00-00'");
        $stmt_del_works->bind_param("s", $keep_start_date);
        $stmt_del_works->execute();
        $deleted_works = $stmt_del_works->affected_rows;
        
        // Delete Clients
        $stmt_del_clients = $conn->prepare("
            DELETE FROM clientes 
            WHERE id NOT IN (
                SELECT DISTINCT cliente_id FROM trabajos 
                -- Note: The subquery now returns IDs of clients who HAVE remaining works. 
                -- Since we just deleted old works, this only finds clients with works >= 2019.
                -- However, we must query the remaining works.
                -- Subquery logic: SELECT DISTINCT cliente_id FROM trabajos
            )
        ");
        // Simplified Logic: After deleting old works, any client who has NO entries in 'trabajos' is effectively an orphan or old client.
        // Wait, what if a client was JUST registered and has 0 works? They would be deleted.
        // The user said: "delete from 0 to 2018".
        // A new client with 0 works should prob be kept? 
        // User said: "que queden solo los registros que tienen fecha del 2019 hasta 2026".
        // A client with NO records has NO date. Technically fits the criteria of removal?
        // Let's stick to the user request: "Clean old data".
        // To be safe, I will stick to the logic: Delete clients who have NO works >= 2019.
        // If a new client has 0 works, they have NO works >= 2019. They will be deleted.
        // This is strict but matches the request "Keep only records 2019-2026".
        
        $stmt_del_clients = $conn->prepare("
             DELETE FROM clientes 
             WHERE id NOT IN (
                 SELECT DISTINCT cliente_id FROM trabajos
             )
        ");
        
        if ($stmt_del_clients->execute()) {
             $deleted_clients = $stmt_del_clients->affected_rows;
             $message = "<div class='alert alert-success'>
                <strong>Limpieza Completa:</strong><br>
                - Trabajos antiguos eliminados: $deleted_works<br>
                - Clientes inactivos eliminados: $deleted_clients<br>
                <br>
                Respaldo guardado en: <strong>$backup_file</strong>
             </div>";
             
             // Reset counts for display
             $count_works = 0;
             $count_clients = 0;
        } else {
            $message = "<div class='alert alert-danger'>Error al eliminar clientes: " . $conn->error . "</div>";
        }
        
    } else {
         $message = "<div class='alert alert-danger'>Error al crear respaldo. Operacin cancelada.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Limpieza Estricta (Pre-2019)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h3 class="mb-0">Limpieza Profunda (Datos anteriores a 2019)</h3>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                
                <p class="lead text-danger"><strong>ATENCIN:</strong> Esta herramienta realizar una limpieza masiva.</p>
                <ul>
                    <li>Se eliminarn TODOS los trabajos con fecha anterior al <strong>1 de Enero de 2019</strong>.</li>
                    <li>Se eliminarn TODOS los clientes que <strong>no tengan ningn trabajo</strong> registrado desde 2019 en adelante.</li>
                </ul>
                
                <div class="row text-center mb-4">
                    <div class="col-md-6">
                        <div class="card bg-white">
                            <div class="card-body">
                                <h2><?php echo $count_works; ?></h2>
                                <p class="text-muted">Trabajos Antiguos a Eliminar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-white">
                            <div class="card-body">
                                <h2><?php echo $count_clients; ?></h2>
                                <p class="text-muted">Clientes Inactivos a Eliminar</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($count_works > 0 || $count_clients > 0): ?>
                    <div class="alert alert-warning">
                        Se crear un respaldo automtico antes de borrar nada.
                    </div>
                
                    <form method="POST">
                        <input type="hidden" name="confirm" value="yes">
                        <button type="submit" class="btn btn-danger btn-lg w-100 py-3" onclick="return confirm('ESTS SEGURO? Esta accin borrar permanentemente datos antiguos.')">
                            🗑️ EJECUTAR LIMPIEZA PROFUNDA
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">La base de datos ya est limpia segn los criterios (Solo datos 2019-2026).</div>
                <?php endif; ?>
                
                 <div class="mt-3 text-center">
                    <a href="index.php" class="btn btn-link">Volver al Inicio</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
