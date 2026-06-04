<?php

if (!$auth->hasRole(['PROPIETARIO', 'ADMINISTRADOR'])) {
    $flash->error('Esta seccion esta disponible solo para propietario y administrador.');
    redirect('dashboard');
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$search = trim((string) ($_GET['search'] ?? ''));
$allowedRoleNames = ['CAJERO', 'MOZO', 'COCINA', 'DELIVERY'];

$placeholders = implode(', ', array_fill(0, count($allowedRoleNames), '?'));
$roles = $db->fetchAll(
    "SELECT id, nombre, descripcion
     FROM roles
     WHERE activo = 1
       AND nombre IN ($placeholders)
     ORDER BY FIELD(nombre, 'CAJERO', 'MOZO', 'COCINA', 'DELIVERY')",
    $allowedRoleNames
);

$allowedRoleIds = array_map(static fn (array $role): int => (int) $role['id'], $roles);
$branches = $db->fetchAll(
    "SELECT id, nombre
     FROM sucursales
     WHERE activo = 1
     ORDER BY nombre"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_user') {
            $userId = (int) ($_POST['id'] ?? 0);
            $branchId = (int) ($_POST['id_sucursal'] ?? 0);
            $roleId = (int) ($_POST['id_rol'] ?? 0);
            $firstName = trim((string) ($_POST['nombres'] ?? ''));
            $lastName = trim((string) ($_POST['apellidos'] ?? ''));
            $username = trim((string) ($_POST['usuario'] ?? ''));
            $email = trim((string) ($_POST['correo'] ?? ''));
            $phone = trim((string) ($_POST['telefono'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $active = isset($_POST['activo']) ? 1 : 0;

            if ($branchId <= 0 || !in_array($roleId, $allowedRoleIds, true) || $firstName === '' || $username === '') {
                throw new RuntimeException('Completa sucursal, rol, nombres y usuario.');
            }

            if ($userId === 0 && $password === '') {
                throw new RuntimeException('La contraseña es obligatoria para crear el usuario.');
            }

            $existsUsername = $db->value(
                "SELECT COUNT(*)
                 FROM usuarios
                 WHERE usuario = :usuario
                   AND id <> :id",
                [
                    'usuario' => $username,
                    'id' => $userId,
                ]
            );

            if ((int) $existsUsername > 0) {
                throw new RuntimeException('El nombre de usuario ya existe.');
            }

            if ($email !== '') {
                $existsEmail = $db->value(
                    "SELECT COUNT(*)
                     FROM usuarios
                     WHERE correo = :correo
                       AND id <> :id",
                    [
                        'correo' => $email,
                        'id' => $userId,
                    ]
                );

                if ((int) $existsEmail > 0) {
                    throw new RuntimeException('El correo ya esta registrado en otro usuario.');
                }
            }

            if ($userId > 0) {
                $existingUser = $db->fetch(
                    "SELECT u.id, r.nombre AS rol_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON r.id = u.id_rol
                     WHERE u.id = :id
                     LIMIT 1",
                    ['id' => $userId]
                );

                if (!$existingUser || !in_array((string) $existingUser['rol_nombre'], $allowedRoleNames, true)) {
                    throw new RuntimeException('Solo puedes editar usuarios operativos.');
                }

                $params = [
                    'id_sucursal' => $branchId,
                    'id_rol' => $roleId,
                    'nombres' => $firstName,
                    'apellidos' => $lastName !== '' ? $lastName : null,
                    'usuario' => $username,
                    'correo' => $email !== '' ? $email : null,
                    'telefono' => $phone !== '' ? $phone : null,
                    'activo' => $active,
                    'id' => $userId,
                ];

                $sql = "UPDATE usuarios
                        SET id_sucursal = :id_sucursal,
                            id_rol = :id_rol,
                            nombres = :nombres,
                            apellidos = :apellidos,
                            usuario = :usuario,
                            correo = :correo,
                            telefono = :telefono,
                            activo = :activo";

                if ($password !== '') {
                    $sql .= ", password_hash = :password_hash";
                    $params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
                }

                $sql .= " WHERE id = :id";

                $db->execute($sql, $params);
                $flash->success('Usuario actualizado correctamente.');
            } else {
                $db->execute(
                    "INSERT INTO usuarios (
                        id_sucursal, id_rol, nombres, apellidos, usuario, correo, telefono, password_hash, activo
                     ) VALUES (
                        :id_sucursal, :id_rol, :nombres, :apellidos, :usuario, :correo, :telefono, :password_hash, :activo
                     )",
                    [
                        'id_sucursal' => $branchId,
                        'id_rol' => $roleId,
                        'nombres' => $firstName,
                        'apellidos' => $lastName !== '' ? $lastName : null,
                        'usuario' => $username,
                        'correo' => $email !== '' ? $email : null,
                        'telefono' => $phone !== '' ? $phone : null,
                        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                        'activo' => $active,
                    ]
                );

                $flash->success('Usuario creado correctamente.');
            }

            redirect('users');
        }

        if ($action === 'toggle_user') {
            $userId = (int) ($_POST['id'] ?? 0);
            $active = (int) ($_POST['activo'] ?? 0);

            $existingUser = $db->fetch(
                "SELECT u.id, r.nombre AS rol_nombre
                 FROM usuarios u
                 INNER JOIN roles r ON r.id = u.id_rol
                 WHERE u.id = :id
                 LIMIT 1",
                ['id' => $userId]
            );

            if (!$existingUser || !in_array((string) $existingUser['rol_nombre'], $allowedRoleNames, true)) {
                throw new RuntimeException('Solo puedes cambiar el estado de usuarios operativos.');
            }

            $db->execute(
                "UPDATE usuarios
                 SET activo = :activo
                 WHERE id = :id",
                [
                    'activo' => $active,
                    'id' => $userId,
                ]
            );

            $flash->success($active ? 'Usuario reactivado correctamente.' : 'Usuario desactivado correctamente.');
            redirect('users');
        }

        throw new RuntimeException('Accion no reconocida.');
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());
        $redirectParams = [];

        if (!empty($_POST['id'])) {
            $redirectParams['edit'] = (int) $_POST['id'];
        }

        redirect('users', $redirectParams);
    }
}

