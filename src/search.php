<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// filepath: c:\wamp64\www\clientes-peluqueria\clientes-peluquerias\src\search.php
include 'db/connection.php';

if (isset($_GET['term'])) {
    $term = trim($_GET['term']);
    $stmt = $conn->prepare("SELECT id, nombre, apellido FROM clientes WHERE nombre LIKE ? OR apellido LIKE ?");
    if ($stmt === false) {
        echo json_encode(["error" => "Error en la consulta SQL: " . $conn->error]);
        exit();
    }
    $termWildcard = "%$term%";
    $stmt->bind_param("ss", $termWildcard, $termWildcard);
    $stmt->execute();
    $result = $stmt->get_result();

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    // Enviar la respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($clients);
    exit();
}
?>

<script>
function fetchSuggestions() {
    console.log("fetchSuggestions ejecutado"); // Depuración
    const search = document.getElementById('search').value;
    const suggestions = document.getElementById('suggestions');

    if (search.length > 0) {
        fetch(`search.php?term=${search}`)
            .then(response => response.json())
            .then(data => {
                console.log("Datos recibidos:", data); // Depuración
                suggestions.innerHTML = ''; // Limpiar la lista
                suggestions.style.display = 'block'; // Mostrar la lista

                if (data.length === 0) {
                    console.log("No se encontraron resultados"); // Depuración
                    const noResults = document.createElement('li');
                    noResults.className = 'list-group-item text-muted';
                    noResults.textContent = 'No se encontraron resultados';
                    suggestions.appendChild(noResults);
                } else {
                    console.log("Resultados encontrados:", data); // Depuración
                    data.forEach(client => {
                        const item = document.createElement('li');
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = `${client.nombre} ${client.apellido}`;
                        item.addEventListener('click', () => {
                            document.getElementById('search').value = `${client.nombre} ${client.apellido}`;
                            suggestions.innerHTML = '';
                            suggestions.style.display = 'none';
                        });
                        suggestions.appendChild(item);
                    });
                }
            })
            .catch(error => {
                console.error('Error al obtener sugerencias:', error);
            });
    } else {
        console.log("Campo de búsqueda vacío"); // Depuración
        suggestions.innerHTML = '';
        suggestions.style.display = 'none';
    }
}
</script>