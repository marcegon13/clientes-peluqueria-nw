function fetchSuggestions() {
    console.log("fetchSuggestions ejecutado"); // Depuración
    const search = document.getElementById('search').value;
    const suggestions = document.getElementById('suggestions');

    if (search.length > 0) {
        fetch(`search.php?term=${search}`)
            .then(response => response.json())
            .then(data => {
                console.log("Datos recibidos:", data); // Depuración
                suggestions.innerHTML = '';
                suggestions.style.display = 'block';

                if (data.length === 0) {
                    const noResults = document.createElement('li');
                    noResults.className = 'list-group-item text-muted';
                    noResults.textContent = 'No se encontraron resultados';
                    suggestions.appendChild(noResults);
                } else {
                    data.forEach(client => {
                        const item = document.createElement('li');
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = `${client.nombre} ${client.apellido}`;
                        item.addEventListener('click', () => {
                            selectClient(client.id, client.nombre, client.apellido);
                        });
                        suggestions.appendChild(item);
                    });
                }
            })
            .catch(error => {
                console.error('Error al obtener sugerencias:', error);
            });
    } else {
        suggestions.innerHTML = '';
        suggestions.style.display = 'none';
    }
}

function selectClient(id, nombre, apellido) {
    const search = document.getElementById('search');
    const suggestions = document.getElementById('suggestions');

    search.value = `${nombre} ${apellido}`;
    suggestions.innerHTML = '';
    suggestions.style.display = 'none';

    console.log(`Cliente seleccionado: ID=${id}, Nombre=${nombre}, Apellido=${apellido}`);
}

function editWork(clientId) {
    // Aquí puedes cargar los datos del trabajo desde el servidor
    fetch(`getWork.php?clientId=${clientId}`)
        .then(response => response.json())
        .then(data => {
            // Llenar el modal con los datos del trabajo
            document.getElementById('editWorkModalTitle').textContent = `Editar Trabajo - Cliente ID: ${clientId}`;
            document.getElementById('editWorkField').value = data.trabajo_realizado || '';
            document.getElementById('editStylistField').value = data.estilista || '';
            document.getElementById('editDateField').value = data.fecha || '';

            // Mostrar el modal
            const editModal = new bootstrap.Modal(document.getElementById('editWorkModal'));
            editModal.show();
        })
        .catch(error => {
            console.error('Error al cargar los datos del trabajo:', error);
        });
}

function addWork(clientId) {
    // Configurar el modal para agregar un nuevo trabajo
    document.getElementById('addWorkModalTitle').textContent = `Agregar Trabajo - Cliente ID: ${clientId}`;
    document.getElementById('addWorkField').value = '';
    document.getElementById('addStylistField').value = '';
    document.getElementById('addDateField').value = '';

    // Mostrar el modal
    const addModal = new bootstrap.Modal(document.getElementById('addWorkModal'));
    addModal.show();
}