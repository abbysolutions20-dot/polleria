<?php

if (!$auth->isOwner()) {
    $flash->error('Esta seccion esta disponible solo para el propietario.');
    redirect('dashboard');
}

$section = (string) ($_GET['section'] ?? 'categories');
$editCategoryId = isset($_GET['edit_category']) ? (int) $_GET['edit_category'] : 0;
$editAreaId = isset($_GET['edit_area']) ? (int) $_GET['edit_area'] : 0;
$editTableId = isset($_GET['edit_table']) ? (int) $_GET['edit_table'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_category') {
            $categoryId = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['nombre'] ?? ''));
            $description = trim((string) ($_POST['descripcion'] ?? ''));
            $categoryType = trim((string) ($_POST['tipo'] ?? ''));
            $active = isset($_POST['activo']) ? 1 : 0;

            if ($name === '') {
                throw new RuntimeException('La categoria debe tener un nombre.');
            }

            $payload = [
                'nombre' => $name,
                'descripcion' => $description !== '' ? $description : null,
                'tipo' => $categoryType !== '' ? strtoupper($categoryType) : null,
                'activo' => $active,
            ];

            if ($categoryId > 0) {
                $payload['id'] = $categoryId;
                $db->execute(
                    "UPDATE categorias_producto
                     SET nombre = :nombre,
                         descripcion = :descripcion,
                         tipo = :tipo,
                         activo = :activo
                     WHERE id = :id",
                    $payload
                );
                $flash->success('Categoria actualizada correctamente.');
            } else {
                $db->execute(
                    "INSERT INTO categorias_producto (nombre, descripcion, tipo, activo)
                     VALUES (:nombre, :descripcion, :tipo, :activo)",
                    $payload
                );
                $flash->success('Categoria creada correctamente.');
            }

            redirect('masters', ['section' => 'categories']);
        }

        if ($action === 'delete_category') {
            $categoryId = (int) ($_POST['id'] ?? 0);

            if ($categoryId <= 0) {
                throw new RuntimeException('Selecciona una categoria valida.');
            }

            $db->execute(
                "UPDATE categorias_producto
                 SET activo = 0
                 WHERE id = :id",
                ['id' => $categoryId]
            );

            $flash->success('Categoria desactivada correctamente.');
            redirect('masters', ['section' => 'categories']);
        }

        if ($action === 'save_area') {
            $areaId = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['nombre'] ?? ''));
            $description = trim((string) ($_POST['descripcion'] ?? ''));
            $active = isset($_POST['activo']) ? 1 : 0;

            if ($name === '') {
                throw new RuntimeException('El area debe tener un nombre.');
            }

            $payload = [
                'nombre' => strtoupper($name),
                'descripcion' => $description !== '' ? $description : null,
                'activo' => $active,
            ];

            if ($areaId > 0) {
                $payload['id'] = $areaId;
                $db->execute(
                    "UPDATE areas_preparacion
                     SET nombre = :nombre,
                         descripcion = :descripcion,
                         activo = :activo
                     WHERE id = :id",
                    $payload
                );
                $flash->success('Area actualizada correctamente.');
            } else {
                $db->execute(
                    "INSERT INTO areas_preparacion (nombre, descripcion, activo)
                     VALUES (:nombre, :descripcion, :activo)",
                    $payload
                );
                $flash->success('Area creada correctamente.');
            }

            redirect('masters', ['section' => 'areas']);
        }

        if ($action === 'delete_area') {
            $areaId = (int) ($_POST['id'] ?? 0);

            if ($areaId <= 0) {
                throw new RuntimeException('Selecciona un area valida.');
            }

            $db->execute(
                "UPDATE areas_preparacion
                 SET activo = 0
                 WHERE id = :id",
                ['id' => $areaId]
            );

            $flash->success('Area desactivada correctamente.');
            redirect('masters', ['section' => 'areas']);
        }

        if ($action === 'add_product_type') {
            $masterDataService->addProductType((string) ($_POST['nuevo_tipo'] ?? ''));
            $flash->success('Tipo agregado correctamente.');
            redirect('masters', ['section' => 'types']);
        }

        if ($action === 'rename_product_type') {
            $masterDataService->renameProductType(
                (string) ($_POST['tipo_actual'] ?? ''),
                (string) ($_POST['nuevo_nombre'] ?? '')
            );
            $flash->success('Tipo actualizado correctamente.');
            redirect('masters', ['section' => 'types']);
        }

        if ($action === 'delete_product_type') {
            $replacement = trim((string) ($_POST['tipo_reemplazo'] ?? ''));

            $masterDataService->deleteProductType(
                (string) ($_POST['tipo_actual'] ?? ''),
                $replacement !== '' ? $replacement : null
            );

            $flash->success('Tipo eliminado correctamente.');
            redirect('masters', ['section' => 'types']);
        }

        if ($action === 'save_table') {
            $tableId = (int) ($_POST['id'] ?? 0);
            $branchId = (int) ($_POST['id_sucursal'] ?? 0);
            $zoneId = (int) ($_POST['id_zona'] ?? 0);
            $number = trim((string) ($_POST['numero'] ?? ''));
            $name = trim((string) ($_POST['nombre'] ?? ''));
            $capacity = max(1, (int) ($_POST['capacidad'] ?? 1));
            $state = (string) ($_POST['estado'] ?? 'LIBRE');
            $active = isset($_POST['activo']) ? 1 : 0;

            if ($branchId <= 0 || $number === '') {
                throw new RuntimeException('La mesa debe tener sucursal y numero.');
            }

            if (!in_array($state, ['LIBRE', 'OCUPADA', 'RESERVADA', 'INACTIVA'], true)) {
                throw new RuntimeException('Selecciona un estado valido para la mesa.');
            }

            if (!$active) {
                $state = 'INACTIVA';
            }

            $payload = [
                'id_sucursal' => $branchId,
                'id_zona' => $zoneId > 0 ? $zoneId : null,
                'numero' => $number,
                'nombre' => $name !== '' ? $name : null,
                'capacidad' => $capacity,
                'estado' => $state,
                'activo' => $active,
            ];

            if ($tableId > 0) {
                $payload['id'] = $tableId;
                $db->execute(
                    "UPDATE mesas
                     SET id_sucursal = :id_sucursal,
                         id_zona = :id_zona,
                         numero = :numero,
                         nombre = :nombre,
                         capacidad = :capacidad,
                         estado = :estado,
                         activo = :activo
                     WHERE id = :id",
                    $payload
                );
                $flash->success('Mesa actualizada correctamente.');
            } else {
                $db->execute(
                    "INSERT INTO mesas (id_sucursal, id_zona, numero, nombre, capacidad, estado, activo)
                     VALUES (:id_sucursal, :id_zona, :numero, :nombre, :capacidad, :estado, :activo)",
                    $payload
                );
                $flash->success('Mesa creada correctamente.');
            }

            redirect('masters', ['section' => 'tables']);
        }

        if ($action === 'delete_table') {
            $tableId = (int) ($_POST['id'] ?? 0);

            if ($tableId <= 0) {
                throw new RuntimeException('Selecciona una mesa valida.');
            }

            $db->execute(
                "UPDATE mesas
                 SET activo = 0,
                     estado = 'INACTIVA'
                 WHERE id = :id",
                ['id' => $tableId]
            );

            $flash->success('Mesa desactivada correctamente.');
            redirect('masters', ['section' => 'tables']);
        }

        throw new RuntimeException('Accion no reconocida.');
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());
        redirect('masters', ['section' => $section]);
    }
}

