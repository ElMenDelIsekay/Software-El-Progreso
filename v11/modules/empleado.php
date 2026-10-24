<?php
/* ========== MÓDULO DE EMPLEADOS ========== */
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
$empleado_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_emp'])) {
    $id_eliminar = $_GET['id_emp'];
    try {
        $stmt = $conn->prepare("DELETE FROM empleado WHERE id_emp = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Empleado con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_emp'])) {
    $id_buscar = $_GET['id_emp'];
    try {
        $stmt = $conn->prepare("SELECT * FROM empleado WHERE id_emp = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $empleado_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_emp = $_POST["id_emp"];
        $nombre = $_POST["nombre"];
        $telefono = $_POST["telefono"];
        $direccion = $_POST["direccion"];
        $email = $_POST["email"];
        $fecha_contrato = $_POST["fecha_contrato"];
        $id_fk_car = $_POST["id_fk_car"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("INSERT INTO empleado (id_emp, nombre, telefono, direccion, email, fecha_contrato, id_fk_car, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssis", $id_emp, $nombre, $telefono, $direccion, $email, $fecha_contrato, $id_fk_car, $estado);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Empleado registrado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_emp) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_emp = $_POST["id_emp"];
        $nombre = $_POST["nombre"];
        $telefono = $_POST["telefono"];
        $direccion = $_POST["direccion"];
        $email = $_POST["email"];
        $fecha_contrato = $_POST["fecha_contrato"];
        $id_fk_car = $_POST["id_fk_car"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("UPDATE empleado SET nombre = ?, telefono = ?, direccion = ?, email = ?, fecha_contrato = ?, id_fk_car = ?, estado = ? WHERE id_emp = ?");
            $stmt->bind_param("sssssisi", $nombre, $telefono, $direccion, $email, $fecha_contrato, $id_fk_car, $estado, $id_emp);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Empleado actualizado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

$sql_base = "SELECT e.id_emp, e.nombre, e.telefono, e.direccion, e.email, e.fecha_contrato, e.id_fk_car, e.estado, c.nombre AS nom_cargo
             FROM empleado e LEFT JOIN cargo c ON e.id_fk_car = c.id_car";

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare($sql_base . " WHERE e.id_emp LIKE ? OR e.nombre LIKE ? OR e.telefono LIKE ? OR e.email LIKE ? ORDER BY e.id_emp ASC");
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    $stmt->execute();
    $empleados = $stmt->get_result();
} else {
    $empleados = $conn->query($sql_base . " ORDER BY e.id_emp ASC");
}

$titulo_pagina = "Gestión de Empleados";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Empleados</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte la información del personal de la empresa.</p>

<?= $mensaje ?>

<?php if ($empleado_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Empleado</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_emp" value="<?= htmlspecialchars($empleado_editar['id_emp']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Empleado:</label>
          <input type="number" value="<?= htmlspecialchars($empleado_editar['id_emp']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Nombre Completo:</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($empleado_editar['nombre']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Teléfono:</label>
          <input type="text" name="telefono" value="<?= htmlspecialchars($empleado_editar['telefono']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Correo Electrónico:</label>
          <input type="email" name="email" value="<?= htmlspecialchars($empleado_editar['email']) ?>">
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Dirección:</label>
          <input type="text" name="direccion" value="<?= htmlspecialchars($empleado_editar['direccion']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha de Contrato:</label>
          <input type="date" name="fecha_contrato" value="<?= htmlspecialchars($empleado_editar['fecha_contrato']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Cargo:</label>
          <select name="id_fk_car" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_car, nombre FROM cargo ORDER BY id_car ASC");
            while ($c = $res->fetch_assoc()) {
                $sel = ($empleado_editar['id_fk_car'] == $c['id_car']) ? 'selected' : '';
                echo "<option value='{$c['id_car']}' $sel>{$c['id_car']} - {$c['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Estado:</label>
          <select name="estado">
            <option value="Activo" <?= $empleado_editar['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo" <?= $empleado_editar['estado'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
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
    <h2><i class="fa-solid fa-plus"></i> Registro de Nuevo Empleado</h2>
    <form action="" method="POST">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Empleado:</label>
          <input type="number" name="id_emp" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre Completo:</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="grupo-campo">
          <label>Teléfono:</label>
          <input type="text" name="telefono" required>
        </div>
        <div class="grupo-campo">
          <label>Correo Electrónico:</label>
          <input type="email" name="email">
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Dirección:</label>
          <input type="text" name="direccion" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha de Contrato:</label>
          <input type="date" name="fecha_contrato" required>
        </div>
        <div class="grupo-campo">
          <label>Cargo:</label>
          <select name="id_fk_car" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_car, nombre FROM cargo ORDER BY id_car ASC");
            while ($c = $res->fetch_assoc()) {
                echo "<option value='{$c['id_car']}'>{$c['id_car']} - {$c['nombre']}</option>";
            }
            ?>
          </select>
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
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta de Empleados</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Teléfono o Email..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Ejecutar Búsqueda</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Restablecer Vista</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Empleados Registrados</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Teléfono</th><th>Dirección</th><th>Email</th><th>F. Contrato</th><th>Cargo</th><th>Estado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($empleados && $empleados->num_rows > 0): ?>
          <?php while ($fila = $empleados->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_emp']) ?></strong></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['telefono']) ?></td>
              <td><?= htmlspecialchars($fila['direccion']) ?></td>
              <td><?= htmlspecialchars($fila['email']) ?></td>
              <td><?= htmlspecialchars($fila['fecha_contrato']) ?></td>
              <td><?= $fila['id_fk_car'] . ' - ' . htmlspecialchars($fila['nom_cargo'] ?? 'Sin asignar') ?></td>
              <td>
                <span class="estado <?= $fila['estado'] === 'Activo' ? 'activo' : 'inactivo' ?>">
                  <?= htmlspecialchars($fila['estado']) ?>
                </span>
              </td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_emp=<?= $fila['id_emp'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_emp=<?= $fila['id_emp'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar este empleado?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="9" class="sin-registros">No se encontraron registros de empleados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>