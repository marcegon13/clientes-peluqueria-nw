<?php
include 'db/connection.php';

$searchResults = [];
$searchPerformed = false;
$listMode = false;

// Sorting parameters
$sortBy = $_POST['sort_by'] ?? 'nombre'; // Default sort
$sortOrder = $_POST['sort_order'] ?? 'ASC'; // Default order
$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC'; // Validate

// Limit parameters
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 50;
if ($limit < 50) $limit = 50;
$queryLimit = $limit + 1; // Fetch one extra to check if there are more
$hasMore = false;

// Helper to determine next sort order for UI
function getNextSortOrder($currentCol, $activeCol, $activeOrder) {
    if ($currentCol === $activeCol) {
        return $activeOrder === 'ASC' ? 'DESC' : 'ASC';
    }
    return 'ASC';
}

function getSortIcon($currentCol, $activeCol, $activeOrder) {
    if ($currentCol !== $activeCol) return '<i class="fas fa-sort text-muted small ms-1"></i>';
    return $activeOrder === 'ASC' ? '<i class="fas fa-sort-up text-primary small ms-1"></i>' : '<i class="fas fa-sort-down text-primary small ms-1"></i>';
}

// Logic: Search Term takes precedence over List All if typed.
// ALLOW GET for search_term to support redirection
$searchTerm = isset($_POST['search_term']) ? trim($_POST['search_term']) : (isset($_GET['search_term']) ? trim($_GET['search_term']) : '');

