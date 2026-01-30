let currentFocus = -1;

function fetchSuggestions() {
    const searchInput = document.getElementById('search');
    const suggestions = document.getElementById('suggestions');
    const term = searchInput.value;

    if (term.length > 0) {
        fetch(`search.php?term=${term}`)
            .then(response => response.json())
            .then(data => {
                suggestions.innerHTML = '';
                suggestions.style.display = 'block';
                currentFocus = -1;

                if (data.length === 0) {
                    const noResults = document.createElement('li');
                    noResults.className = 'list-group-item text-muted';
                    noResults.textContent = 'No se encontraron resultados';
                    suggestions.appendChild(noResults);
                } else {
                    data.forEach((client, index) => {
                        const item = document.createElement('li');
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = `${client.nombre} ${client.apellido}`;
                        // Mouse event
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

// Keyboard navigation listener
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');

    // Trigger search when text changes
    searchInput.addEventListener('input', fetchSuggestions);

    searchInput.addEventListener('keydown', function (e) {
        const suggestions = document.getElementById('suggestions');
        const items = suggestions.getElementsByTagName('li');

        if (e.key === 'ArrowDown') {
            e.preventDefault(); // Prevent page scroll
            currentFocus++;
            addActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault(); // Prevent page scroll
            currentFocus--;
            addActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentFocus > -1 && items) {
                if (items[currentFocus]) {
                    items[currentFocus].click();
                }
            } else {
                // If no suggestion selected, submit normally
                this.form.submit();
            }
        }
    });
});

function addActive(items) {
    if (!items) return false;
    removeActive(items);
    if (currentFocus >= items.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = items.length - 1;
    items[currentFocus].classList.add('active');

    // Scroll into view logic could go here if list is long
    items[currentFocus].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeActive(items) {
    for (let i = 0; i < items.length; i++) {
        items[i].classList.remove('active');
    }
}

function selectClient(id, nombre, apellido) {
    const search = document.getElementById('search');
    const suggestions = document.getElementById('suggestions');

    search.value = `${nombre} ${apellido}`;
    suggestions.innerHTML = '';
    suggestions.style.display = 'none';

    // Auto-submit the form to "open" the client
    search.form.submit();
}

function editWork(clientId, trabajo, estilista, fecha) {
    document.getElementById('editClienteId').value = clientId;
    document.getElementById('editWorkField').value = trabajo || '';
    document.getElementById('editStylistField').value = estilista || '';
    document.getElementById('editDateField').value = fecha || '';

    // Optional: Update title if element exists
    const titleEl = document.getElementById('editWorkModalTitle');
    if (titleEl) {
        titleEl.textContent = `Editar Trabajo - Cliente ID: ${clientId}`;
    }

    const editModal = new bootstrap.Modal(document.getElementById('editWorkModal'));
    editModal.show();
}

function addWork(clientId) {
    document.getElementById('addClienteId').value = clientId;
    document.getElementById('addWorkField').value = '';
    document.getElementById('addStylistField').value = '';
    document.getElementById('addDateField').value = '';

    const titleEl = document.getElementById('addWorkModalTitle');
    if (titleEl) {
        titleEl.textContent = `Agregar Trabajo - Cliente ID: ${clientId}`;
    }

    const addModal = new bootstrap.Modal(document.getElementById('addWorkModal'));
    addModal.show();

    // Set default date to Today (Local)
    document.getElementById('addDateField').value = getLocalTodayDate();
}

function getLocalTodayDate() {
    const now = new Date();
    // Adjust to local timezone as YYYY-MM-DD
    const offset = now.getTimezoneOffset() * 60000;
    const local = new Date(now.getTime() - offset);
    return local.toISOString().split('T')[0];
}

// Initialize Register Modal Date on Load
document.addEventListener('DOMContentLoaded', () => {
    const newFechaInput = document.querySelector('input[name="new_fecha"]');
    if (newFechaInput) {
        newFechaInput.value = getLocalTodayDate();
    }
});