$conditions = ["r.nombre IN ('CAJERO', 'MOZO', 'COCINA', 'DELIVERY')"];
$params = [];

if ($search !== '') {
    $conditions[] = '(u.usuario LIKE :search_user OR u.nombres LIKE :search_name OR u.apellidos LIKE :search_last OR u.correo LIKE :search_email)';
    $params['search_user'] = '%' . $search . '%';
    $params['search_name'] = '%' . $search . '%';
    $params['search_last'] = '%' . $search . '%';
    $params['search_email'] = '%' . $search . '%';
}

$users = $db->fetchAll(
    "SELECT u.*, r.nombre AS rol_nombre, s.nombre AS sucursal_nombre
     FROM usuarios u
     INNER JOIN roles r ON r.id = u.id_rol
     LEFT JOIN sucursales s ON s.id = u.id_sucursal
     WHERE " . implode(' AND ', $conditions) . "
     ORDER BY u.id DESC",
    $params
);

$userForm = [
    'id' => 0,
    'id_sucursal' => '',
    'id_rol' => '',
    'nombres' => '',
    'apellidos' => '',
    'usuario' => '',
    'correo' => '',
    'telefono' => '',
    'activo' => 1,
];

if ($editId > 0) {
    $editUser = $db->fetch(
        "SELECT u.*, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.id_rol
         WHERE u.id = :id
         LIMIT 1",
        ['id' => $editId]
    );

    if ($editUser && in_array((string) $editUser['rol_nombre'], $allowedRoleNames, true)) {
        $userForm = $editUser;
    }
}

render_page($title, 'users', [
    'csrf' => $csrf,
    'roles' => $roles,
    'branches' => $branches,
    'users' => $users,
    'userForm' => $userForm,
    'editId' => $editId,
    'search' => $search,
]);
