<?php
$servername = "localhost";
$username = "root"; // Cambia esto si usas otro usuario
$password = ""; // Cambia esto si tienes una contraseña
$dbname = "peluqueria"; // Cambia esto al nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>