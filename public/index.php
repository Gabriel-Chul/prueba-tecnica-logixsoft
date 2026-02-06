<?php

require __DIR__ . '/../app/bootstrap.php';

$user = current_user();

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main class="container">
        <h1>Prueba Tecnica</h1>

        <?php if ($user): ?>
            <p>Bienvenido, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>.</p>
            <div class="actions">
                <a class="button" href="map.php">Ir al mapa</a>
                <a class="button button-secondary" href="logout.php">Cerrar sesion</a>
            </div>
        <?php else: ?>
            <p>Necesitas una cuenta para continuar.</p>
            <div class="actions">
                <a class="button" href="login.php">Iniciar sesion</a>
                <a class="button button-secondary" href="register.php">Registrarme</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
