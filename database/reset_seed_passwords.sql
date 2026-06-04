USE polleria_pos_pro;

UPDATE usuarios
SET password_hash = '$2y$10$8ZI0GToPzVwU6/1dsWyuYet7waBBuAbvYnQ7YoXviwNTPGc8eltpi'
WHERE usuario IN ('propietario', 'admin', 'cajera', 'mozo1', 'cocina1', 'delivery1');
