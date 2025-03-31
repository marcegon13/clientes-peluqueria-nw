<?php
include 'db/connection.php';

$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_term'])) {
    $searchTerm = trim($_POST['search_term']);
    $stmt = $conn->prepare("
        SELECT c.id, c.nombre, c.apellido, t.fecha, t.trabajo_realizado, t.estilista
        FROM clientes c
        LEFT JOIN trabajos t ON c.id = t.cliente_id
        WHERE CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ?
    ");
    $searchTermWildcard = "%" . $searchTerm . "%";
    $stmt->bind_param("sss", $searchTermWildcard, $searchTermWildcard, $searchTermWildcard);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
        #suggestions {
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .list-group-item {
            cursor: pointer;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="search-container">
        <h1 class="mb-4">Búsqueda de Clientes</h1>
        <form method="POST" action="index.php" class="position-relative">
    <div class="input-group mb-3">
        <input type="text" id="search" name="search_term" class="form-control" placeholder="Nombre o Apellido" autocomplete="off" onkeyup="fetchSuggestions()" required>
        <button type="submit" class="btn btn-primary">Buscar Cliente</button>
    </div>
    <ul id="suggestions" class="list-group position-absolute w-100" style="display: none;"></ul>
</form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerClientModal">Registrar Nuevo Cliente</button>
    </div>

    <?php if (!empty($searchResults)): ?>
        <h2 class="mt-4">Resultados de la búsqueda:</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Trabajo Realizado</th>
                        <th>Estilista</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($searchResults as $result): ?>
        <tr>
            <td><?php echo htmlspecialchars($result['nombre'] . ' ' . $result['apellido']); ?></td>
            <td><?php echo htmlspecialchars($result['fecha'] ?? 'Sin fecha'); ?></td>
            <td><?php echo htmlspecialchars($result['trabajo_realizado'] ?? 'Sin trabajo'); ?></td>
            <td><?php echo htmlspecialchars($result['estilista'] ?? 'Sin estilista'); ?></td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editWork(<?php echo $result['id']; ?>)">Editar</button>
                <button class="btn btn-success btn-sm" onclick="addWork(<?php echo $result['id']; ?>)">Agregar Trabajo</button>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-warning mt-4">No se encontraron resultados para "<?php echo htmlspecialchars($_POST['search_term']); ?>"</div>
    <?php endif; ?>

    <!-- Modal para registrar cliente -->
    <div class="modal fade" id="registerClientModal" tabindex="-1" aria-labelledby="registerClientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerClientModalLabel">Registrar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="register.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Trabajo -->
    <div class="modal fade" id="editWorkModal" tabindex="-1" aria-labelledby="editWorkModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWorkModalTitle">Editar Trabajo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editWorkForm">
                        <div class="mb-3">
                            <label for="editWorkField" class="form-label">Trabajo Realizado</label>
                            <input type="text" class="form-control" id="editWorkField" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStylistField" class="form-label">Estilista</label>
                            <input type="text" class="form-control" id="editStylistField" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDateField" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="editDateField" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" form="editWorkForm">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar Trabajo -->
    <div class="modal fade" id="addWorkModal" tabindex="-1" aria-labelledby="addWorkModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWorkModalTitle">Agregar Trabajo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addWorkForm">
                        <div class="mb-3">
                            <label for="addWorkField" class="form-label">Trabajo Realizado</label>
                            <input type="text" class="form-control" id="addWorkField" required>
                        </div>
                        <div class="mb-3">
                            <label for="addStylistField" class="form-label">Estilista</label>
                            <input type="text" class="form-control" id="addStylistField" required>
                        </div>
                        <div class="mb-3">
                            <label for="addDateField" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="addDateField" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" form="addWorkForm">Guardar Trabajo</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>