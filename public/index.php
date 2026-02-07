<?php

require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/RateLimiter.php';

if (current_user()) {
    redirect('map.php');
}

function is_ajax_request(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

$loginErrors = [];
$registerErrors = [];
$email = '';
$registerName = '';
$registerEmail = '';
$registerSuccess = flash_get('success');
$activePanel = 'login';

if (isset($_GET['view']) && $_GET['view'] === 'register') {
    $activePanel = 'register';
}

$csrf = new CsrfService($config['csrf_key']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'login';
    $token = $_POST['csrf_token'] ?? '';
    $sameOrigin = is_same_origin_request();

    if (!$sameOrigin) {
        $errorMessage = 'Origen de solicitud no valido.';
        if ($formType === 'register') {
            $registerErrors[] = $errorMessage;
            $activePanel = 'register';
        } else {
            $loginErrors[] = $errorMessage;
        }
    } elseif (!$csrf->validate($token)) {
        if ($formType === 'register') {
            $registerErrors[] = 'Token CSRF invalido.';
            $activePanel = 'register';
        } else {
            $loginErrors[] = 'Token CSRF invalido.';
        }
    } else {
        $users = new UserStore($config['storage_dir'], $config['encryption_key']);
        $rateLimiter = new RateLimiter(
            $config['storage_dir'],
            $config['rate_limit']['window_seconds'],
            $config['rate_limit']['max_attempts']
        );
        $emailLimiter = new RateLimiter(
            $config['storage_dir'],
            $config['rate_limit_email']['window_seconds'],
            $config['rate_limit_email']['max_attempts']
        );
        $auth = new AuthService($users, $rateLimiter, $emailLimiter, $config['storage_dir']);

        if ($formType === 'register') {
            $registerName = $_POST['name'] ?? '';
            $registerEmail = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';

            $result = $auth->register(
                $registerName,
                $registerEmail,
                $password,
                $confirm,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            );

            if ($result['ok']) {
                if (is_ajax_request()) {
                    $newToken = $csrf->rotate();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'ok' => true,
                        'message' => 'Registro exitoso. Ahora puedes iniciar sesion.',
                        'token' => $newToken,
                    ]);
                    exit;
                }

                $csrf->rotate();
                flash_set('success', 'Registro exitoso. Ahora puedes iniciar sesion.');
                redirect('index.php');
            }

            $registerErrors = $result['errors'];
            $activePanel = 'register';
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $result = $auth->login($email, $password, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            if ($result['ok']) {
                $csrf->rotate();
                redirect('map.php');
            }

            $loginErrors = $result['errors'];
        }
    }

    if (is_ajax_request() && $formType === 'register') {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'errors' => $registerErrors,
        ]);
        exit;
    }
}

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page<?php echo $activePanel === 'register' ? ' auth-page--register' : ''; ?>">
    <main class="auth-shell">
        <section class="auth-left" aria-hidden="true">
            <div class="auth-left__content">
                <p class="auth-tagline">Panel de Control Geografico</p>
                <p class="auth-credit">Hecho por</p>
                <p class="auth-name">Alfredo Gabriel Chul Moreno</p>
            </div>
        </section>

        <section class="auth-right">
            <div class="auth-card">
                <h1>Bienvenido de nuevo</h1>
                <p class="auth-subtitle">Acceda a su entorno de visualizacion seguro.</p>

                <?php if (!empty($registerSuccess)): ?>
                    <div class="auth-success" id="login-success">
                        <?php echo htmlspecialchars($registerSuccess, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else: ?>
                    <div class="auth-success" id="login-success" hidden></div>
                <?php endif; ?>

                <?php if (!empty($loginErrors)): ?>
                    <div class="alert">
                        <ul>
                            <?php foreach ($loginErrors as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="form_type" value="login">

                    <label for="login_email">Correo electronico</label>
                    <input id="login_email" name="email" type="email" autocomplete="username" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="login_password">Contrasena</label>
                    <input id="login_password" name="password" type="password" autocomplete="current-password" required>

                    <button class="auth-button" type="submit">Ingresar</button>
                </form>

                <p class="auth-helper">Aun no tienes cuenta? <a href="index.php?view=register" data-auth-toggle="register">Registrate aqui</a></p>
            </div>
        </section>

        <section class="auth-register" aria-hidden="<?php echo $activePanel === 'register' ? 'false' : 'true'; ?>">
            <div class="auth-card">
                <h1>Crear cuenta</h1>
                <p class="auth-subtitle">Configura tu acceso a la plataforma.</p>

                <?php if (!empty($registerErrors)): ?>
                    <div class="alert" id="register-errors">
                        <ul>
                            <?php foreach ($registerErrors as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert" id="register-errors" hidden>
                        <ul></ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form" id="register-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="form_type" value="register">

                    <label for="register_name">Nombre</label>
                    <input id="register_name" name="name" type="text" autocomplete="name" required value="<?php echo htmlspecialchars($registerName, ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="register_email">Correo electronico</label>
                    <input id="register_email" name="email" type="email" autocomplete="username" required value="<?php echo htmlspecialchars($registerEmail, ENT_QUOTES, 'UTF-8'); ?>">

                    <label for="register_password">Contrasena</label>
                    <input id="register_password" name="password" type="password" autocomplete="new-password" required>

                    <label for="register_confirm">Confirmar contrasena</label>
                    <input id="register_confirm" name="confirm" type="password" autocomplete="new-password" required>

                    <button class="auth-button" type="submit">Crear cuenta</button>
                </form>

                <p class="auth-helper">Ya tienes cuenta? <a href="index.php" data-auth-toggle="login">Inicia sesion</a></p>
            </div>
        </section>
    </main>

    <script src="assets/js/auth.js"></script>
</body>
</html>
