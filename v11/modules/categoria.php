<?php
/* ========== MÓDULO DE CATEGORÍAS ========== */
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
$categoria_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_cat'])) {
    $id_eliminar = $_GET['id_cat'];
    try {
        $stmt = $conn->prepare("DELETE FROM categoria WHERE id_cat = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Categoría con ID " . htmlspecialchars($id_eliminar) . " eliminada correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_cat'])) {
    $id_buscar = $_GET['id_cat'];
    try {
        $stmt = $conn->prepare("SELECT * FROM categoria WHERE id_cat = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $categoria_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_cat = $_POST["id_cat"];
        $nombre = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("INSERT INTO categoria (id_cat, nombre, descripcion, estado) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $id_cat, $nombre, $descripcion, $estado);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Categoría registrada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_cat) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_cat = $_POST["id_cat"];
        $nombre = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("UPDATE categoria SET nombre = ?, descripcion = ?, estado = ? WHERE id_cat = ?");
            $stmt->bind_param("sssi", $nombre, $descripcion, $estado, $id_cat);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Categoría actualizada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare("SELECT * FROM categoria WHERE id_cat LIKE ? OR nombre LIKE ? OR descripcion LIKE ?");
    $stmt->bind_param("sss", $param, $param, $param);
    $stmt->execute();
    $categorias = $stmt->get_result();
} else {
    $categorias = $conn->query("SELECT * FROM categoria ORDER BY id_cat ASC");
}

$titulo_pagina = "Gestión de Categorías";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Categorías de Productos</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte las categorías disponibles para los productos.</p>

<?= $mensaje ?>

<?php if ($categoria_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Categoría</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_cat" value="<?= htmlspecialchars($categoria_editar['id_cat']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Categoría):</label>
          <input type="number" value="<?= htmlspecialchars($categoria_editar['id_cat']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Nombre de la Categoría:</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($categoria_editar['nombre']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Estado:</label>
          <select name="estado">
            <option value="Activo" <?= $categoria_editar['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo" <?= $categoria_editar['estado'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Descripción Detallada:</label>
          <textarea name="descripcion" rows="3" required><?= htmlspecialchars($categoria_editar['descripcion']) ?></textarea>
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
    <h2><i class="fa-solid fa-plus"></i> Registro de Nueva Categoría</h2>
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
        <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      </div>
      <div class="acciones-form">
        <button type="submit" name="guardar" class="boton-primario"><i class="fa-solid fa-paper-plane"></i> Guardar Registro</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta y Filtro de Categorías</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, Nombre o Descripción..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Ejecutar Búsqueda</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Restablecer Vista</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Categorías Registradas</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($categorias && $categorias->num_rows > 0): ?>
          <?php while ($fila = $categorias->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_cat']) ?></strong></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['descripcion']) ?></td>
              <td>
                <span class="estado <?= $fila['estado'] === 'Activo' ? 'activo' : 'inactivo' ?>">
                  <?= htmlspecialchars($fila['estado']) ?>
                </span>
              </td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_cat=<?= $fila['id_cat'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_cat=<?= $fila['id_cat'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="sin-registros">No se encontraron registros de categorías en el sistema.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>