<?php
/* ========== MÓDULO DE PROVEEDORES ========== */
include("../config/conexion.php");

$rol = isset($_GET['rol']) ? $_GET['rol'] : (isset($_POST['rol']) ? $_POST['rol'] : '');

if (empty($rol)) {
    header("Location: ../index.html");
    exit();
}

$permisos = [
    'Administrador' => ['producto', 'categoria', 'compra', 'factura', 'cliente', 'proveedor', 'empleado', 'cargo'],
    'Vendedor'      => ['producto', 'factura', 'cliente'],
    'Bodeguero'     => ['producto', 'categoria', 'compra', 'proveedor']
];

$modulo_actual = basename($_SERVER['PHP_SELF'], ".php");

if (!in_array($modulo_actual, $permisos[$rol] ?? [])) {
    echo "<script>
        alert('Acceso denegado. Su rol (" . $rol . ") no tiene permiso para acceder a este módulo.');
        window.location.href = '../menu.php?rol=" . urlencode($rol) . "';
    </script>";
    exit();
}

$mensaje = "";
$proveedor_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_prov'])) {
    $id_eliminar = $_GET['id_prov'];
    try {
        $stmt = $conn->prepare("DELETE FROM proveedor WHERE id_prov = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Proveedor con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_prov'])) {
    $id_buscar = $_GET['id_prov'];
    try {
        $stmt = $conn->prepare("SELECT * FROM proveedor WHERE id_prov = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $proveedor_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_prov = $_POST["id_prov"];
        $nit = $_POST["nit"];
        $nombre = $_POST["nombre"];
        $nombre_proveedor = $_POST["nombre_proveedor"];
        $telefono = $_POST["telefono"];
        $telefono_empresa = $_POST["telefono_empresa"];
        $correo = $_POST["correo"];
        $direccion = $_POST["direccion"];
        $ciudad = $_POST["ciudad"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("INSERT INTO proveedor (id_prov, nit, nombre, nombre_proveedor, telefono, telefono_empresa, correo, direccion, ciudad, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssss", $id_prov, $nit, $nombre, $nombre_proveedor, $telefono, $telefono_empresa, $correo, $direccion, $ciudad, $estado);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Proveedor registrado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_prov) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_prov = $_POST["id_prov"];
        $nit = $_POST["nit"];
        $nombre = $_POST["nombre"];
        $nombre_proveedor = $_POST["nombre_proveedor"];
        $telefono = $_POST["telefono"];
        $telefono_empresa = $_POST["telefono_empresa"];
        $correo = $_POST["correo"];
        $direccion = $_POST["direccion"];
        $ciudad = $_POST["ciudad"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("UPDATE proveedor SET nit = ?, nombre = ?, nombre_proveedor = ?, telefono = ?, telefono_empresa = ?, correo = ?, direccion = ?, ciudad = ?, estado = ? WHERE id_prov = ?");
            $stmt->bind_param("sssssssssi", $nit, $nombre, $nombre_proveedor, $telefono, $telefono_empresa, $correo, $direccion, $ciudad, $estado, $id_prov);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Proveedor actualizado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare("SELECT * FROM proveedor WHERE id_prov LIKE ? OR nit LIKE ? OR nombre LIKE ? OR nombre_proveedor LIKE ? OR telefono LIKE ? OR telefono_empresa LIKE ? OR correo LIKE ? OR direccion LIKE ? OR ciudad LIKE ?");
    $stmt->bind_param("sssssssss", $param, $param, $param, $param, $param, $param, $param, $param, $param);
    $stmt->execute();
    $proveedores = $stmt->get_result();
} else {
    $proveedores = $conn->query("SELECT * FROM proveedor ORDER BY id_prov ASC");
}

$titulo_pagina = "Gestión de Proveedores";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Proveedores</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte la información de los proveedores.</p>

<?= $mensaje ?>

<?php if ($proveedor_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Proveedor</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_prov" value="<?= htmlspecialchars($proveedor_editar['id_prov']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Proveedor:</label>
          <input type="number" value="<?= htmlspecialchars($proveedor_editar['id_prov']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>NIT:</label>
          <input type="text" name="nit" value="<?= htmlspecialchars($proveedor_editar['nit']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre Empresa:</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($proveedor_editar['nombre']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre del Contacto:</label>
          <input type="text" name="nombre_proveedor" value="<?= htmlspecialchars($proveedor_editar['nombre_proveedor']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Teléfono:</label>
          <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor_editar['telefono']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Teléfono Empresa:</label>
          <input type="text" name="telefono_empresa" value="<?= htmlspecialchars($proveedor_editar['telefono_empresa']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Correo:</label>
          <input type="email" name="correo" value="<?= htmlspecialchars($proveedor_editar['correo']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Dirección:</label>
          <input type="text" name="direccion" value="<?= htmlspecialchars($proveedor_editar['direccion']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Ciudad:</label>
          <input type="text" name="ciudad" value="<?= htmlspecialchars($proveedor_editar['ciudad']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Estado:</label>
          <select name="estado">
            <option value="Activo" <?= $proveedor_editar['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo" <?= $proveedor_editar['estado'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>
      </div>
      <div class="acciones-form">
        <button type="submit" name="actualizar" class="boton-primario"><i class="fa-solid fa-floppy-disk"></i> Actualizar Información</button>
        <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-plus"></i> Registro de Nuevo Proveedor</h2>
    <form action="" method="POST">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Proveedor:</label>
          <input type="number" name="id_prov" required>
        </div>
        <div class="grupo-campo">
          <label>NIT:</label>
          <input type="text" name="nit" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre Empresa:</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre del Contacto:</label>
          <input type="text" name="nombre_proveedor">
        </div>
        <div class="grupo-campo">
          <label>Teléfono:</label>
          <input type="text" name="telefono" required>
        </div>
        <div class="grupo-campo">
          <label>Teléfono Empresa:</label>
          <input type="text" name="telefono_empresa">
        </div>
        <div class="grupo-campo">
          <label>Correo:</label>
          <input type="email" name="correo">
        </div>
        <div class="grupo-campo">
          <label>Dirección:</label>
          <input type="text" name="direccion">
        </div>
        <div class="grupo-campo">
          <label>Ciudad:</label>
          <input type="text" name="ciudad">
        </div>
        <input type="hidden" name="estado" value="Activo">
        <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      </div>
      <div class="acciones-form">
        <button type="submit" name="guardar" class="boton-primario"><i class="fa-solid fa-paper-plane"></i> Guardar Registro</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta y Filtro de Proveedores</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, NIT, Nombre, Teléfono, Correo, Ciudad..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Ejecutar Búsqueda</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Restablecer Vista</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Proveedores Registrados</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>NIT</th><th>Empresa</th><th>Contacto</th><th>Tel. Proveedor</th><th>Tel. Empresa</th><th>Correo</th><th>Dirección</th><th>Ciudad</th><th>Estado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($proveedores && $proveedores->num_rows > 0): ?>
          <?php while ($fila = $proveedores->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_prov']) ?></strong></td>
              <td><?= htmlspecialchars($fila['nit']) ?></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['nombre_proveedor'] ?? '') ?></td>
              <td><?= htmlspecialchars($fila['telefono']) ?></td>
              <td><?= htmlspecialchars($fila['telefono_empresa'] ?? '') ?></td>
              <td><?= htmlspecialchars($fila['correo'] ?? '') ?></td>
              <td><?= htmlspecialchars($fila['direccion'] ?? '') ?></td>
              <td><?= htmlspecialchars($fila['ciudad'] ?? '') ?></td>
              <td>
                <span class="estado <?= $fila['estado'] === 'Activo' ? 'activo' : 'inactivo' ?>">
                  <?= htmlspecialchars($fila['estado']) ?>
                </span>
              </td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_prov=<?= $fila['id_prov'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_prov=<?= $fila['id_prov'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar este proveedor?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="11" class="sin-registros">No se encontraron registros de proveedores.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>