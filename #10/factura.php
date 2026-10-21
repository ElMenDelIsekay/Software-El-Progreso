<?php
include("conexion.php");

$rol = isset($_GET['rol']) ? $_GET['rol'] : (isset($_POST['rol']) ? $_POST['rol'] : '');

// Si no viene rol, redirigir al login
if (empty($rol)) {
    header("Location: index.html");
    exit();
}

// Definir qué módulos puede ver cada rol
$permisos = [
    'Administrador' => ['producto', 'categoria', 'compra', 'factura', 'cliente', 'proveedor', 'empleado', 'cargo'],
    'Vendedor'      => ['producto', 'factura', 'cliente'],
    'Bodeguero'     => ['producto', 'categoria', 'compra', 'proveedor']
];

// Detectar el módulo actual (nombre del archivo sin .php)
$modulo_actual = basename($_SERVER['PHP_SELF'], ".php");

// Validar que el rol tenga permiso
if (!in_array($modulo_actual, $permisos[$rol] ?? [])) {
    echo "<script>
        alert('⛔ Acceso denegado. Su rol (" . $rol . ") no tiene permiso para acceder a este módulo.');
        window.location.href = 'menu.php?rol=" . urlencode($rol) . "';
    </script>";
    exit();
}

// Compatibilidad con la variable de conexión
if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$fact_editar = null;
$busqueda = "";

