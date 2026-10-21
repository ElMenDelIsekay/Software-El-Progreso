<?php
include("conexion.php");

// Recibir el rol desde la URL
$rol = isset($_GET['rol']) ? $_GET['rol'] : 'Vendedor';

$mensaje = "";

// Procesar la redirección con validación de permisos
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $destino = "";
    $modulo  = "";

    // Detectar qué botón se presionó
    if (isset($_POST['btn_com'])) {
        $destino = "compra.php";
        $modulo  = "Módulo de Pedidos";
    } else if (isset($_POST['btn_fac'])) {
        $destino = "factura.php";
        $modulo  = "Módulo de Facturación";
    } else if (isset($_POST['btn_cli'])) {
        $destino = "cliente.php";
        $modulo  = "Gestión de Clientes";
    } else if (isset($_POST['btn_prov'])) {
        $destino = "proveedor.php";
        $modulo  = "Gestión de Proveedores";
    } else if (isset($_POST['btn_emp'])) {
        $destino = "empleado.php";
        $modulo  = "Gestión de Empleados";
    } else if (isset($_POST['btn_prod'])) {
        $destino = "producto.php";
        $modulo  = "Catálogo de Productos";
    } else if (isset($_POST['btn_car'])) {
        $destino = "cargo.php";
        $modulo  = "Gestión de Cargos";
    } else if (isset($_POST['btn_cat'])) {
        $destino = "categoria.php";
        $modulo  = "Categorías de Productos";
    } else if (isset($_POST['btn_salir'])) {
        header("Location: index.html");
        exit();
    }

    // Validar permisos según el rol
    $permitido = false;

    if ($rol == 'Administrador') {
        $permitido = true;
    } else if ($rol == 'Vendedor') {
        if (in_array($modulo, ['Módulo de Facturación', 'Gestión de Clientes', 'Catálogo de Productos'])) {
            $permitido = true;
        }
    } else if ($rol == 'Bodeguero') {
        if (in_array($modulo, ['Módulo de Pedidos', 'Catálogo de Productos', 'Categorías de Productos', 'Gestión de Proveedores'])) {
            $permitido = true;
        }
    }

    // Redirigir si tiene permiso, o mostrar mensaje de error si no
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
    <link rel="stylesheet" href="boton.css">
    <link rel="shortcut icon" href="img/logo.png" type="image/x-icon">
    <title>El Progreso — Menú Principal</title>
    <style>
        /* Reset global */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Barra de navegación superior */
        .barra-navegacion {
            background: #0D2252;
            height: 56px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 20px;
            flex-shrink: 0;
        }

        .logo-empresa {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .imagen-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .logo-empresa span {
            color: white;
            font-weight: bold;
            font-size: 17px;
            letter-spacing: 1.5px;
        }

        .buscador {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .buscador input {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            width: 100%;
            max-width: 380px;
            outline: none;
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 14px;
        }

        .buscador input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .zona-derecha {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: #FF6B1A;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .barra-naranja {
            background: #FF6B1A;
            height: 5px;
            flex-shrink: 0;
        }

        /* Contenido principal */
        .contenido-principal {
            flex: 1;
            padding: 40px 32px 50px 32px;
            overflow-y: auto;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .encabezado {
            text-align: center;
            margin-bottom: 40px;
            max-width: 700px;
        }

        .titulo-pagina {
            color: #0D2252;
            margin-bottom: 12px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .subtitulo-pagina {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Alertas */
        .alerta {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border-left: 5px solid;
            max-width: 800px;
            width: 100%;
            text-align: center;
        }

        .alerta.error {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: #dc2626;
        }

        /* Fila de tarjetas */
        .fila-tarjetas {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 20px;
            max-width: 1300px;
            width: 100%;
            justify-content: center;
        }

        .tarjeta {
            background: white;
            padding: 24px 22px;
            border-radius: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }

        /* Tarjetas de módulos */
        .tarjeta-modulo {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            justify-content: space-between;
            min-height: 230px;
            border-top: 4px solid #FF6B1A;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .tarjeta-modulo:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 22px rgba(0,0,0,0.13);
        }

        .tarjeta-modulo h3 {
            border-bottom: none;
            font-size: 19px;
            margin-bottom: 14px;
            color: #0D2252;
            line-height: 1.3;
        }

        .tarjeta-modulo p {
            font-size: 13.5px;
            color: #6b7280;
            line-height: 1.6;
            flex-grow: 1;
        }

        /* Botones */
        .boton-primario-pequeno {
            background: #1D6ECC;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            transition: background 0.2s, transform 0.15s;
        }

        .boton-primario-pequeno:hover {
            background: #1558A8;
            transform: scale(1.05);
        }

        .boton-primario-pequeno:active {
            transform: scale(0.98);
        }

        .zona-salir {
            margin-top: 40px;
            text-align: center;
        }

        .boton-salir-grande {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #FF6B1A;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Segoe UI', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(255,107,26,0.35);
        }

        .boton-salir-grande:hover {
            background: #e25c10;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(255,107,26,0.45);
        }

        .boton-salir-grande:active {
            transform: translateY(0);
        }

        /* Footer */
        footer {
            text-align: center;
            background: #0D2252;
            font-size: 11px;
            color: #9ca3af;
            font-family: 'Segoe UI', sans-serif;
            padding: 12px 20px;
            flex-shrink: 0;
            line-height: 1.6;
        }

        footer pre {
            font-family: 'Segoe UI', sans-serif;
            white-space: pre-wrap;
        }

        /* Scrollbar */
        .contenido-principal::-webkit-scrollbar {
            width: 8px;
        }

        .contenido-principal::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 4px;
        }

        .contenido-principal::-webkit-scrollbar-thumb {
            background: #0D2252;
            border-radius: 4px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .fila-tarjetas {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .fila-tarjetas {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .contenido-principal {
                padding: 24px 18px 36px 18px;
            }

            .titulo-pagina {
                font-size: 26px;
            }

            .buscador {
                display: none;
            }

            .tarjeta-modulo {
                min-height: auto;
            }

            .tarjeta-modulo h3 {
                font-size: 17px;
            }
        }

        @media (max-width: 600px) {
            .fila-tarjetas {
                grid-template-columns: 1fr;
            }

            .titulo-pagina {
                font-size: 22px;
            }

            .boton-salir-grande {
                width: 100%;
                justify-content: center;
                padding: 14px 20px;
            }
        }
    </style>
</head>
<body>

<!-- Barra superior -->
<nav class="barra-navegacion">
  <a class="logo-empresa" href="menu.php?rol=<?php echo urlencode($rol); ?>">
    <img src="img/logo.png" alt="Logo" class="imagen-logo">
    <span>EL PROGRESO</span>
  </a>
  <div class="buscador">
    <input type="text" placeholder="Buscar módulo...">
  </div>
  <div class="zona-derecha">
    <div class="avatar" title="<?php echo htmlspecialchars($rol); ?>">
      <?php echo strtoupper(substr($rol, 0, 2)); ?>
    </div>
  </div>
</nav>
<div class="barra-naranja"></div>

<!-- Contenido principal -->
<main class="contenido-principal">
  <div class="encabezado">
    <h1 class="titulo-pagina">Menú Principal de Módulos</h1>
    <p class="subtitulo-pagina">Bienvenido, <strong><?php echo htmlspecialchars($rol); ?></strong>. Seleccione el módulo al que desea acceder.</p>
  </div>

  <!-- Mensaje de error de permisos -->
  <?php echo $mensaje; ?>

  <form action="" method="POST" style="width:100%; display:flex; justify-content:center;">
    <div class="fila-tarjetas">

      <!-- Módulo principal: Facturación -->
      <div class="tarjeta tarjeta-modulo">
        <h3>🧾 Módulo de Facturación</h3>
        <p>Gestione la facturación y los documentos de venta.</p>
        <button type="submit" name="btn_fac" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Asociado a Facturación: Clientes -->
      <div class="tarjeta tarjeta-modulo">
        <h3>👥 Gestión de Clientes</h3>
        <p>Consulte, registre y actualice la información de clientes.</p>
        <button type="submit" name="btn_cli" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Módulo principal: Pedidos -->
      <div class="tarjeta tarjeta-modulo">
        <h3>🛒 Módulo de Pedidos</h3>
        <p>Registre y administre los pedidos de compra a proveedores.</p>
        <button type="submit" name="btn_com" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Asociado a Pedidos: Proveedores -->
      <div class="tarjeta tarjeta-modulo">
        <h3>🤝 Gestión de Proveedores</h3>
        <p>Administre los proveedores y sus datos de contacto.</p>
        <button type="submit" name="btn_prov" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Módulo base: Productos -->
      <div class="tarjeta tarjeta-modulo">
        <h3>📦 Catálogo de Productos</h3>
        <p>Consulte y gestione el catálogo de productos disponibles.</p>
        <button type="submit" name="btn_prod" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Asociado a Productos: Categorías -->
      <div class="tarjeta tarjeta-modulo">
        <h3>🏷️ Categorías de Productos</h3>
        <p>Organice los productos por categorías.</p>
        <button type="submit" name="btn_cat" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Módulo base: Empleados -->
      <div class="tarjeta tarjeta-modulo">
        <h3>👔 Gestión de Empleados</h3>
        <p>Registre y administre el personal de la empresa.</p>
        <button type="submit" name="btn_emp" class="boton-primario-pequeno">Ingresar</button>
      </div>

      <!-- Asociado a Empleados: Cargos -->
      <div class="tarjeta tarjeta-modulo">
        <h3>💼 Gestión de Cargos</h3>
        <p>Defina y administre los cargos de la organización.</p>
        <button type="submit" name="btn_car" class="boton-primario-pequeno">Ingresar</button>
      </div>

    </div>
  </form>

  <!-- Botón cerrar sesión -->
  <div class="zona-salir">
    <form action="" method="POST" style="display:inline;">
      <button type="submit" name="btn_salir" class="boton-salir-grande" onclick="return confirm('¿Está seguro de que desea cerrar sesión?');">
        🚪 Cerrar Sesión
      </button>
    </form>
  </div>
</main>

<div class="barra-naranja"></div>
<footer>
  <pre>El Progreso © 2026. <br> Sistema de Gestión de Inventario diseñado para optimizar el control de productos, mejorar la organización y facilitar la toma de decisiones.</pre>
</footer>

<!-- Botón flotante ir arriba -->
<button id="btn-arriba" class="btn-arriba" title="Ir arriba" aria-label="Ir arriba">▲</button>
<script src="js/boton-arriba.js"></script>
</body>
</html>