<?php

$section = (string) ($_GET['section'] ?? 'platos');
$section = in_array($section, ['platos', 'articulos'], true) ? $section : 'platos';
$search = trim((string) ($_GET['search'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$createMode = (string) ($_GET['create'] ?? '') === '1';

if ($editId > 0) {
    $section = 'articulos';
}

$currentUser = $auth->user();
$canManageSensitiveProducts = in_array(
    (string) ($currentUser['rol_nombre'] ?? ''),
    ['PROPIETARIO', 'ADMINISTRADOR'],
    true
);
$productTypes = $masterDataService->productTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf->guard($_POST['_token'] ?? null);

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_product') {
            $productId = (int) ($_POST['id'] ?? 0);
            $sku = trim((string) ($_POST['sku'] ?? ''));
            $categoryId = (int) ($_POST['id_categoria'] ?? 0);
            $areaId = (int) ($_POST['id_area_preparacion'] ?? 0);
            $name = trim((string) ($_POST['nombre'] ?? ''));
            $description = trim((string) ($_POST['descripcion'] ?? ''));
            $productType = (string) ($_POST['tipo_producto'] ?? ($productTypes[0] ?? 'PLATO'));
            $salePrice = (float) ($_POST['precio_venta'] ?? 0);
            $unitCost = (float) ($_POST['costo_unitario'] ?? 0);
            $affectoIgv = isset($_POST['afecto_igv']) ? 1 : 0;

            if ($categoryId <= 0 || $name === '' || $salePrice <= 0) {
                throw new RuntimeException('Completa categoria, nombre y precio de venta para guardar el plato.');
            }

            if (!in_array($productType, $productTypes, true)) {
                throw new RuntimeException('Selecciona un tipo de producto valido.');
            }

            $margin = $salePrice > 0
                ? round((($salePrice - $unitCost) / $salePrice) * 100, 2)
                : 0.0;

            $payload = [
                'sku' => $sku !== '' ? $sku : null,
                'id_categoria' => $categoryId,
                'id_area_preparacion' => $areaId > 0 ? $areaId : null,
                'nombre' => $name,
                'descripcion' => $description !== '' ? $description : null,
                'tipo_producto' => $productType,
                'precio_venta' => $salePrice,
                'costo_unitario' => $unitCost,
                'margen_porcentaje' => $margin,
                'afecto_igv' => $affectoIgv,
            ];

            if ($productId > 0) {
                $currentProduct = $db->fetch(
                    "SELECT precio_venta, activo
                     FROM productos
                     WHERE id = :id
                     LIMIT 1",
                    ['id' => $productId]
                );

                if (!$currentProduct) {
                    throw new RuntimeException('No encontramos el plato que intentas editar.');
                }

                if (
                    !$canManageSensitiveProducts
                    && abs((float) $currentProduct['precio_venta'] - $salePrice) > 0.00001
                ) {
                    throw new RuntimeException('Solo el propietario o el administrador pueden cambiar precios.');
                }

                $payload['id'] = $productId;
                $payload['activo'] = (int) $currentProduct['activo'];

                $db->execute(
                    "UPDATE productos
                     SET sku = :sku,
                         id_categoria = :id_categoria,
                         id_area_preparacion = :id_area_preparacion,
                         nombre = :nombre,
                         descripcion = :descripcion,
                         tipo_producto = :tipo_producto,
                         precio_venta = :precio_venta,
                         costo_unitario = :costo_unitario,
                         margen_porcentaje = :margen_porcentaje,
                         afecto_igv = :afecto_igv,
                         activo = :activo,
                         updated_at = NOW()
                     WHERE id = :id",
                    $payload
                );

                $flash->success('Plato actualizado correctamente.');
            } else {
                $payload['activo'] = 1;

                $db->execute(
                    "INSERT INTO productos (
                        sku, id_categoria, id_area_preparacion, nombre, descripcion,
                        tipo_producto, precio_venta, costo_unitario, margen_porcentaje,
                        afecto_igv, activo
                    ) VALUES (
                        :sku, :id_categoria, :id_area_preparacion, :nombre, :descripcion,
                        :tipo_producto, :precio_venta, :costo_unitario, :margen_porcentaje,
                        :afecto_igv, :activo
                    )",
                    $payload
                );

                $flash->success('Plato creado correctamente.');
            }

            redirect('products', ['section' => 'articulos']);
        } elseif ($action === 'toggle_visibility') {
            if (!$canManageSensitiveProducts) {
                throw new RuntimeException('Solo el propietario o el administrador pueden cambiar la visibilidad.');
            }

            $productId = (int) ($_POST['id'] ?? 0);
            $visible = (int) ($_POST['visible'] ?? 0);

            if ($productId <= 0) {
                throw new RuntimeException('Selecciona un plato valido.');
            }

            $db->execute(
                "UPDATE productos
                 SET activo = :activo,
                     updated_at = NOW()
                 WHERE id = :id",
                [
                    'activo' => $visible,
                    'id' => $productId,
                ]
            );

            $flash->success($visible === 1 ? 'Plato visible en la carta.' : 'Plato ocultado de la carta.');
            redirect('products', ['section' => 'platos']);
        } elseif ($action === 'delete_product') {
            if (!$canManageSensitiveProducts) {
                throw new RuntimeException('Solo el propietario o el administrador pueden eliminar platos.');
            }

            $productId = (int) ($_POST['id'] ?? 0);

            if ($productId <= 0) {
                throw new RuntimeException('Selecciona un plato valido para eliminar.');
            }

            $db->execute(
                "UPDATE productos
                 SET activo = 0,
                     updated_at = NOW()
                 WHERE id = :id",
                ['id' => $productId]
            );

            $flash->success('Plato desactivado correctamente.');
            redirect('products', ['section' => 'articulos']);
        } else {
            throw new RuntimeException('Accion no reconocida.');
        }
    } catch (Throwable $exception) {
        $flash->error($exception->getMessage());

        $redirectParams = ['section' => $section];

        if ($action === 'save_product') {
            $redirectParams['section'] = 'articulos';

            if (!empty($_POST['id']) && (int) $_POST['id'] > 0) {
                $redirectParams['edit'] = (int) $_POST['id'];
            } else {
                $redirectParams['create'] = 1;
            }
        }

        redirect('products', $redirectParams);
    }
}

