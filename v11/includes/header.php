<?php
/* ========== HEADER GLOBAL ========== */
$titulo_pagina = $titulo_pagina ?? 'El Progreso';
$rol = $rol ?? ($_GET['rol'] ?? '');
$ruta_base = $ruta_base ?? ''; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= $ruta_base ?>assets/css/estilos.css">
    <link rel="stylesheet" href="<?= $ruta_base ?>assets/css/boton.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= $ruta_base ?>assets/img/logo.png?v=2">
    <title><?= htmlspecialchars($titulo_pagina) ?> — El Progreso</title>
</head>
<body>

<nav class="barra-navegacion">
  <a class="logo-empresa" href="<?= $ruta_base ?>menu.php?rol=<?= urlencode($rol) ?>">
    <img src="<?= $ruta_base ?>assets/img/logo.png" alt="Logo" class="imagen-logo">
    <span>EL PROGRESO</span>
  </a>
  <div class="zona-derecha">
    <form action="<?= $ruta_base ?>menu.php?rol=<?= urlencode($rol) ?>" method="POST" style="display:inline;">
      <button type="submit" class="boton-salir"><i class="fa-solid fa-arrow-left"></i> Volver al Menú</button>
    </form>
    <div class="avatar" title="<?= htmlspecialchars($rol) ?>">
      <?= strtoupper(substr($rol, 0, 2)) ?>
    </div>
  </div>
</nav>
<div class="barra-naranja"></div>

<main class="contenido-principal">