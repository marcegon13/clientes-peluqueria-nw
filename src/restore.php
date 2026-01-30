<?php
include 'db/connection.php';

$message = "";

// Handle Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_file'])) {
    $file = $_POST['backup_file'];
    
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        
        if (is_array($data)) {
            $restored_count = 0;
            $error_count = 0;
            
            $stmt = $conn->prepare("INSERT INTO trabajos (id, cliente_id, fecha, estilista, trabajo_realizado) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($data as $row) {
                // Ensure ID is preserved
                $stmt->bind_param("iisss", $row['id'], $row['cliente_id'], $row['fecha'], $row['estilista'], $row['trabajo_realizado']);
                
                if ($stmt->execute()) {
                    $restored_count++;
                } else {
                    // Ignore duplicate key errors if we run restore twice
                    if ($conn->errno == 1062) { 
                        // Already exists, skip
                    } else {
                        $error_count++;
                    }
                }
            }
            
            $message = "<div class='alert alert-success'>Restauración completada. <br> Registros recuperados: <strong>$restored_count</strong>. <br> Errores/Duplicados: $error_count.</div>";
        } else {
            $message = "<div class='alert alert-danger'>El archivo de respaldo está corrupto o vacío.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Archivo no encontrado.</div>";
    }
}

// List Backups
$backup_files = glob("backup_deleted_*.json");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restaurar Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Restaurar Copia de Seguridad</h3>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                
                <h5 class="card-title">Archivos de Respaldo Disponibles</h5>
                
                <?php if (empty($backup_files)): ?>
                    <div class="alert alert-warning">No se encontraron archivos de respaldo (backup_deleted_*.json) en esta carpeta.</div>
                <?php else: ?>
                    <p>Selecciona un archivo para restaurar los datos eliminados.</p>
                    <div class="list-group">
                        <?php foreach ($backup_files as $file): ?>
                            <form method="POST" class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($file); ?></strong>
                                    <br>
                                    <small class="text-muted">Tamaño: <?php echo round(filesize($file) / 1024, 2); ?> KB - Fecha: <?php echo date("F d Y H:i:s", filemtime($file)); ?></small>
                                </div>
                                <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($file); ?>">
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Restaurar estos datos a la base de datos?')">
                                    ♻️ Restaurar
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">Volver al Inicio</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
