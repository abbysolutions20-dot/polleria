<?php

$search = trim((string) ($_GET['search'] ?? ''));
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);

        $action = (string) ($_POST['action'] ?? '');
        $clientId = (int) ($_POST['id'] ?? 0);
        $documentType = (string) ($_POST['tipo_documento'] ?? 'DNI');
        $documentNumber = trim((string) ($_POST['numero_documento'] ?? ''));
        $name = trim((string) ($_POST['nombre_razon_social'] ?? ''));
        $address = trim((string) ($_POST['direccion'] ?? ''));
        $phone = trim((string) ($_POST['telefono'] ?? ''));
        $email = trim((string) ($_POST['correo'] ?? ''));

        if ($action !== 'save_client') {
            throw new RuntimeException('Acción no reconocida.');
        }

        if ($documentNumber === '' || $name === '') {
            throw new RuntimeException('El documento y el nombre del cliente son obligatorios.');
        }

        if ($clientId > 0) {
            $db->execute(
                "UPDATE clientes
                 SET tipo_documento = :tipo_documento,
                     numero_documento = :numero_documento,
                     nombre_razon_social = :nombre,
                     direccion = :direccion,
                     telefono = :telefono,
                     correo = :correo
                 WHERE id = :id",
                [
                    'tipo_documento' => $documentType,
                    'numero_documento' => $documentNumber,
                    'nombre' => $name,
                    'direccion' => $address ?: null,
                    'telefono' => $phone ?: null,
                    'correo' => $email ?: null,
                    'id' => $clientId,
                ]
            );

            $flash->success('Cliente actualizado correctamente.');
        } else {
            $db->execute(
                "INSERT INTO clientes (tipo_documento, numero_documento, nombre_razon_social, direccion, telefono, correo)
                 VALUES (:tipo_documento, :numero_documento, :nombre, :direccion, :telefono, :correo)",
                [
                    'tipo_documento' => $documentType,
                    'numero_documento' => $documentNumber,
                    'nombre' => $name,
                    'direccion' => $address ?: null,
                    'telefono' => $phone ?: null,
                    'correo' => $email ?: null,
                ]
            );

            $flash->success('Cliente registrado correctamente.');
        }

        redirect('clients');
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());
        $redirectParams = [];

        if (!empty($_POST['id'])) {
            $redirectParams['edit'] = (int) $_POST['id'];
        }

        redirect('clients', $redirectParams);
    }
}

$conditions = ['activo = 1'];
$params = [];

if ($search !== '') {
    $conditions[] = '(nombre_razon_social LIKE :search_name OR numero_documento LIKE :search_doc OR telefono LIKE :search_phone)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_doc'] = '%' . $search . '%';
    $params['search_phone'] = '%' . $search . '%';
}

$clients = $db->fetchAll(
    "SELECT *
     FROM clientes
     WHERE " . implode(' AND ', $conditions) . "
     ORDER BY id DESC",
    $params
);

$clientForm = [
    'id' => 0,
    'tipo_documento' => 'DNI',
    'numero_documento' => '',
    'nombre_razon_social' => '',
    'direccion' => '',
    'telefono' => '',
    'correo' => '',
];

if ($editId > 0) {
    $clientForm = $db->fetch("SELECT * FROM clientes WHERE id = :id LIMIT 1", ['id' => $editId]) ?? $clientForm;
}

render_page($title, 'clients', [
    'csrf' => $csrf,
    'clients' => $clients,
    'clientForm' => $clientForm,
    'search' => $search,
    'editId' => $editId,
]);
