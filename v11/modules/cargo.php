<?php
/* ========== MÓDULO DE CARGOS ========== */
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
$cargo_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_car'])) {
    $id_eliminar = $_GET['id_car'];
    try {
        $stmt_del = $conn->prepare("DELETE FROM cargo WHERE id_car = ?");
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Cargo con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</div>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_car'])) {
    $id_buscar = $_GET['id_car'];
    try {
        $stmt = $conn->prepare("SELECT * FROM cargo WHERE id_car = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $cargo_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_car = $_POST["id_car"];
        $nombre = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $salario = $_POST["salario"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("INSERT INTO cargo (id_car, nombre, descripcion, salario, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $id_car, $nombre, $descripcion, $salario, $estado);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Cargo registrado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_car) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_car = $_POST["id_car"];
        $nombre = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $salario = $_POST["salario"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("UPDATE cargo SET nombre = ?, descripcion = ?, salario = ?, estado = ? WHERE id_car = ?");
            $stmt->bind_param("ssdsi", $nombre, $descripcion, $salario, $estado, $id_car);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Cargo actualizado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare("SELECT * FROM cargo WHERE id_car LIKE ? OR nombre LIKE ? OR descripcion LIKE ? OR salario LIKE ?");
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    $stmt->execute();
    $cargos = $stmt->get_result();
} else {
    $cargos = $conn->query("SELECT * FROM cargo ORDER BY id_car ASC");
}

$titulo_pagina = "Gestión de Cargos";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Cargos</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte los cargos y salarios de la organización.</p>

<?= $mensaje ?>

<?php if ($cargo_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Cargo</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_car" value="<?= htmlspecialchars($cargo_editar['id_car']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Cargo):</label>
          <input type="number" value="<?= htmlspecialchars($cargo_editar['id_car']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Denominación del Cargo:</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($cargo_editar['nombre']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Asignación Salarial (Monto Base):</label>
          <input type="number" step="0.01" name="salario" value="<?= htmlspecialchars($cargo_editar['salario']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Estado:</label>
          <select name="estado">
            <option value="Activo" <?= $cargo_editar['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo" <?= $cargo_editar['estado'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Descripción:</label>
          <textarea name="descripcion" rows="3"><?= htmlspecialchars($cargo_editar['descripcion']) ?></textarea>
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
    <h2><i class="fa-solid fa-plus"></i> Registro de Nuevo Cargo</h2>
    <form action="" method="POST">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Cargo):</label>
          <input type="number" name="id_car" required>
        </div>
        <div class="grupo-campo">
          <label>Denominación del Cargo:</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="grupo-campo">
          <label>Asignación Salarial (Monto Base):</label>
          <input type="number" step="0.01" name="salario" required>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Descripción:</label>
          <textarea name="descripcion" rows="3"></textarea>
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
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta de Cargos</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Descripción o Salario..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Ejecutar Búsqueda</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Restablecer Vista</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Cargos Registrados</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Salario</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($cargos && $cargos->num_rows > 0): ?>
          <?php while ($fila = $cargos->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_car']) ?></strong></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['descripcion']) ?></td>
              <td>$<?= number_format($fila['salario'], 2) ?></td>
              <td>
                <span class="estado <?= $fila['estado'] === 'Activo' ? 'activo' : 'inactivo' ?>">
                  <?= htmlspecialchars($fila['estado']) ?>
                </span>
              </td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_car=<?= $fila['id_car'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_car=<?= $fila['id_car'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar este cargo?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6" class="sin-registros">No se encontraron registros de cargos en el sistema.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>