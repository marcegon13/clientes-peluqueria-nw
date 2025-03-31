# Clientes Peluquerías

Este proyecto es una aplicación web para la gestión de clientes en una peluquería. Permite a los usuarios registrarse, iniciar sesión y buscar información sobre los trabajos realizados a los clientes.

## Estructura del Proyecto

El proyecto está organizado en las siguientes carpetas y archivos:

- **src/**: Contiene los archivos PHP que manejan la lógica de la aplicación.
  - `index.php`: Página principal que muestra la interfaz de búsqueda de clientes y lista los trabajos realizados.
  - `login.php`: Maneja la lógica de inicio de sesión y validación de credenciales.
  - `register.php`: Permite el registro de nuevos usuarios en la base de datos.
  - `search.php`: Procesa la búsqueda de clientes y devuelve los trabajos realizados.
  - **modals/**: Contiene los archivos para los modales de registro de usuario y cliente.
    - `register_modal.php`: Modal para registrar un nuevo usuario.
    - `new_client_modal.php`: Modal para registrar un nuevo cliente.
  - **db/**: Contiene el archivo de conexión a la base de datos.
    - `connection.php`: Establece la conexión a la base de datos MySQL.

- **sql/**: Contiene el archivo SQL para crear la base de datos y tablas.
  - `peluqueria.sql`: Instrucciones SQL para crear la base de datos "Peluquería" y las tablas "usuarios" y "clientes".

- **css/**: Contiene los estilos CSS para la aplicación.
  - `styles.css`: Define la apariencia de las páginas y modales.

- **js/**: Contiene el código JavaScript para la lógica del cliente.
  - `scripts.js`: Maneja la apertura de modales y la interacción con la interfaz.

- `README.md`: Documentación del proyecto, incluyendo instrucciones de instalación y uso.

- `.gitignore`: Especifica los archivos y carpetas que deben ser ignorados por Git.

## Instalación

1. Clona el repositorio en tu máquina local.
2. Asegúrate de tener WAMP instalado y en funcionamiento.
3. Importa el archivo `sql/peluqueria.sql` en phpMyAdmin para crear la base de datos y las tablas necesarias.
4. Configura el archivo `src/db/connection.php` con tus credenciales de base de datos.
5. Abre `src/index.php` en tu navegador para acceder a la aplicación.

## Uso

- **Registro de Usuario**: Haz clic en el botón de registro para abrir el modal y crear una nueva cuenta.
- **Inicio de Sesión**: Ingresa tus credenciales en la página de inicio de sesión.
- **Buscar Cliente**: Utiliza la caja de texto en la página principal para buscar clientes por nombre y apellido.
- **Registrar Nuevo Cliente**: Haz clic en el botón para abrir el modal y registrar un nuevo cliente con los detalles requeridos.

## Contribuciones

Las contribuciones son bienvenidas. Si deseas contribuir, por favor abre un issue o envía un pull request.

## Licencia

Este proyecto está bajo la Licencia MIT.