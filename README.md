# Polleria POS Pro para XAMPP

Aplicación PHP pura, lista para copiar a `C:\xampp\htdocs`, conectada al esquema `polleria_pos_pro` y pensada para trabajar con el SQL original incluido en [database/polleria_pos_pro_mysql.sql](/C:/Users/RDPARIONAG/Documents/New%20project/database/polleria_pos_pro_mysql.sql).

## Qué incluye

- Login con sesiones PHP y actualización automática de contraseñas semilla.
- Dashboard con ventas del día, órdenes activas, stock crítico y caja.
- POS con creación de órdenes, agregado de ítems, descuentos, estados, comandas, pagos y comprobantes.
- Gestión de caja con apertura, cierre, movimientos y gastos.
- Catálogo de productos, clientes, inventario y comprobantes.
- Uso de los procedimientos almacenados del SQL para la lógica central.

## Instalación en XAMPP

1. Copia esta carpeta a `C:\xampp\htdocs\polleria-pos`.
2. Inicia `Apache` y `MySQL` desde el panel de XAMPP.
3. Importa [database/polleria_pos_pro_mysql.sql](/C:/Users/RDPARIONAG/Documents/New%20project/database/polleria_pos_pro_mysql.sql) en phpMyAdmin o con:

```powershell
C:\xampp\mysql\bin\mysql.exe -uroot < C:\xampp\htdocs\polleria-pos\database\polleria_pos_pro_mysql.sql
```

4. Si prefieres dejar las contraseñas semilla listas desde SQL, ejecuta también:

```powershell
C:\xampp\mysql\bin\mysql.exe -uroot polleria_pos_pro < C:\xampp\htdocs\polleria-pos\database\reset_seed_passwords.sql
```

5. Revisa [config/config.php](/C:/Users/RDPARIONAG/Documents/New%20project/config/config.php) si tu MySQL usa otra contraseña o puerto.
6. Abre:

```text
http://localhost/polleria-pos/
```

## Acceso inicial

- Usuarios: `propietario`, `admin`, `cajera`, `mozo1`, `cocina1`, `delivery1`
- Contraseña inicial: `123456`

Nota: aunque no ejecutes el SQL de reseteo, la app detecta el hash inválido de la semilla y actualiza el password la primera vez que ingresas con `123456`.

## Flujo sugerido

1. Inicia sesión con `cajera` o `admin`.
2. Ve a `Caja` y abre un turno.
3. En `POS`, crea una orden.
4. En el detalle de la orden, agrega productos.
5. Registra el pago.
6. Emite el comprobante.

## Estructura

- [index.php](/C:/Users/RDPARIONAG/Documents/New%20project/index.php): front controller.
- [app/Core](/C:/Users/RDPARIONAG/Documents/New%20project/app/Core): conexión, auth, helpers, CSRF y vistas.
- [app/Pages](/C:/Users/RDPARIONAG/Documents/New%20project/app/Pages): lógica por pantalla.
- [app/Views](/C:/Users/RDPARIONAG/Documents/New%20project/app/Views): vistas HTML.
- [assets](/C:/Users/RDPARIONAG/Documents/New%20project/assets): estilos y JavaScript.
- [database](/C:/Users/RDPARIONAG/Documents/New%20project/database): SQL original y parche de contraseñas.
