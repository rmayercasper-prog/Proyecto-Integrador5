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
                            <a href="?eliminar_u=<?php echo $user['id_usuario']; ?>" 
                               onclick="return confirm('¿Eliminar usuario?')" 
                               style="color:red; text-decoration:none;">❌ Eliminar</a>
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

            <h3>Inventario de Libros</h3>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>ISBN</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Estado</th>
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
                        <td><?php echo $row['id_categoria'] ?? 'N/A'; ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td>
                            <?php if ($row['stock'] > 0): ?>
                                <span class="status disponible">DISPONIBLE</span>
                            <?php else: ?>
                                <span class="status prestado">AGOTADO</span>
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