$categories = $db->fetchAll(
    "SELECT *
     FROM categorias_producto
     ORDER BY activo DESC, nombre"
);

$areas = $db->fetchAll(
    "SELECT *
     FROM areas_preparacion
     ORDER BY activo DESC, nombre"
);

$productTypes = $masterDataService->productTypes();
$productTypeUsageRows = $db->fetchAll(
    "SELECT tipo_producto, COUNT(*) AS total
     FROM productos
     GROUP BY tipo_producto"
);
$productTypeUsage = [];

foreach ($productTypeUsageRows as $row) {
    $productTypeUsage[$row['tipo_producto']] = (int) $row['total'];
}

$branches = $db->fetchAll(
    "SELECT id, nombre
     FROM sucursales
     WHERE activo = 1
     ORDER BY nombre"
);

$zones = $db->fetchAll(
    "SELECT z.id, z.id_sucursal, z.nombre, s.nombre AS sucursal
     FROM zonas_mesa z
     INNER JOIN sucursales s ON s.id = z.id_sucursal
     WHERE z.activo = 1
     ORDER BY s.nombre, z.nombre"
);

$tables = $db->fetchAll(
    "SELECT m.*, s.nombre AS sucursal, z.nombre AS zona
     FROM mesas m
     INNER JOIN sucursales s ON s.id = m.id_sucursal
     LEFT JOIN zonas_mesa z ON z.id = m.id_zona
     ORDER BY m.activo DESC, s.nombre, m.numero"
);

$categoryForm = [
    'id' => 0,
    'nombre' => '',
    'descripcion' => '',
    'tipo' => '',
    'activo' => 1,
];

if ($editCategoryId > 0) {
    $categoryForm = $db->fetch(
        "SELECT *
         FROM categorias_producto
         WHERE id = :id
         LIMIT 1",
        ['id' => $editCategoryId]
    ) ?? $categoryForm;
}

$areaForm = [
    'id' => 0,
    'nombre' => '',
    'descripcion' => '',
    'activo' => 1,
];

if ($editAreaId > 0) {
    $areaForm = $db->fetch(
        "SELECT *
         FROM areas_preparacion
         WHERE id = :id
         LIMIT 1",
        ['id' => $editAreaId]
    ) ?? $areaForm;
}

$tableForm = [
    'id' => 0,
    'id_sucursal' => '',
    'id_zona' => '',
    'numero' => '',
    'nombre' => '',
    'capacidad' => 4,
    'estado' => 'LIBRE',
    'activo' => 1,
];

if ($editTableId > 0) {
    $tableForm = $db->fetch(
        "SELECT *
         FROM mesas
         WHERE id = :id
         LIMIT 1",
        ['id' => $editTableId]
    ) ?? $tableForm;
}

render_page($title, 'masters', [
    'csrf' => $csrf,
    'section' => $section,
    'categories' => $categories,
    'areas' => $areas,
    'productTypes' => $productTypes,
    'productTypeUsage' => $productTypeUsage,
    'branches' => $branches,
    'zones' => $zones,
    'tables' => $tables,
    'categoryForm' => $categoryForm,
    'areaForm' => $areaForm,
    'tableForm' => $tableForm,
    'editCategoryId' => $editCategoryId,
    'editAreaId' => $editAreaId,
    'editTableId' => $editTableId,
]);
