<?php
session_start();
include 'Conexion.php';

$mensaje = "";
$modo = $_GET['modo'] ?? 'login';

// --- 1. LÓGICA DE USUARIOS ---
if (isset($_POST['registrar_usuario'])) {
    $u = $_POST['u']; 
    $p = $_POST['p']; 
    $r = $_POST['r'];
    $sql = "INSERT INTO usuario (nombre, correo, password, id_rol) VALUES ('$u', '$u', '$p', '$r')";
    if ($conn->query($sql)) { 
        $mensaje = "Usuario creado con éxito."; 
        $modo = 'login'; 
    }
}

if (isset($_POST['login'])) {
    $u = $_POST['u'];
    $p = $_POST['p'];
    $res = $conn->query("SELECT * FROM usuario WHERE correo='$u' AND password='$p'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['usuario'] = $row['nombre'];
        $_SESSION['rol'] = $row['id_rol'];
        header("Location: index.php");
        exit();
    } else {
        $mensaje = "Usuario o contraseña incorrectos.";
    }
}

if (isset($_GET['salir'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// --- 2. LÓGICA DE LIBROS ---
if (isset($_POST['guardar_libro']) && $_SESSION['rol'] == '1') {
    $t = $_POST['titulo']; $a = $_POST['isbn']; $c = $_POST['cat'];
    $conn->query("INSERT INTO libro (titulo, isbn, id_categoria, stock) VALUES ('$t', '$a', '$c', 0)");
}
// --- LÓGICA DE LIBROS (AMPLIADA) ---

// ELIMINAR LIBRO
if (isset($_GET['eliminar_id']) && $_SESSION['rol'] == '1') {
    $id = $_GET['eliminar_id'];
    $conn->query("DELETE FROM libro WHERE id_libro = $id");
    header("Location: index.php?mensaje=Libro eliminado");
    exit();
}

// ACTUALIZAR LIBRO (Se activa al enviar el formulario de edición)
if (isset($_POST['actualizar_libro']) && $_SESSION['rol'] == '1') {
    $id = $_POST['id_libro'];
    $t = $_POST['titulo']; 
    $i = $_POST['isbn']; 
    $s = $_POST['stock'];
    $conn->query("UPDATE libro SET titulo='$t', isbn='$i', stock='$s' WHERE id_libro = $id");
    header("Location: index.php?mensaje=Libro actualizado");
    exit();
}
// --- 3. LÓGICA DE HISTORIA (Solo Admin) ---
if (isset($_POST['actualizar_historia']) && $_SESSION['rol'] == '1') {
    // Escapamos el texto para evitar errores con comillas
    $nuevo_texto = mysqli_real_escape_string($conn, $_POST['nuevo_contenido']);
    $conn->query("UPDATE informacion SET contenido = '$nuevo_texto' WHERE clave = 'historia'");
    $mensaje = "Historia actualizada correctamente.";
}
// --- 4. LÓGICA DE PRÉSTAMOS ---
if (isset($_GET['pedir_id']) && isset($_SESSION['id_usuario'])) {
    $id_libro = $_GET['pedir_id'];
    $id_user = $_SESSION['id_usuario'];

    // Verificamos stock disponible antes de prestar
    $resLibro = $conn->query("SELECT stock FROM libro WHERE id_libro = $id_libro");
    $libro = $resLibro->fetch_assoc();

    if ($libro['stock'] > 0) {
        $conn->query("INSERT INTO prestamo (id_usuario, id_libro, fecha_prestamo, estado) 
        VALUES ($id_user, $id_libro, NOW(), 'Pendiente')");
        $conn->query("UPDATE libro SET stock = stock - 1 WHERE id_libro = $id_libro");
        
        header("Location: index.php?mensaje=Libro pedido correctamente");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Biblioteca Inmaculada Concepción</title>
    <link rel="stylesheet" href="Estilos.css">
</head>
<body>

<?php if (!isset($_SESSION['usuario'])): ?>
    <div class="auth-container">
        <h2 style="text-align:center; color:black;">Bienvenido a la Biblioteca Inmaculada Concepción</h2>
        <h2 style="text-align:center; color:#004a99;">Acceso A Biblioteca</h2>
        <p style="color:red;"><?php echo $mensaje; ?></p>

        <?php if ($modo == 'login'): ?>
            <form method="POST">
                <input type="text" name="u" placeholder="Correo electrónico" required>
                <input type="password" name="p" placeholder="Contraseña" required>
                <button type="submit" name="login" style="background:#004a99; color:white; border:none; cursor:pointer;">Entrar</button>
            </form>
            <p align="center"><a href="?modo=registro">¿No tienes cuenta? Regístrate</a></p>
        <?php else: ?>
            <form method="POST">
                <input type="text" name="u" placeholder="Nombre completo" required>
                <input type="password" name="p" placeholder="Crear Contraseña" required>
                <select name="r">
                    <option value="4">Estudiante</option>
                    <option value="5">Profesor</option>
                    <option value="1">Administrador</option>
                </select>
                <button type="submit" name="registrar_usuario" class="btn-green">Crear Cuenta</button>
            </form>
            <p align="center"><a href="?modo=login">Volver al inicio de sesión</a></p>
        <?php endif; ?>
    </div>

<?php else: ?>
    <nav>
        <span><strong>📖 Institución Inmaculada Concepción</strong></span>
        <div>
            <span>👤 <?php echo $_SESSION['usuario']; ?> (Rol: <?php echo $_SESSION['rol']; ?>)</span>  
            <a href="?salir=1" style="color:#ffc107; text-decoration:none; margin-left:15px;">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="menu">
        <a href="index.php">📚 Libros</a>
        <?php if ($_SESSION['rol'] == 1): ?>
            <a href="?seccion=usuarios" >👥 Gestionar Usuarios</a>
        <?php endif; ?>
        <a href="#">📂 Categorías</a>
        <a href="#">🏛️ Historia</a>
        <a href="#">📞 Contactos</a>
        <a href="#">❓ Ayuda</a>
    </div>

    <div class="container">
        <?php if (isset($_GET['seccion']) && $_GET['seccion'] == 'usuarios' && $_SESSION['rol'] == 1): ?>
            <h3>Gestión de Usuarios Registrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $resU = $conn->query("SELECT * FROM usuario");
                    while($user = $resU->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $user['id_usuario']; ?></td>
                        <td><?php echo $user['nombre']; ?></td>
                        <td><?php echo $user['correo']; ?></td>
                        <td><?php echo $user['id_rol']; ?></td>
                        <td>
                      <a href="?seccion=usuarios&editar_u=<?php echo $user['id_usuario']; ?>" style="text-decoration:none;">✏️ Actualizar</a> | 
        <a href="?eliminar_u=<?php echo $user['id_usuario']; ?>" 
           style="color:red; text-decoration:none;" 
           onclick="return confirm('¿Eliminar usuario?')">❌ Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        
        <?php else: ?>
            <div class="search-box">
                <form method="GET">
                    <input type="text" name="buscar" placeholder="Buscar por título o autor..." style="width:80%" value="<?php echo $_GET['buscar'] ?? ''; ?>">
                    <button type="submit" style="width:18%; background:#004a99; color:white; border:none; cursor:pointer;">Buscar</button>
                </form>
            </div>

            <?php if ($_SESSION['rol'] == '1'): ?>
                <div class="admin-box">
                    <h3>➕ Registrar Nuevo Libro</h3>
                    <form method="POST" style="display:flex; gap:10px;">
                        <input type="text" name="titulo" placeholder="Título" required>
                        <input type="text" name="isbn" placeholder="ISBN" required>
                        <select name="cat">
                            <option value="1">Literatura</option>
                            <option value="2">Ciencias</option>
                        </select>
                        <button type="submit" name="guardar_libro" class="btn-green">Guardar</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php 
if (isset($_GET['editar_id']) && $_SESSION['rol'] == '1'): 
    $id_edit = $_GET['editar_id'];
    $resEdit = $conn->query("SELECT * FROM libro WHERE id_libro = $id_edit");
    $libroEdit = $resEdit->fetch_assoc();
?>
    <div class="admin-box" style="border: 2px solid #004a99; margin-bottom: 20px; background-color: #f9f9f9;">
        <h3 style="color: #004a99;">✏️ Editando: <?php echo $libroEdit['titulo']; ?></h3>
        <form method="POST" style="display:flex; gap:10px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="id_libro" value="<?php echo $libroEdit['id_libro']; ?>">
            
            <div style="flex: 1;">
                <label style="font-size: 0.8em; display: block;">Título:</label>
                <input type="text" name="titulo" value="<?php echo $libroEdit['titulo']; ?>" required style="width: 100%;">
            </div>
            
            <div style="flex: 1;">
                <label style="font-size: 0.8em; display: block;">ISBN:</label>
                <input type="text" name="isbn" value="<?php echo $libroEdit['isbn']; ?>" required style="width: 100%;">
            </div>
            
            <div style="width: 80px;">
                <label style="font-size: 0.8em; display: block;">Stock:</label>
                <input type="number" name="stock" value="<?php echo $libroEdit['stock']; ?>" required style="width: 100%;">
            </div>
            
            <div style="align-self: flex-end; display: flex; gap: 5px;">
                <button type="submit" name="actualizar_libro" class="btn-green">Actualizar</button>
                <a href="index.php" style="background: #ccc; color: black; padding: 10px; text-decoration: none; border-radius: 4px; font-size: 0.9em;">Cancelar</a>
            </div>
        </form>
    </div>
<?php endif; ?>

            <h3>Inventario de Libros</h3>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>ISBN</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $busqueda = $_GET['buscar'] ?? '';
                    $sql = "SELECT * FROM libro WHERE titulo LIKE '%$busqueda%' OR isbn LIKE '%$busqueda%'";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row['titulo']; ?></td>
                        <td><?php echo $row['isbn']; ?></td>
                        <td><?php echo $row['id_categoria'] ?? '1'; ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td>
                            <?php if ($row['stock'] > 0): ?>
                                <span class="status disponible">DISPONIBLE</span>
                            <?php else: ?>
                                <span class="status prestado">AGOTADO</span>
                            <?php endif; ?>
                        </td>
                    <td>
                <?php if ($_SESSION['rol'] == '1'): ?>
                <a href="?editar_id=<?php echo $row['id_libro']; ?>" style="text-decoration:none;">✏️ Editar</a> | 
                <a href="?eliminar_id=<?php echo $row['id_libro']; ?>" 
                   style="color:red; text-decoration:none;" 
                   onclick="return confirm('¿Seguro que deseas eliminar este libro?')">🗑️ Eliminar</a>
            <?php elseif (($_SESSION['rol'] == '4' || $_SESSION['rol'] == '5') && $row['stock'] > 0): ?>
                <a href="?pedir_id=<?php echo $row['id_libro']; ?>" class="btn-pedido">📖 Pedir</a>
            <?php endif; ?>
                    </td>
              </tr>
                    <?php endwhile; ?> 
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>


</body>
</html>