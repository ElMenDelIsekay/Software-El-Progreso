<?php
/* ========== MÓDULO DE PRODUCTOS ========== */
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
$producto_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function calcularEstado($stock, $stock_minimo = 5) {
    if ($stock <= 0) return "Agotado";
    if ($stock <= $stock_minimo) return "Bajo Stock";
    return "Disponible";
}

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_prod'])) {
    $id_eliminar = $_GET['id_prod'];
    try {
        $stmt = $conn->prepare("DELETE FROM producto WHERE id_prod = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Producto con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_prod'])) {
    $id_buscar = $_GET['id_prod'];
    try {
        $stmt = $conn->prepare("SELECT * FROM producto WHERE id_prod = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $producto_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_prod = $_POST["id_prod"];
        $nombre = $_POST["nombre"];
        $codigo = $_POST["codigo"];
        $descripcion = $_POST["descripcion"];
        $precio = $_POST["precio"];
        $stock = $_POST["stock"];
        $fecha_ingreso = $_POST["fecha_ingreso"];
        $stock_minimo = $_POST["stock_minimo"];
        $id_fk_cat = $_POST["id_fk_cat"];
        $id_fk_prov = $_POST["id_fk_prov"];
        $estado = calcularEstado($stock, $stock_minimo);
        try {
            $stmt = $conn->prepare("INSERT INTO producto (id_prod, nombre, codigo, descripcion, precio, estado, stock, fecha_ingreso, stock_minimo, id_fk_cat, id_fk_prov) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdsisiii", $id_prod, $nombre, $codigo, $descripcion, $precio, $estado, $stock, $fecha_ingreso, $stock_minimo, $id_fk_cat, $id_fk_prov);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Producto registrado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_prod) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_prod = $_POST["id_prod"];
        $nombre = $_POST["nombre"];
        $codigo = $_POST["codigo"];
        $descripcion = $_POST["descripcion"];
        $precio = $_POST["precio"];
        $stock = $_POST["stock"];
        $fecha_ingreso = $_POST["fecha_ingreso"];
        $stock_minimo = $_POST["stock_minimo"];
        $id_fk_cat = $_POST["id_fk_cat"];
        $id_fk_prov = $_POST["id_fk_prov"];
        $estado = calcularEstado($stock, $stock_minimo);
        try {
            $stmt = $conn->prepare("UPDATE producto SET nombre = ?, codigo = ?, descripcion = ?, precio = ?, estado = ?, stock = ?, fecha_ingreso = ?, stock_minimo = ?, id_fk_cat = ?, id_fk_prov = ? WHERE id_prod = ?");
            $stmt->bind_param("sssdsisiii", $nombre, $codigo, $descripcion, $precio, $estado, $stock, $fecha_ingreso, $stock_minimo, $id_fk_cat, $id_fk_prov, $id_prod);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Producto actualizado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . $e->getMessage() . "</div>";
        }
    }
}

$sql_base = "SELECT p.id_prod, p.nombre, p.codigo, p.descripcion, p.precio, p.estado, p.stock, p.fecha_ingreso, p.stock_minimo, p.id_fk_cat, p.id_fk_prov,
                    c.nombre AS nom_categoria, pr.nombre AS nom_proveedor
             FROM producto p
             LEFT JOIN categoria c ON p.id_fk_cat = c.id_cat
             LEFT JOIN proveedor pr ON p.id_fk_prov = pr.id_prov";

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare($sql_base . " WHERE p.id_prod LIKE ? OR p.nombre LIKE ? OR p.codigo LIKE ? OR p.estado LIKE ? OR p.fecha_ingreso LIKE ? ORDER BY p.id_prod ASC");
    $stmt->bind_param("sssss", $param, $param, $param, $param, $param);
    $stmt->execute();
    $productos = $stmt->get_result();
} else {
    $productos = $conn->query($sql_base . " ORDER BY p.id_prod ASC");
}

$titulo_pagina = "Gestión de Productos";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Productos</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte el catálogo de productos disponibles.</p>

<?= $mensaje ?>

<?php if ($producto_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Producto</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_prod" value="<?= htmlspecialchars($producto_editar['id_prod']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Producto:</label>
          <input type="number" value="<?= htmlspecialchars($producto_editar['id_prod']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Nombre del Producto:</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($producto_editar['nombre']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Código / Referencia:</label>
          <input type="text" name="codigo" value="<?= htmlspecialchars($producto_editar['codigo']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Precio Unitario:</label>
          <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($producto_editar['precio']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Estado Actual:</label>
          <input type="text" value="<?= htmlspecialchars($producto_editar['estado']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Existencias (Stock):</label>
          <input type="number" name="stock" value="<?= htmlspecialchars($producto_editar['stock']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha de Ingreso:</label>
          <input type="date" name="fecha_ingreso" value="<?= htmlspecialchars($producto_editar['fecha_ingreso']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Stock Mínimo:</label>
          <input type="number" name="stock_minimo" value="<?= htmlspecialchars($producto_editar['stock_minimo']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Categoría:</label>
          <select name="id_fk_cat" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_cat, nombre FROM categoria ORDER BY id_cat ASC");
            while ($c = $res->fetch_assoc()) {
                $sel = ($producto_editar['id_fk_cat'] == $c['id_cat']) ? "selected" : "";
                echo "<option value='{$c['id_cat']}' $sel>{$c['id_cat']} - {$c['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Proveedor:</label>
          <select name="id_fk_prov" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC");
            while ($p = $res->fetch_assoc()) {
                $sel = ($producto_editar['id_fk_prov'] == $p['id_prov']) ? "selected" : "";
                echo "<option value='{$p['id_prov']}' $sel>{$p['id_prov']} - {$p['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Descripción:</label>
          <textarea name="descripcion" rows="3" required><?= htmlspecialchars($producto_editar['descripcion']) ?></textarea>
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
    <h2><i class="fa-solid fa-plus"></i> Registro de Nuevo Producto</h2>
    <form action="" method="POST">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Producto:</label>
          <input type="number" name="id_prod" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre del Producto:</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="grupo-campo">
          <label>Código / Referencia:</label>
          <input type="text" name="codigo">
        </div>
        <div class="grupo-campo">
          <label>Precio Unitario:</label>
          <input type="number" step="0.01" name="precio" required>
        </div>
        <div class="grupo-campo">
          <label>Existencias (Stock):</label>
          <input type="number" name="stock" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha de Ingreso:</label>
          <input type="date" name="fecha_ingreso">
        </div>
        <div class="grupo-campo">
          <label>Stock Mínimo:</label>
          <input type="number" name="stock_minimo" value="5" required>
        </div>
        <div class="grupo-campo">
          <label>Categoría:</label>
          <select name="id_fk_cat" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_cat, nombre FROM categoria ORDER BY id_cat ASC");
            while ($c = $res->fetch_assoc()) {
                echo "<option value='{$c['id_cat']}'>{$c['id_cat']} - {$c['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Proveedor:</label>
          <select name="id_fk_prov" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC");
            while ($p = $res->fetch_assoc()) {
                echo "<option value='{$p['id_prov']}'>{$p['id_prov']} - {$p['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Descripción:</label>
          <textarea name="descripcion" rows="3" required></textarea>
        </div>
      </div>
      <div class="acciones-form">
        <button type="submit" name="guardar" class="boton-primario"><i class="fa-solid fa-paper-plane"></i> Guardar Registro</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta de Productos</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Código, Estado o Fecha..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Ejecutar Búsqueda</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Restablecer Vista</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Productos Registrados</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Código</th><th>Descripción</th><th>Precio</th><th>Estado</th><th>Stock</th><th>F. Ingreso</th><th>Stock Mín.</th><th>Categoría</th><th>Proveedor</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($productos && $productos->num_rows > 0): ?>
          <?php while ($fila = $productos->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_prod']) ?></strong></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['codigo']) ?></td>
              <td><?= htmlspecialchars($fila['descripcion']) ?></td>
              <td>$<?= number_format($fila['precio'], 2) ?></td>
              <td>
                <?php
                $clase = 'disponible';
                if ($fila['estado'] === 'Bajo Stock') $clase = 'bajo-stock';
                else if ($fila['estado'] === 'Agotado') $clase = 'agotado';
                ?>
                <span class="estado <?= $clase ?>"><?= htmlspecialchars($fila['estado']) ?></span>
              </td>
              <td><?= htmlspecialchars($fila['stock']) ?></td>
              <td><?= htmlspecialchars($fila['fecha_ingreso']) ?></td>
              <td><?= htmlspecialchars($fila['stock_minimo']) ?></td>
              <td><?= $fila['id_fk_cat'] . ' - ' . htmlspecialchars($fila['nom_categoria'] ?? 'Sin asignar') ?></td>
              <td><?= $fila['id_fk_prov'] . ' - ' . htmlspecialchars($fila['nom_proveedor'] ?? 'Sin asignar') ?></td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_prod=<?= $fila['id_prod'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_prod=<?= $fila['id_prod'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar este producto?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="12" class="sin-registros">No se encontraron registros de productos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>