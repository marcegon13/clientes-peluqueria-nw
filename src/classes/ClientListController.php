<?php
// src/classes/ClientListController.php

class ClientListController {
    private $conn;
    private $defaults = [
        'limit' => 50,
        'sort_by' => 'nombre',
        'sort_order' => 'ASC'
    ];

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function getParams() {
        $params = [];
        $params['sort_by'] = $_POST['sort_by'] ?? $this->defaults['sort_by'];
        $params['sort_order'] = $_POST['sort_order'] ?? $this->defaults['sort_order'];
        $params['sort_order'] = strtoupper($params['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : $this->defaults['limit'];
        $params['limit'] = ($limit < 50) ? 50 : $limit;
        
        // Search Term Logic
        // Prefer POST, then GET (for redirects), then empty
        $params['search_term'] = isset($_POST['search_term']) ? trim($_POST['search_term']) : (isset($_GET['search_term']) ? trim($_GET['search_term']) : '');
        
        // Mode logic
        $params['list_all'] = isset($_POST['list_all']);
        $params['active_mode'] = $_POST['active_mode'] ?? '';
        
        return $params;
    }

    public function fetchResults($params) {
        $searchResults = [];
        $hasMore = false;
        $searchPerformed = false;
        $listMode = false;
        
        $searchTerm = $params['search_term'];
        $sortBy = $params['sort_by'];
        $sortOrder = $params['sort_order'];
        $queryLimit = $params['limit'] + 1;

        // Determine if we should run a query
        $shouldSearch = false;
        
        // Case 1: Explicit "List All" or persisting List Mode with empty search
        if (($searchTerm !== '' || $_SERVER['REQUEST_METHOD'] === 'POST') && ($params['list_all'] || ($searchTerm === '' && $params['active_mode'] === 'list'))) {
            $shouldSearch = true;
            $listMode = true;
            $searchPerformed = true;
        } 
        // Case 2: Search term exists
        else if ($searchTerm !== '') {
            $shouldSearch = true;
            $searchPerformed = true;
        }

        if ($shouldSearch) {
            $validCols = ['nombre' => 'c.nombre', 'apellido' => 'c.apellido', 'fecha' => 'last_visit_date'];
            
            // Map 'fecha' to the alias of the subquery/join column to avoid SQL errors
            // Note: In the optimized query, we'll name the column 'last_visit_date'
            if ($sortBy === 'fecha') {
                $orderBySQL = 'last_visit_date';
            } else {
                $orderBySQL = $validCols[$sortBy] ?? 'c.nombre';
            }

            // Optimized Query:
            // We want Client Info + Latest Work Info.
            // Using a Correlated Subquery in SELECT is slow.
            // Using a JOIN with a derived table of "MAX(id) per client" is standard and usually faster.
            
            $sql = "
                SELECT 
                    c.id, 
                    c.nombre, 
                    c.apellido, 
                    t_details.fecha as last_visit_date,
                    t_details.trabajo_realizado,
                    t_details.estilista
                FROM clientes c
                LEFT JOIN (
                    SELECT t1.cliente_id, t1.fecha, t1.trabajo_realizado, t1.estilista
                    FROM trabajos t1
                    INNER JOIN (
                        SELECT cliente_id, MAX(id) as max_id
                        FROM trabajos
                        GROUP BY cliente_id
                    ) t_latest ON t1.id = t_latest.max_id
                ) t_details ON c.id = t_details.cliente_id
            ";

            $types = "";
            $bindings = [];

            if (!$listMode && $searchTerm !== '') {
                $sql .= " WHERE CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ?";
                $termWildcard = "%" . $searchTerm . "%";
                $types .= "sss";
                $bindings[] = $termWildcard;
                $bindings[] = $termWildcard;
                $bindings[] = $termWildcard;
            }

            $sql .= " ORDER BY $orderBySQL $sortOrder LIMIT ?";
            $types .= "i";
            $bindings[] = $queryLimit;

            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                if (!empty($bindings)) {
                    $stmt->bind_param($types, ...$bindings);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    // Map back to expected keys for view compatibility
                    $row['fecha'] = $row['last_visit_date']; 
                    $searchResults[] = $row;
                }
            }
        }

        // Pagination Check
        if (count($searchResults) > $params['limit']) {
            $hasMore = true;
            array_pop($searchResults);
        }

        return [
            'results' => $searchResults,
            'hasMore' => $hasMore,
            'searchPerformed' => $searchPerformed,
            'listMode' => $listMode
        ];
    }
    
    // View Helpers
    public static function getNextSortOrder($currentCol, $activeCol, $activeOrder) {
        if ($currentCol === $activeCol) {
            return $activeOrder === 'ASC' ? 'DESC' : 'ASC';
        }
        return 'ASC';
    }

    public static function getSortIcon($currentCol, $activeCol, $activeOrder) {
        if ($currentCol !== $activeCol) return '<i class="fas fa-sort text-muted small ms-1"></i>';
        return $activeOrder === 'ASC' ? '<i class="fas fa-sort-up text-primary small ms-1"></i>' : '<i class="fas fa-sort-down text-primary small ms-1"></i>';
    }
}
