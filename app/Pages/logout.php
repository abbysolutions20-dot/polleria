<?php

$auth->logout();
$flash->info('La sesión se cerró correctamente.');

redirect('login');
