<?php
/* ========== MENÚ PRINCIPAL ========== */
include("config/conexion.php");

$rol = isset($_GET['rol']) ? $_GET['rol'] : 'Vendedor';
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $destino = "";
    $modulo  = "";

    if (isset($_POST['btn_com']))       { $destino = "modules/compra.php";    $modulo = "Módulo de Pedidos"; }
    else if (isset($_POST['btn_fac']))  { $destino = "modules/factura.php";   $modulo = "Módulo de Facturación"; }
    else if (isset($_POST['btn_cli']))  { $destino = "modules/cliente.php";   $modulo = "Gestión de Clientes"; }
    else if (isset($_POST['btn_prov'])) { $destino = "modules/proveedor.php"; $modulo = "Gestión de Proveedores"; }
    else if (isset($_POST['btn_emp']))  { $destino = "modules/empleado.php";  $modulo = "Gestión de Empleados"; }
    else if (isset($_POST['btn_prod'])) { $destino = "modules/producto.php";  $modulo = "Catálogo de Productos"; }
    else if (isset($_POST['btn_car']))  { $destino = "modules/cargo.php";     $modulo = "Gestión de Cargos"; }
    else if (isset($_POST['btn_cat']))  { $destino = "modules/categoria.php"; $modulo = "Categorías de Productos"; }
    else if (isset($_POST['btn_salir'])) { header("Location: index.html"); exit(); }

    $permitido = false;
    if ($rol == 'Administrador') $permitido = true;
    else if ($rol == 'Vendedor' && in_array($modulo, ['Módulo de Facturación', 'Gestión de Clientes', 'Catálogo de Productos'])) $permitido = true;
    else if ($rol == 'Bodeguero' && in_array($modulo, ['Módulo de Pedidos', 'Catálogo de Productos', 'Categorías de Productos', 'Gestión de Proveedores'])) $permitido = true;

    if ($permitido && $destino != "") {
        header("Location: " . $destino . "?rol=" . urlencode($rol));
        exit();
    } else if ($destino != "") {
        $mensaje = "<div class='alerta error'>❌ Acceso denegado. Su rol (<strong>" . htmlspecialchars($rol) . "</strong>) no tiene permiso para acceder al <strong>" . htmlspecialchars($modulo) . "</strong>.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="assets/css/boton.css">
    <link rel="icon" href="assets/img/logo.png?v=2">
    <!-- Font Awesome (iconos profesionales y gratuitos) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>El Progreso — Menú Principal</title>
</head>
<body>

<nav class="barra-navegacion">
  <a class="logo-empresa" href="menu.php?rol=<?= urlencode($rol) ?>">
    <img src="assets/img/logo.png" alt="Logo" class="imagen-logo">
    <span>EL PROGRESO</span>
  </a>
  <div class="buscador">
    <input type="text" placeholder="Buscar módulo...">
  </div>
  <div class="zona-derecha">
    <div class="avatar" title="<?= htmlspecialchars($rol) ?>">
      <?= strtoupper(substr($rol, 0, 2)) ?>
    </div>
  </div>
</nav>
<div class="barra-naranja"></div>

<main class="contenido-principal">
  <div class="encabezado">
    <h1 class="titulo-pagina">Menú Principal de Módulos</h1>
    <p class="subtitulo-pagina">Bienvenido, <strong><?= htmlspecialchars($rol) ?></strong>. Seleccione el módulo al que desea acceder.</p>
  </div>

  <?= $mensaje ?>

  <form action="" method="POST" class="form-menu">
    <div class="fila-tarjetas">

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <h3>Módulo de Facturación</h3>
        <p>Gestione la facturación y los documentos de venta.</p>
        <button type="submit" name="btn_fac" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-users"></i></div>
        <h3>Gestión de Clientes</h3>
        <p>Consulte, registre y actualice la información de clientes.</p>
        <button type="submit" name="btn_cli" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-cart-shopping"></i></div>
        <h3>Módulo de Pedidos</h3>
        <p>Registre y administre los pedidos de compra a proveedores.</p>
        <button type="submit" name="btn_com" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-handshake"></i></div>
        <h3>Gestión de Proveedores</h3>
        <p>Administre los proveedores y sus datos de contacto.</p>
        <button type="submit" name="btn_prov" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-box-open"></i></div>
        <h3>Catálogo de Productos</h3>
        <p>Consulte y gestione el catálogo de productos disponibles.</p>
        <button type="submit" name="btn_prod" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-tags"></i></div>
        <h3>Categorías de Productos</h3>
        <p>Organice los productos por categorías.</p>
        <button type="submit" name="btn_cat" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-user-tie"></i></div>
        <h3>Gestión de Empleados</h3>
        <p>Registre y administre el personal de la empresa.</p>
        <button type="submit" name="btn_emp" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <div class="tarjeta tarjeta-modulo">
        <div class="icono-modulo"><i class="fa-solid fa-briefcase"></i></div>
        <h3>Gestión de Cargos</h3>
        <p>Defina y administre los cargos de la organización.</p>
        <button type="submit" name="btn_car" class="boton-primario-pequeno">Ingresar</button>
      </div>

    </div>
  </form>

  <div class="zona-salir">
    <form action="" method="POST" style="display:inline;">
      <button type="submit" name="btn_salir" class="boton-salir-grande" onclick="return confirm('¿Está seguro de que desea cerrar sesión?');">
        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
      </button>
    </form>
  </div>
</main>

<div class="barra-naranja"></div>
<footer>
  <pre>El Progreso © 2026. <br> Sistema de Gestión de Inventario diseñado para optimizar el control de productos, mejorar la organización y facilitar la toma de decisiones.</pre>
</footer>

<button id="btn-arriba" class="btn-arriba" title="Ir arriba" aria-label="Ir arriba">▲</button>
<script src="assets/js/boton-arriba.js"></script>
</body>
</html>