<?php
session_start();
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND password = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $_SESSION['user_id'] = $user_id;
        header('Location: index.php');
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}

// Handle add new work for client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_work'])) {
    $clientId = $_POST['client_id'];
    $date = $_POST['date'];
    $stylist = $_POST['stylist'];
    $workDone = $_POST['work_done'];

    $stmt = $conn->prepare("INSERT INTO trabajos (cliente_id, fecha, estilista, trabajo_realizado) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("isss", $clientId, $date, $stylist, $workDone);
    $stmt->execute();

    header('Location: index.php');
    exit();
}

// Handle update client work
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_work'])) {
    $workId = $_POST['work_id'];
    $date = $_POST['date'];
    $stylist = $_POST['stylist'];
    $workDone = $_POST['work_done'];

    $stmt = $conn->prepare("UPDATE trabajos SET fecha = ?, estilista = ?, trabajo_realizado = ? WHERE id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssi", $date, $stylist, $workDone, $workId);
    $stmt->execute();

    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Login - Peluquería</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }
        .login-container {
            width: 40%;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }
        .login-container h1 {
            margin-bottom: 20px;
        }
        .login-container input {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .login-container button {
            width: 40%;
            padding: 10px;
            margin: 10px 5%;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" name="login">Ingresar</button>
        </form>
        <form method="GET" action="register.php">
            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>