// Activar el modo de reporte de excepciones en MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// accion eliminar factura bd
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_fac'])) {

    $fact_eliminar = $_GET['id_fac'];

    try {
        $sql_del = "DELETE FROM factura WHERE id_fac = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $fact_eliminar);
        $stmt_del->execute();

        $mensaje = "<div class='alerta exito'>✅ Factura con id " . htmlspecialchars($fact_eliminar) . " eliminada correctamente.</div>";
        $stmt_del->close();

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<div class='alerta error'>❌ No se puede eliminar la factura con id " . htmlspecialchars($fact_eliminar) . " porque tiene detalles de factura registrados.</div>";
        } else {
            $mensaje = "<div class='alerta error'>❌ Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 2 accion modificar datos
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_fac'])) {
    $fact_buscar = $_GET['id_fac'];

    try {
        $sql_buscar = "SELECT * FROM factura WHERE id_fac = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $fact_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $fact_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'>❌ Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 3 formularios guardar y modificar post
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // crear o guardar nueva factura
    if (isset($_POST["guardar"])) {
        $id_fac        = $_POST["id_fac"];
        $fecha         = $_POST["fecha"];
        $impuesto      = $_POST["impuesto"];
        $estado        = $_POST["estado"];
        $metodo_pago   = $_POST["metodo_pago"];
        $observaciones = $_POST["observaciones"];
        $total         = $_POST["total"];
        $id_fk_emp     = $_POST["id_fk_emp"];
        $id_fk_cli     = $_POST["id_fk_cli"];

        try {
            $sql = "INSERT INTO factura (id_fac, fecha, impuesto, estado, metodo_pago, observaciones, total, id_fk_emp, id_fk_cli) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdsssdii", $id_fac, $fecha, $impuesto, $estado, $metodo_pago, $observaciones, $total, $id_fk_emp, $id_fk_cli);
            $stmt->execute();

            $mensaje = "<div class='alerta exito'>✅ Factura registrada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<div class='alerta error'>❌ Error: El id de factura " . htmlspecialchars($id_fac) . " ya está registrada.</div>";
            } else {
                $mensaje = "<div class='alerta error'>❌ Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }

    // modificar y/o actualizar factura
    if (isset($_POST["actualizar"])) {
        $id_fac        = $_POST["id_fac"];
        $fecha         = $_POST["fecha"];
        $impuesto      = $_POST["impuesto"];
        $estado        = $_POST["estado"];
        $metodo_pago   = $_POST["metodo_pago"];
        $observaciones = $_POST["observaciones"];
        $total         = $_POST["total"];
        $id_fk_emp     = $_POST["id_fk_emp"];
        $id_fk_cli     = $_POST["id_fk_cli"];

        try {
            $sql_up = "UPDATE factura SET fecha = ?, impuesto = ?, estado = ?, metodo_pago = ?, observaciones = ?, total = ?, id_fk_emp = ?, id_fk_cli = ? WHERE id_fac = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sdsssdiii", $fecha, $impuesto, $estado, $metodo_pago, $observaciones, $total, $id_fk_emp, $id_fk_cli, $id_fac);
            $stmt_up->execute();

            $mensaje = "<div class='alerta exito'>✅ Factura actualizada correctamente.</div>";
            $stmt_up->close();

        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'>❌ Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 4 bloque codigo buscar y listar compras (CON JOIN para traer nombres)
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT f.id_fac, f.fecha, f.impuesto, f.estado, f.metodo_pago, f.observaciones, f.total, f.id_fk_emp, f.id_fk_cli,
                          e.nombre AS nom_empleado,
                          c.nombre AS nom_cliente
                   FROM factura f
                   LEFT JOIN empleado e ON f.id_fk_emp = e.id_emp
                   LEFT JOIN cliente c ON f.id_fk_cli = c.id_cli
                   WHERE f.id_fac LIKE ? OR f.fecha LIKE ? OR f.estado LIKE ? OR f.total LIKE ?
                   ORDER BY f.id_fac ASC";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $facturas = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT f.id_fac, f.fecha, f.impuesto, f.estado, f.metodo_pago, f.observaciones, f.total, f.id_fk_emp, f.id_fk_cli,
                          e.nombre AS nom_empleado,
                          c.nombre AS nom_cliente
                   FROM factura f
                   LEFT JOIN empleado e ON f.id_fk_emp = e.id_emp
                   LEFT JOIN cliente c ON f.id_fk_cli = c.id_cli
                   ORDER BY f.id_fac ASC";
    $facturas = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
     <link rel="stylesheet" href="boton.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/logo.png" type="image/x-icon">
    <title>Gestión de Facturación — El Progreso</title>
    <style>
        /* =================
           RESET global
           ================ */
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

        /* ============================================
           BARRA DE NAVEGACIÓN SUPERIOR
           ============================================ */
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

        .zona-derecha {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* ============================================
           CONTENIDO PRINCIPAL
           ============================================ */
        .contenido-principal {
            flex: 1;
            padding: 30px 40px 50px 40px;
            overflow-y: auto;
            background: #f9fafb;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
        }

        .titulo-pagina {
            color: #0D2252;
            margin-bottom: 6px;
            font-size: 28px;
            font-weight: 700;
        }

        .subtitulo-pagina {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* ============================================
           ALERTAS
           ============================================ */
        .alerta {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border-left: 5px solid;
        }

        .alerta.exito {
            background: #ecfdf5;
            color: #065f46;
            border-left-color: #16a34a;
        }

        .alerta.error {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: #dc2626;
        }

        /* ============================================
           TARJETAS / SECCIONES
           ============================================ */
        .tarjeta-seccion {
            background: white;
            padding: 24px 26px;
            border-radius: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            border-top: 4px solid #FF6B1A;
        }

        .tarjeta-seccion h2 {
            color: #0D2252;
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* ============================================
           FORMULARIOS
           ============================================ */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .grupo-campo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .grupo-campo.ancho-completo {
            grid-column: 1 / -1;
        }

        .grupo-campo label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .grupo-campo input,
        .grupo-campo select,
        .grupo-campo textarea {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            outline: none;
            width: 100%;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .grupo-campo input:focus,
        .grupo-campo select:focus,
        .grupo-campo textarea:focus {
            border-color: #1D6ECC;
            box-shadow: 0 0 0 3px rgba(29,110,204,0.15);
        }

        .grupo-campo textarea {
            resize: vertical;
            min-height: 70px;
        }

        .grupo-campo input:disabled {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* ============================================
           BOTONES
           ============================================ */
        .boton-primario {
            background: #1D6ECC;
            color: white;
            border: none;
            padding: 11px 26px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Segoe UI', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .boton-primario:hover {
            background: #1558A8;
            transform: translateY(-1px);
        }

        .boton-primario:active {
            transform: translateY(0);
        }

        .boton-secundario {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 11px 26px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Segoe UI', sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .boton-secundario:hover {
            background: #e5e7eb;
        }

        .boton-salir {
            background: #FF6B1A;
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Segoe UI', sans-serif;
            cursor: pointer;
            transition: background 0.2s;
        }

        .boton-salir:hover {
            background: #e25c10;
        }

        .acciones-form {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* ============================================
           TABLA
           ============================================ */
        .tabla-contenedor {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        table.tabla-datos {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 1100px;
        }

        table.tabla-datos thead {
            background: #0D2252;
        }

        table.tabla-datos th {
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12.5px;
            white-space: nowrap;
        }

        table.tabla-datos td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            vertical-align: middle;
        }

        table.tabla-datos tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        table.tabla-datos tbody tr:hover {
            background: #eef2ff;
        }

        table.tabla-datos td.sin-registros {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
            font-style: italic;
        }

        /* Estado con colores */
        .estado {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-block;
            white-space: nowrap;
        }

        .estado.pendiente  { background: #fef3c7; color: #92400e; }
        .estado.pagada     { background: #d1fae5; color: #065f46; }
        .estado.cancelada  { background: #fee2e2; color: #991b1b; }

        /* Acciones de tabla */
        .acciones-tabla a {
            text-decoration: none;
            font-weight: 600;
            font-size: 12.5px;
            padding: 5px 10px;
            border-radius: 6px;
            margin-right: 4px;
            transition: background 0.2s;
            display: inline-block;
        }

        .acciones-tabla a.modificar {
            color: #1D6ECC;
            background: #e0e7ff;
        }

        .acciones-tabla a.modificar:hover {
            background: #c7d2fe;
        }

        .acciones-tabla a.eliminar {
            color: #dc2626;
            background: #fee2e2;
        }

        .acciones-tabla a.eliminar:hover {
            background: #fecaca;
        }

        /* ============================================
           BUSCADOR
           ============================================ */
        .form-busqueda {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-busqueda input {
            flex: 1;
            min-width: 220px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
            font-family: 'Segoe UI', sans-serif;
        }

        .form-busqueda input:focus {
            border-color: #1D6ECC;
            box-shadow: 0 0 0 3px rgba(29,110,204,0.15);
        }

        /* ============================================
           FOOTER
           ============================================ */
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

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 900px) {
            .contenido-principal {
                padding: 20px 18px 40px 18px;
            }

            .titulo-pagina {
                font-size: 22px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .tarjeta-seccion {
                padding: 18px 16px;
            }
        }
    </style>
</head>
<body>

<!-- ============ BARRA SUPERIOR ============ -->
<nav class="barra-navegacion">
  <a class="logo-empresa" href="menu.php">
    <img src="img/logo.png" alt="Logo" class="imagen-logo">
    <span>EL PROGRESO</span>
  </a>
  <div class="zona-derecha">
    <form action="menu.php?rol=<?php echo urlencode($rol); ?>" method="POST" style="display:inline;">
      <button type="submit" class="boton-salir">← Volver al Menú</button>
    </form>
    <div class="avatar" title="Juan Pérez">JP</div>
  </div>
</nav>
<div class="barra-naranja"></div>

<!-- ============ CONTENIDO PRINCIPAL ============ -->
<main class="contenido-principal">

  <h1 class="titulo-pagina">Gestión de Facturación</h1>
  <p class="subtitulo-pagina">Registre, modifique y consulte las facturas emitidas a clientes.</p>

  <?php echo $mensaje; ?>

  <!-- ============ FORMULARIO (EDITAR O INSERTAR) ============ -->
  <?php if ($fact_editar) : ?>
    <div class="tarjeta-seccion">
      <h2>✏️ Modificación de Registro de Factura</h2>
      <form action="" method="POST">
        <input type="hidden" name="id_fac" value="<?php echo htmlspecialchars($fact_editar['id_fac']); ?>">
        <input type="hidden" name="rol" value="<?php echo htmlspecialchars($rol); ?>">

        <div class="form-grid">
          <div class="grupo-campo">
            <label>ID Factura:</label>
            <input type="number" value="<?php echo htmlspecialchars($fact_editar['id_fac']); ?>" disabled>
          </div>

          <div class="grupo-campo">
            <label>Fecha:</label>
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($fact_editar['fecha']); ?>" required>
          </div>

          <div class="grupo-campo">
            <label>Impuesto:</label>
            <input type="number" name="impuesto" step="0.01" value="<?php echo htmlspecialchars($fact_editar['impuesto']); ?>" required>
          </div>

          <div class="grupo-campo">
            <label>Estado:</label>
            <select name="estado" required>
              <option value="Pendiente" <?php echo ($fact_editar['estado'] === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
              <option value="Pagada" <?php echo ($fact_editar['estado'] === 'Pagada') ? 'selected' : ''; ?>>Pagada</option>
              <option value="Cancelada" <?php echo ($fact_editar['estado'] === 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
            </select>
          </div>

          <div class="grupo-campo">
            <label>Método de Pago:</label>
            <select name="metodo_pago" required>
              <option value="Efectivo" <?php echo ($fact_editar['metodo_pago'] === 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
              <option value="Transferencia" <?php echo ($fact_editar['metodo_pago'] === 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
              <option value="Crédito" <?php echo ($fact_editar['metodo_pago'] === 'Crédito') ? 'selected' : ''; ?>>Crédito</option>
            </select>
          </div>

          <div class="grupo-campo">
            <label>Total:</label>
            <input type="number" name="total" step="0.01" value="<?php echo htmlspecialchars($fact_editar['total']); ?>" required>
          </div>

          <div class="grupo-campo">
            <label>Empleado:</label>
            <select name="id_fk_emp" required>
              <option value="">-- Seleccione un empleado --</option>
              <?php
              $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
              $res_emp2 = $conn->query($sql_emp2);
              while ($emp2 = $res_emp2->fetch_assoc()) {
                  $selected = ($fact_editar['id_fk_emp'] == $emp2['id_emp']) ? 'selected' : '';
                  echo '<option value="' . htmlspecialchars($emp2['id_emp']) . '" ' . $selected . '>'
                      . htmlspecialchars($emp2['id_emp'] . ' - ' . $emp2['nombre'])
                      . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="grupo-campo">
            <label>Cliente:</label>
            <select name="id_fk_cli" required>
              <option value="">-- Seleccione un cliente --</option>
              <?php
              $sql_cli2 = "SELECT id_cli, nombre FROM cliente ORDER BY id_cli ASC";
              $res_cli2 = $conn->query($sql_cli2);
              while ($cli2 = $res_cli2->fetch_assoc()) {
                  $selected = ($fact_editar['id_fk_cli'] == $cli2['id_cli']) ? 'selected' : '';
                  echo '<option value="' . htmlspecialchars($cli2['id_cli']) . '" ' . $selected . '>'
                      . htmlspecialchars($cli2['id_cli'] . ' - ' . $cli2['nombre'])
                      . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="grupo-campo ancho-completo">
            <label>Observaciones:</label>
            <textarea name="observaciones" rows="3"><?php echo htmlspecialchars($fact_editar['observaciones']); ?></textarea>
          </div>
        </div>

        <div class="acciones-form">
          <button type="submit" name="actualizar" class="boton-primario">💾 Actualizar Factura</button>
          <a href="?" class="boton-secundario">Cancelar</a>
        </div>
      </form>
    </div>

  <?php else: ?>
    <div class="tarjeta-seccion">
      <h2>➕ Registro de Nueva Factura</h2>
      <form action="" method="POST">
        <div class="form-grid">
          <div class="grupo-campo">
            <label>ID Factura:</label>
            <input type="number" name="id_fac" required>
          </div>

          <div class="grupo-campo">
            <label>Fecha:</label>
            <input type="date" name="fecha" required>
          </div>

          <div class="grupo-campo">
            <label>Impuesto:</label>
            <input type="number" name="impuesto" step="0.01" required>
          </div>

          <div class="grupo-campo">
            <label>Método de Pago:</label>
            <select name="metodo_pago" required>
              <option value="Efectivo">Efectivo</option>
              <option value="Transferencia">Transferencia</option>
              <option value="Crédito">Crédito</option>
            </select>
          </div>

          <div class="grupo-campo">
            <label>Total:</label>
            <input type="number" name="total" step="0.01" required>
          </div>

          <div class="grupo-campo">
            <label>Empleado:</label>
            <select name="id_fk_emp" required>
              <option value="">-- Seleccione un empleado --</option>
              <?php
              $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
              $res_emp2 = $conn->query($sql_emp2);
              while ($emp2 = $res_emp2->fetch_assoc()) {
                  echo '<option value="' . htmlspecialchars($emp2['id_emp']) . '">'
                      . htmlspecialchars($emp2['id_emp'] . ' - ' . $emp2['nombre'])
                      . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="grupo-campo">
            <label>Cliente:</label>
            <select name="id_fk_cli" required>
              <option value="">-- Seleccione un cliente --</option>
              <?php
              $sql_cli2 = "SELECT id_cli, nombre FROM cliente ORDER BY id_cli ASC";
              $res_cli2 = $conn->query($sql_cli2);
              while ($cli2 = $res_cli2->fetch_assoc()) {
                  echo '<option value="' . htmlspecialchars($cli2['id_cli']) . '">'
                      . htmlspecialchars($cli2['id_cli'] . ' - ' . $cli2['nombre'])
                      . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="grupo-campo ancho-completo">
            <label>Observaciones:</label>
            <textarea name="observaciones" rows="3"></textarea>
          </div>
        </div>

        <!-- Campo oculto del estado inicial -->
        <input type="text" name="estado" value="Pendiente" hidden>
        <input type="hidden" name="rol" value="<?php echo htmlspecialchars($rol); ?>">

        <div class="acciones-form">
          <button type="submit" name="guardar" class="boton-primario">📤 Guardar Factura</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- ============ BUSCADOR ============ -->
  <div class="tarjeta-seccion">
    <h2>🔍 Consulta de Facturas</h2>
    <form action="" method="GET" class="form-busqueda">
      <input type="text" name="buscar" placeholder="Ingrese ID, estado o fecha..." value="<?php echo htmlspecialchars($busqueda); ?>">
      <button type="submit" class="boton-primario">Buscar</button>
      <?php if (!empty($busqueda)): ?>
        <a href="?" class="boton-secundario">Mostrar todos</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ============ LISTADO ============ -->
  <div class="tarjeta-seccion">
    <h2>📋 Historial de Facturas Registradas</h2>
    <div class="tabla-contenedor">
      <table class="tabla-datos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Impuesto</th>
            <th>Estado</th>
            <th>Método de Pago</th>
            <th>Observaciones</th>
            <th>Total</th>
            <th>Empleado</th>
            <th>Cliente</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($facturas && $facturas->num_rows > 0): ?>
            <?php while ($fila = $facturas->fetch_assoc()): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($fila['id_fac']); ?></strong></td>
                <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                <td>$<?php echo number_format($fila['impuesto'], 0, ',', '.'); ?></td>
                <td>
                  <?php
                    $clase = 'pendiente';
                    if ($fila['estado'] === 'Pagada') $clase = 'pagada';
                    else if ($fila['estado'] === 'Cancelada') $clase = 'cancelada';
                  ?>
                  <span class="estado <?php echo $clase; ?>"><?php echo htmlspecialchars($fila['estado']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($fila['metodo_pago']); ?></td>
                <td><?php echo htmlspecialchars($fila['observaciones']); ?></td>
                <td>$<?php echo number_format($fila['total'], 0, ',', '.'); ?></td>
                <td><?php echo $fila['id_fk_emp'] . ' - ' . htmlspecialchars($fila['nom_empleado'] ?? 'Sin asignar'); ?></td>
                <td><?php echo $fila['id_fk_cli'] . ' - ' . htmlspecialchars($fila['nom_cliente'] ?? 'Sin asignar'); ?></td>
                <td class="acciones-tabla">
                  <a href="?accion=editar&id_fac=<?php echo $fila['id_fac']; ?>&rol=<?php echo urlencode($rol); ?>" class="modificar">✏️ Editar</a>
                  <a href="?accion=eliminar&id_fac=<?php echo $fila['id_fac']; ?>&rol=<?php echo urlencode($rol); ?>" class="eliminar" onclick="return confirm('¿Seguro que desea eliminar esta factura?');">🗑️ Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="sin-registros">No se encontraron registros de facturas.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<div class="barra-naranja"></div>
<footer>
  <pre>El Progreso © 2026. <br> Sistema de Gestión de Inventario diseñado para optimizar el control de productos, mejorar la organización y facilitar la toma de decisiones.</pre>
</footer>
<!-- Botón flotante para ir arriba -->
<button id="btn-arriba" class="btn-arriba" title="Ir arriba" aria-label="Ir arriba">▲</button>
<script src="js/boton-arriba.js"></script>
</body>
</html>

<?php
$conn->close();
?>