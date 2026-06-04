<?php

$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login === '' || $password === '') {
            throw new RuntimeException('Ingresa tu usuario o correo y tu contrasena.');
        }

        if (!$auth->attempt($login, $password)) {
            throw new RuntimeException('Credenciales invalidas. Si importaste la base semilla, usa la contrasena 123456 la primera vez.');
        }

        $flash->success('Bienvenido al panel de la polleria.');
        redirect($auth->homePage());
    } catch (Throwable $exception) {
        $loginError = $exception->getMessage();
    }
}

render_page($title, 'login', [
    'csrf' => $csrf,
    'loginError' => $loginError,
]);