if (($_SERVER['REQUEST_METHOD'] === 'POST' || $searchTerm !== '') && (isset($_POST['list_all']) || ($searchTerm === '' && isset($_POST['active_mode']) && $_POST['active_mode'] === 'list'))) {
    $searchPerformed = true;
    $listMode = true;
    
    // Valid columns for SQL
    $validCols = ['nombre' => 'c.nombre', 'apellido' => 'c.apellido', 'fecha' => 'fecha'];
    $orderBySQL = $validCols[$sortBy] ?? 'c.nombre';

    $stmt = $conn->prepare("
        SELECT c.id, c.nombre, c.apellido, MAX(t.fecha) as fecha, 
               (SELECT trabajo_realizado FROM trabajos t2 WHERE t2.cliente_id = c.id ORDER BY fecha DESC LIMIT 1) as trabajo_realizado,
               (SELECT estilista FROM trabajos t3 WHERE t3.cliente_id = c.id ORDER BY fecha DESC LIMIT 1) as estilista
        FROM clientes c
        LEFT JOIN trabajos t ON c.id = t.cliente_id
        GROUP BY c.id
        ORDER BY $orderBySQL $sortOrder
        LIMIT ?
    ");
    $stmt->bind_param("i", $queryLimit);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
}
else if ($searchTerm !== '') { 
    // Search by term (POST or GET)
    $searchPerformed = true;
    
    $validCols = ['nombre' => 'c.nombre', 'apellido' => 'c.apellido', 'fecha' => 't.fecha'];
    $orderBySQL = $validCols[$sortBy] ?? 'c.nombre';

    $stmt = $conn->prepare("
        SELECT c.id, c.nombre, c.apellido, MAX(t.fecha) as fecha,
               (SELECT trabajo_realizado FROM trabajos t2 WHERE t2.cliente_id = c.id ORDER BY fecha DESC LIMIT 1) as trabajo_realizado,
               (SELECT estilista FROM trabajos t3 WHERE t3.cliente_id = c.id ORDER BY fecha DESC LIMIT 1) as estilista
        FROM clientes c
        LEFT JOIN trabajos t ON c.id = t.cliente_id
        WHERE CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ?
        GROUP BY c.id
        ORDER BY $orderBySQL $sortOrder
        LIMIT ?
    ");
    
    $searchTermWildcard = "%" . $searchTerm . "%";
    $stmt->bind_param("sssi", $searchTermWildcard, $searchTermWildcard, $searchTermWildcard, $queryLimit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
}

// Check if we have more results than the limit
if (count($searchResults) > $limit) {
    $hasMore = true;
    array_pop($searchResults); // Remove the extra item
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Peluquería</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .search-area {
            max-width: 900px;
            margin: 0 auto;
        }
        .search-form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .user-select-none {
            user-select: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Title & Search Section -->
        <div class="search-area text-center mb-5">
            <h2 class="mb-4 fw-light text-secondary">Clientes</h2>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show mx-auto" style="max-width: 600px;" role="alert">
                    <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($_GET['msg'] ?? 'Operación exitosa'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mx-auto" style="max-width: 600px;" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form id="mainForm" method="POST" action="index.php" class="position-relative">
                <!-- State Management Inputs -->
                <input type="hidden" name="sort_by" id="sortBy" value="<?php echo $sortBy; ?>">
                <input type="hidden" name="sort_order" id="sortOrder" value="<?php echo $sortOrder; ?>">
                <input type="hidden" name="limit" id="limitInput" value="<?php echo $limit; ?>">
                
                <?php if ($listMode): ?>
                    <input type="hidden" name="active_mode" value="list">
                <?php endif; ?>

                <div class="search-form-container">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="search" name="search_term" class="form-control border-start-0 ps-0" placeholder="Buscar cliente..." autocomplete="off" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="btn btn-primary" onclick="resetLimit()">Buscar</button>
                        <button type="submit" name="list_all" value="1" class="btn btn-outline-secondary" title="Ver lista completa" onclick="resetLimitAndClearSearch()"><i class="fas fa-list"></i> Todos</button>
                    </div>
                    <ul id="suggestions" class="list-group position-absolute w-100 text-start" style="top: 100%; z-index: 1050; max-width: 600px;"></ul>
                </div>
                
                <div class="mt-3">
                     <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#registerClientModal">
                        <i class="fas fa-plus me-1"></i> Nuevo Cliente
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($searchResults)): ?>
            <!-- Results Table -->
            <div class="card shadow-sm border-0 search-area mb-5">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">ID</th>
                                    
                                    <th class="cursor-pointer user-select-none" onclick="sortList('nombre', '<?php echo getNextSortOrder('nombre', $sortBy, $sortOrder); ?>')">
                                        Nombre <?php echo getSortIcon('nombre', $sortBy, $sortOrder); ?>
                                    </th>
                                    
                                    <th class="cursor-pointer user-select-none" onclick="sortList('apellido', '<?php echo getNextSortOrder('apellido', $sortBy, $sortOrder); ?>')">
                                        Apellido <?php echo getSortIcon('apellido', $sortBy, $sortOrder); ?>
                                    </th>
                                    
                                    <th class="cursor-pointer user-select-none" onclick="sortList('fecha', '<?php echo getNextSortOrder('fecha', $sortBy, $sortOrder); ?>')">
                                        <?php echo $listMode ? 'Última Visita' : 'Fecha'; ?> <?php echo getSortIcon('fecha', $sortBy, $sortOrder); ?>
                                    </th>
                                    
                                    <th><?php echo $listMode ? 'Último Trabajo' : 'Trabajo Realizado'; ?></th>
                                    <th class="text-end pe-3" style="width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchResults as $result): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?php echo $result['id']; ?></td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($result['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($result['apellido']); ?></td>
                                        <td class="small text-muted"><?php echo htmlspecialchars($result['fecha'] ?? '-'); ?></td>
                                        <td class="small text-muted text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($result['trabajo_realizado'] ?? '-'); ?>
                                            <?php if(isset($result['estilista']) && $result['estilista']): ?>
                                                <span class="badge bg-light text-secondary border ms-1"><?php echo htmlspecialchars($result['estilista']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-icon btn-warning text-white me-1" onclick="editWork(<?php echo $result['id']; ?>, '<?php echo htmlspecialchars($result['trabajo_realizado'] ?? ''); ?>', '<?php echo htmlspecialchars($result['estilista'] ?? ''); ?>', '<?php echo htmlspecialchars($result['fecha'] ?? ''); ?>')" title="Editar Trabajo">
                                                    <i class="fas fa-pen fa-xs"></i>
                                                </button>
                                                <button class="btn btn-icon btn-success" onclick="addWork(<?php echo $result['id']; ?>)" title="Agregar Trabajo">
                                                    <i class="fas fa-plus fa-xs"></i>
                                                </button>
                                                <button class="btn btn-icon btn-danger ms-1" onclick="confirmDelete(<?php echo $result['id']; ?>, '<?php echo htmlspecialchars($result['nombre'] . ' ' . $result['apellido']); ?>')" title="Eliminar Cliente">
                                                    <i class="fas fa-trash fa-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($hasMore): ?>
                <div class="card-footer bg-white border-top-0 text-center py-3">
                    <button type="button" class="btn btn-outline-primary btn-sm px-4" onclick="loadMore()">
                        <i class="fas fa-arrow-down me-1"></i> Cargar más clientes
                    </button>
                    <div class="text-muted small mt-2">Mostrando <?php echo $limit; ?> resultados</div>
                </div>
                <?php endif; ?>
            </div>
        <?php elseif ($searchPerformed): ?>
            <div class="search-area alert alert-warning text-center mt-3 shadow-sm border-0">
                <i class="fas fa-exclamation-circle me-1"></i> No se encontraron resultados.
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Registrar Cliente (NEW) -->
    <div class="modal fade" id="registerClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fs-6">Nuevo Cliente</h5>
                    <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="register.php">
                    <div class="modal-body pt-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" class="form-control" name="nombre" placeholder="Nombre" required>
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control" name="apellido" placeholder="Apellido" required>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <hr class="my-2 text-muted">
                                <small class="text-muted fw-bold d-block mb-2">Primer Trabajo (Opcional)</small>
                            </div>
                            
                            <div class="col-12">
                                <input type="text" class="form-control" name="new_trabajo" placeholder="Trabajo Realizado (Ej: Corte, Tinte)">
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control" name="new_estilista" placeholder="Estilista">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" name="new_fecha">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="submit" class="btn btn-primary w-100">Guardar Cliente y Trabajo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Trabajo -->
    <div class="modal fade" id="editWorkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fs-6" id="editWorkModalTitle">Editar Trabajo</h5>
                    <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editWorkForm" method="POST" action="process_edit.php">
                    <input type="hidden" name="cliente_id" id="editClienteId">
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label text-muted small mb-1">Trabajo Realizado</label>
                                <input type="text" class="form-control" id="editWorkField" name="trabajo_realizado" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Estilista</label>
                                <input type="text" class="form-control" id="editStylistField" name="estilista" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Fecha</label>
                                <input type="date" class="form-control" id="editDateField" name="fecha" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fs-6 text-danger">Eliminar Cliente</h5>
                    <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Estás a punto de eliminar al cliente <strong id="deleteClientName"></strong>.</p>
                    <p class="small text-muted mt-2 mb-0">Esta acción borrará también <strong>todo su historial de trabajos</strong>. No se puede deshacer.</p>
                </div>
                <div class="modal-footer border-top-0">
                    <form method="POST" action="process_delete.php">
                        <input type="hidden" name="delete_id" id="deleteClientId">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm px-4">Sí, Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Trabajo (Existing) -->
    <div class="modal fade" id="addWorkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fs-6" id="addWorkModalTitle">Agregar Trabajo</h5>
                    <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addWorkForm" method="POST" action="process_add.php">
                    <input type="hidden" name="cliente_id" id="addClienteId">
                    <div class="modal-body">
                         <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label text-muted small mb-1">Trabajo Realizado</label>
                                <input type="text" class="form-control" id="addWorkField" name="trabajo_realizado" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Estilista</label>
                                <input type="text" class="form-control" id="addStylistField" name="estilista" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Fecha</label>
                                <input type="date" class="form-control" id="addDateField" name="fecha" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js?v=<?php echo time(); ?>"></script>
    <script>
        function sortList(column, order) {
            document.getElementById('sortBy').value = column;
            document.getElementById('sortOrder').value = order;
            document.getElementById('mainForm').submit();
        }

        function loadMore() {
            var limitInput = document.getElementById('limitInput');
            var currentLimit = parseInt(limitInput.value);
            limitInput.value = currentLimit + 50;
            document.getElementById('mainForm').submit();
        }

        function resetLimit() {
            document.getElementById('limitInput').value = 50;
        }

        function resetLimitAndClearSearch() {
            document.getElementById('limitInput').value = 50;
            document.getElementById('search').value = '';
        }

        function confirmDelete(id, name) {
            document.getElementById('deleteClientId').value = id;
            document.getElementById('deleteClientName').textContent = name;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteClientModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>