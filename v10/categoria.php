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

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$categoria_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Categoría
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_cat'])) {
    $id_eliminar = $_GET['id_cat'];

    try {
        $sql_del = "DELETE FROM categoria WHERE id_cat = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<div class='alerta exito'>✅ Categoría con ID " . htmlspecialchars($id_eliminar) . " eliminada correctamente.</div>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'>❌ Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_cat'])) {
    $id_buscar = $_GET['id_cat'];

    try {
        $sql_buscar = "SELECT * FROM categoria WHERE id_cat = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $categoria_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'>❌ Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Guardar nueva categoría
    if (isset($_POST["guardar"])) {
        $id_cat      = $_POST["id_cat"];
        $nombre      = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $estado      = $_POST["estado"];

        try {
            $sql = "INSERT INTO categoria (id_cat, nombre, descripcion, estado) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $id_cat, $nombre, $descripcion, $estado);
            $stmt->execute();

            $mensaje = "<div class='alerta exito'>✅ Categoría registrada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<div class='alerta error'>❌ Error: El ID " . htmlspecialchars($id_cat) . " ya está registrado.</div>";
            } else {
                $mensaje = "<div class='alerta error'>❌ Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }

    // Actualizar categoría
    if (isset($_POST["actualizar"])) {
        $id_cat      = $_POST["id_cat"];
        $nombre      = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $estado      = $_POST["estado"];

        try {
            $sql_up = "UPDATE categoria SET nombre = ?, descripcion = ?, estado = ? WHERE id_cat = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssi", $nombre, $descripcion, $estado, $id_cat);
            $stmt_up->execute();

            $mensaje = "<div class='alerta exito'>✅ Categoría actualizada correctamente.</div>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'>❌ Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 4. Buscar y listar categorías
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT * FROM categoria WHERE id_cat LIKE ? OR nombre LIKE ? OR descripcion LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("sss", $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $categorias = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT * FROM categoria ORDER BY id_cat ASC";
    $categorias = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/logo.png" type="image/x-icon">
    <title>Gestión de Categorías — El Progreso</title>
    <link rel="stylesheet" href="boton.css">
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
            min-height: 80px;
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
            min-width: 700px;
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

        .estado.activo   { background: #d1fae5; color: #065f46; }
        .estado.inactivo { background: #fee2e2; color: #991b1b; }

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

      <!-- Botón Volver al Menú -->
      <form action="menu.php?rol=<?php echo urlencode($rol); ?>" method="POST" style="display:inline;">
          <button type="submit" class="boton-salir">← Volver al Menú</button>
      </form>

    <div class="avatar" title="Juan Pérez">JP</div>
  </div>
</nav>

<div class="barra-naranja"></div>

<!-- ============ CONTENIDO PRINCIPAL ============ -->
<main class="contenido-principal">

  <h1 class="titulo-pagina">Gestión de Categorías de Productos</h1>
  <p class="subtitulo-pagina">Registre, modifique y consulte las categorías disponibles para los productos.</p>

  <?php echo $mensaje; ?>

  <!-- ============ FORMULARIO (EDITAR O INSERTAR) ============ -->
  <?php if ($categoria_editar): ?>
    <div class="tarjeta-seccion">
      <h2>✏️ Modificación de Categoría</h2>
      <form action="" method="POST">
        <input type="hidden" name="id_cat" value="<?php echo htmlspecialchars($categoria_editar['id_cat']); ?>">
        <input type="hidden" name="rol" value="<?php echo htmlspecialchars($rol); ?>">

        <div class="form-grid">
          <div class="grupo-campo">
            <label>Código Identificador (ID Categoría):</label>
            <input type="number" value="<?php echo htmlspecialchars($categoria_editar['id_cat']); ?>" disabled>
          </div>

          <div class="grupo-campo">
            <label>Nombre de la Categoría:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($categoria_editar['nombre']); ?>" required>
          </div>

          <div class="grupo-campo">
            <label>Estado:</label>
            <select name="estado">
              <option value="Activo" <?php if ($categoria_editar['estado'] == 'Activo') echo 'selected'; ?>>Activo</option>
              <option value="Inactivo" <?php if ($categoria_editar['estado'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
            </select>
          </div>

          <div class="grupo-campo ancho-completo">
            <label>Descripción Detallada:</label>
            <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($categoria_editar['descripcion']); ?></textarea>
          </div>
        </div>

        <div class="acciones-form">
          <button type="submit" name="actualizar" class="boton-primario">💾 Actualizar Información</button>
          <a href="?" class="boton-secundario">Cancelar</a>
        </div>
      </form>
    </div>

  <?php else: ?>
    <div class="tarjeta-seccion">
      <h2>➕ Registro de Nueva Categoría</h2>
      <form action="" method="POST">
        <div class="form-grid">
          <div class="grupo-campo">
            <label>Código Identificador (ID Categoría):</label>
            <input type="number" name="id_cat" required>
          </div>

          <div class="grupo-campo">
            <label>Nombre de la Categoría:</label>
            <input type="text" name="nombre" required>
          </div>

          <div class="grupo-campo ancho-completo">
            <label>Descripción Detallada:</label>
            <textarea name="descripcion" rows="3" required></textarea>
          </div>

          <input type="hidden" name="estado" value="Activo">
          <input type="hidden" name="rol" value="<?php echo htmlspecialchars($rol); ?>">
        </div>

        <div class="acciones-form">
          <button type="submit" name="guardar" class="boton-primario">📤 Guardar Registro</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- ============ BUSCADOR ============ -->
  <div class="tarjeta-seccion">
    <h2>🔍 Consulta y Filtro de Categorías</h2>
    <form action="" method="GET" class="form-busqueda">
      <input type="text" name="buscar" placeholder="Ingrese ID, Nombre o Descripción..." value="<?php echo htmlspecialchars($busqueda); ?>">
      <button type="submit" class="boton-primario">Ejecutar Búsqueda</button>
      <?php if (!empty($busqueda)): ?>
        <a href="?" class="boton-secundario">Restablecer Vista</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ============ LISTADO ============ -->
  <div class="tarjeta-seccion">
    <h2>📋 Listado General de Categorías Registradas</h2>
    <div class="tabla-contenedor">
      <table class="tabla-datos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($categorias && $categorias->num_rows > 0): ?>
            <?php while ($fila = $categorias->fetch_assoc()): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($fila['id_cat']); ?></strong></td>
                <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                <td>
                  <?php
                    $clase = ($fila['estado'] === 'Activo') ? 'activo' : 'inactivo';
                  ?>
                  <span class="estado <?php echo $clase; ?>"><?php echo htmlspecialchars($fila['estado']); ?></span>
                </td>
                <td class="acciones-tabla">
                  <a href="?accion=editar&id_cat=<?php echo $fila['id_cat']; ?>&rol=<?php echo urlencode($rol); ?>" class="modificar">✏️ Editar</a>
                  <a href="?accion=eliminar&id_cat=<?php echo $fila['id_cat']; ?>&rol=<?php echo urlencode($rol); ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?');">🗑️ Eliminar</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="sin-registros">No se encontraron registros de categorías en el sistema.</td>
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