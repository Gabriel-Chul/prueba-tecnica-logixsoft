<?php

require __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$name = '';
$email = '';

$csrf = new CsrfService($config['csrf_key']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!$csrf->validate($token)) {
        $errors[] = 'Token CSRF invalido.';
    } else {
        $users = new UserStore($config['storage_dir']);
        $rateLimiter = new RateLimiter(
            $config['storage_dir'],
            $config['rate_limit']['window_seconds'],
            $config['rate_limit']['max_attempts']
        );
        $auth = new AuthService($users, $rateLimiter, $config['storage_dir']);

        $result = $auth->register(
            $name,
            $email,
            $password,
            $confirm,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        );

        if ($result['ok']) {
            flash_set('success', 'Registro exitoso. Ahora puedes iniciar sesion.');
            redirect('login.php');
        }

        $errors = $result['errors'];
    }
}

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main class="container">
        <h1>Registro</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label for="name">Nombre</label>
            <input id="name" name="name" type="text" required value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="email">Correo</label>
            <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="password">Contrasena</label>
            <input id="password" name="password" type="password" required>

            <label for="confirm">Confirmar contrasena</label>
            <input id="confirm" name="confirm" type="password" required>

            <button class="button" type="submit">Crear cuenta</button>
        </form>

        <p class="helper">Ya tienes cuenta? <a href="login.php">Inicia sesion</a></p>
    </main>
</body>
</html>