$categories = $db->fetchAll(
    "SELECT id, nombre
     FROM categorias_producto
     WHERE activo = 1
     ORDER BY nombre"
);

$areas = $db->fetchAll(
    "SELECT id, nombre
     FROM areas_preparacion
     WHERE activo = 1
     ORDER BY nombre"
);

$conditions = ['1 = 1'];
$params = [];

if ($section === 'articulos') {
    $conditions[] = 'p.activo = 1';
}

if ($search !== '') {
    $conditions[] = '(p.nombre LIKE :search_name OR p.sku LIKE :search_sku)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_sku'] = '%' . $search . '%';
}

if ($category !== '') {
    $conditions[] = 'p.id_categoria = :category';
    $params['category'] = $category;
}

if ($type !== '') {
    $conditions[] = 'p.tipo_producto = :type';
    $params['type'] = $type;
}

$products = $db->fetchAll(
    "SELECT p.*, cp.nombre AS categoria, ap.nombre AS area
     FROM productos p
     INNER JOIN categorias_producto cp ON cp.id = p.id_categoria
     LEFT JOIN areas_preparacion ap ON ap.id = p.id_area_preparacion
     WHERE " . implode(' AND ', $conditions) . "
     ORDER BY p.activo DESC, cp.nombre, p.nombre",
    $params
);

$productForm = [
    'id' => 0,
    'sku' => '',
    'id_categoria' => '',
    'id_area_preparacion' => '',
    'nombre' => '',
    'descripcion' => '',
    'tipo_producto' => 'PLATO',
    'precio_venta' => '0.00',
    'costo_unitario' => '0.00',
    'afecto_igv' => 1,
];

if ($editId > 0) {
    $productForm = $db->fetch(
        "SELECT *
         FROM productos
         WHERE id = :id
         LIMIT 1",
        ['id' => $editId]
    ) ?? $productForm;
}

$showProductForm = $section === 'articulos' && ($createMode || $editId > 0);

render_page($title, 'products', [
    'products' => $products,
    'categories' => $categories,
    'areas' => $areas,
    'productTypes' => $productTypes,
    'search' => $search,
    'category' => $category,
    'type' => $type,
    'section' => $section,
    'showProductForm' => $showProductForm,
    'createMode' => $createMode,
    'csrf' => $csrf,
    'productForm' => $productForm,
    'editId' => $editId,
    'canManageSensitiveProducts' => $canManageSensitiveProducts,
]);
