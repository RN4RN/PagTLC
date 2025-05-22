<?php
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: http://localhost/nuevo/contrase%C3%B1a/indexlogin.php");
    exit();
}
?>
<?php
include("conexion1.php");

// Comprobamos si el usuario tiene el rol de Director
if ($_SESSION['rol'] != 'Director') {
    header("Location: index.php");
    exit;
}


// Asignar rol a un usuario
// Asignar rol a un usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asignar_rol'])) {
  $id_usuario = $_POST['id_usuario'];
  $id_rol = $_POST['id_rol'];
  $nuevo_estado = $_POST['modificar_estado'];

  $errores = [];

  // Verifica si se seleccionó un rol
  if ($id_rol == '') {
      $errores[] = "Debe seleccionar un rol.";
  }

  // Verifica si se seleccionó un estado
  if ($nuevo_estado == '') {
      $errores[] = "Debe seleccionar un estado.";
  }

  if (empty($errores)) {
      $sql = "UPDATE usuarios SET id_rol = $id_rol, activo = '$nuevo_estado' WHERE id_usuario = $id_usuario";
      if ($conexion->query($sql)) {
          echo "<div class='alert alert-success'>Rol y estado actualizados correctamente.</div>";
      } else {
          echo "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
      }
  } else {
      foreach ($errores as $error) {
          echo "<div class='alert alert-danger'>$error</div>";
      }
  }
}

// Obtener lista de usuarios con sus roles y estado activo
$sql = "SELECT u.id_usuario, u.nombre, r.nombre_rol, u.activo 
      FROM usuarios u 
      LEFT JOIN roles r ON u.id_rol = r.id_rol";
$resultado = $conexion->query($sql);

// Obtener lista de roles disponibles
$sql_roles = "SELECT * FROM roles";
$roles_result = $conexion->query($sql_roles);

// Preparar datos de usuarios para JavaScript
$usuarios = [];
$resultado->data_seek(0);
while ($row = $resultado->fetch_assoc()) {
  $usuarios[] = $row;
}
// Desactivar usuario (cambiar estado a 'NO')
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario_eliminar'])) {
  $id_usuario = intval($_POST['id_usuario_eliminar']);

  $sql = "UPDATE usuarios SET activo = 'NO' WHERE id_usuario = $id_usuario";
  if ($conexion->query($sql)) {
      echo "<div class='alert alert-success'>Usuario desactivado correctamente.</div>";
  } else {
      echo "<div class='alert alert-danger'>Error al desactivar el usuario: " . $conexion->error . "</div>";
  }
}

// Buscar por nombre de equipo si se ha enviado desde el formulario
$busqueda = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';

// Consultar equipos entregados
$sqlEntregados = "SELECT * FROM estado_entregas WHERE entregado = 'Sí'";
if ($busqueda !== '') {
    $sqlEntregados .= " AND nombre_equipo LIKE '%$busqueda%'";
}
$resultadoEntregados = $conexion->query($sqlEntregados);

// Consultar equipos pendientes
$sqlPendientes = "SELECT * FROM estado_entregas WHERE entregado = 'No'";
if ($busqueda !== '') {
    $sqlPendientes .= " AND nombre_equipo LIKE '%$busqueda%'";
}
$resultadoPendientes = $conexion->query($sqlPendientes);

// Procesar actualización de estado si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_movimiento'])) {
    $id = $_POST['id_movimiento'];
    $nuevoEstado = $conexion->real_escape_string($_POST['nuevo_estado']);
    $observacion = $conexion->real_escape_string($_POST['observacion']);

    $conexion->query("UPDATE estado_entregas SET entregado='Sí', observacion='$observacion', fecha_actualizacion=NOW() WHERE id_movimiento=$id");

    // Actualizar fecha_entrega en tabla movimientos
    $conexion->query("UPDATE movimientos SET fecha_entrega=NOW() WHERE id_movimiento=$id");

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direccion</title>
    <link rel="stylesheet" href="cumple1.css">
    <link rel="stylesheet" href="../volver.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style.css">
   <link rel="stylesheet" href="pu.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
     <script src="https://cdn.tailwindcss.com"></script>
     <style>
        .contenedor {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
        }
        .panel {
            position:relative;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin-top:40px;
        }
        .panel h2 {
            text-align: center;
            color: #333;
        }
        .equipo {
            background: #e0e0e0;
            margin: 8px 0;
            padding: 10px;
            border-radius: 5px;
        }
        form {
            display: inline;
        }
        input[type="text"] {
            padding: 5px;
            margin-bottom: 10px;
        }
        button {
            padding: 6px 12px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        /* From Uiverse.io by Li-Deheng */ 
.search {
  --input-line: #cccccc;
  --input-text-color: #808080;
  --input-text-hover-color: transparent;
  --input-border-color: #808080;
  --input-border-hover-color: #999999;
  --input-bg-color: #333333;
  --search-max-width: 250px;
  --search-min-width: 150px;
  --border-radius: 5px;
  --transition-cubic-bezier: 150ms cubic-bezier(0.4,0,0.2,1);
}

.search-box {
  max-width: var(--search-max-width);
  min-width: var(--search-min-width);
  height: 35px;
  border: 1px solid var(--input-border-color);
  border-radius: var(--border-radius);
  padding: 5px 15px;
  background: var(--input-bg-color);
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
  transition: var(--transition-cubic-bezier);
}

.search-box:hover {
  border-color: var(--input-border-hover-color);
}

/*Section input*/
.search-field {
  position: relative;
  width: 100%;
  height: 100%;
  left: -5px;
  border: 0;
}

.input {
  width: calc(100% - 29px);
  height: 100%;
  border: 0;
  border-color: transparent;
  font-size: 1rem;
  padding-right: 0px;
  color: var(--input-line);
  background: var(--input-bg-color);
  border-right: 2px solid var(--input-border-color);
  outline: none;
}

.input::-webkit-input-placeholder {
  color: var(--input-text-color);
}

.input::-moz-input-placeholder {
  color: var(--input-text-color);
}

.input::-ms-input-placeholder {
  color: var(--input-text-color);
}

.input:focus::-webkit-input-placeholder {
  color: var(--input-text-hover-color);
}

.input:focus::-moz-input-placeholder {
  color: var(--input-text-hover-color);
}

.input:focus::-ms-input-placeholder {
  color: var(--input-text-hover-color);
}

/*Search button*/
.search-box-icon {
  width: 52px;
  height: 35px;
  position: absolute;
  top: -6px;
  right: -21px;
  background: transparent;
  border-bottom-right-radius: var(--border-radius);
  border-top-right-radius: var(--border-radius);
  transition: var(--transition-cubic-bezier);
}

.search-box-icon:hover {
  background: var(--input-border-color);
}

.btn-icon-content {
  width: 52px;
  height: 35px;
  top: -6px;
  right: -21px;
  background: transparent;
  border: none;
  cursor: pointer;
  border-bottom-right-radius: var(--border-radius);
  border-top-right-radius: var(--border-radius);
  transition: var(--transition-cubic-bezier);
  opacity: .4;
}

.btn-icon-content:hover {
  opacity: .8;
}

.search-icon {
  width: 21px;
  height: 21px;
  position: absolute;
  top: 7px;
  right: 15px;
}
.mt-5{
    margin-top:0rem!important;
}
     </style>
</head>
<div class="conta"></div>
<body>

<div class="barra">
  <!-- From Uiverse.io by xopc333 --> 
  <a href="http://localhost/nuevo/contraseña/mapaprincipal/index.php"><button class="button"> 
  <div class="button-box">
    <span class="button-elem">
      <svg viewBox="0 0 46 40" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M46 20.038c0-.7-.3-1.5-.8-2.1l-16-17c-1.1-1-3.2-1.4-4.4-.3-1.2 1.1-1.2 3.3 0 4.4l11.3 11.9H3c-1.7 0-3 1.3-3 3s1.3 3 3 3h33.1l-11.3 11.9c-1 1-1.2 3.3 0 4.4 1.2 1.1 3.3.8 4.4-.3l16-17c.5-.5.8-1.1.8-1.9z"
        ></path>
      </svg>
    </span>
    <span class="button-elem">
      <svg viewBox="0 0 46 40">
        <path
          d="M46 20.038c0-.7-.3-1.5-.8-2.1l-16-17c-1.1-1-3.2-1.4-4.4-.3-1.2 1.1-1.2 3.3 0 4.4l11.3 11.9H3c-1.7 0-3 1.3-3 3s1.3 3 3 3h33.1l-11.3 11.9c-1 1-1.2 3.3 0 4.4 1.2 1.1 3.3.8 4.4-.3l16-17c.5-.5.8-1.1.8-1.9z"
        ></path>
      </svg>
    </span>
  </div>
</button></a>

        <label class="bar" for="check">
    <input type="checkbox" id="check">
    <span class="top"></span>
    <span class="middle"></span>
    <span class="bottom"></span>
    </label>
    <div class="perfil">
        <img src="https://i.postimg.cc/T3ZGBW81/ingenieria-electronica-uch-universidad-560x416.png" alt="Perfil de Usuario">
    </div>
    
        <svg class="calendar" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 3h-1V2a1 1 0 1 0-2 0v1H8V2a1 1 0 1 0-2 0v1H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H5V8h14v13zM9 13.5l1.5 1.5 3.5-3.5-1.5-1.5-2 2-1-1-1.5 1.5 2 2z"/>
        </svg>
   
</div> 
<div class="divtotal" style="width: 100%; height: 100%; display:flex; position: fixed; top:90px;">

  <div class="izquierda" style="width: 40%; height: 100%; display:flex; position:relative;">
  <div class="izquierda1" style="height: 100%; width: 50%; position: relative; display:flex;  justify-content: center;">
  <center>
  <form class="form" method="POST" action="" style="top:5px;">
    <p class="title">Asignar rol </p>
    <p class="message">Seleccione a un usuario para modificar su rol de usuario.</p>
    <div class="flex">
        <h2 style="font-size:20px;">Usuarios</h2>
        <select id="usuario" name="id_usuario" required style="color:white; border-radius:20px; width: 100%; height: 30px; display:flex;   background-color: #464646;">
            <option value="">Seleccione un usuario</option>
            <?php 
            $usuarios = [];
            $resultado->data_seek(0); 
            while ($row = $resultado->fetch_assoc()) {
                $usuarios[] = $row; // Para pasar al JS luego
            ?>
                <option value="<?php echo $row['id_usuario']; ?>">
                    <?php echo $row['nombre']; ?> <?php echo $row['nombre_rol'] ? "( ".$row['nombre_rol']." )" : "(Sin rol)"; ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <label>
        <input id="nombre_usuario" class="input" type="text" placeholder="" readonly>
        <span>Nombre de Usuario</span>
    </label>

    <label>
        <input id="rol_actual" class="input" type="text" placeholder="" readonly>
        <span>Rol Actual</span>
    </label>

    <label>
        <input id="estado_activo" class="input" type="text" placeholder="" readonly>
        <span>Estado actual</span>
    </label>

   <label for="modificar_estado" >Modificar Estado:</label>
            <select name="modificar_estado" id="modificar_estado" required style=" background-color: #464646; border-radius:20px; width: 100%; height: 30px; display:flex;">
                <option value="">Seleccione un estado</option>
                <option value="SI">Activo</option>
                <option value="NO">Inactivo</option>
            </select>

            <div id="modificar-rol" style="display:none;">
                <label for="id_rol">Modificar Rol:</label>
                <select name="id_rol" id="id_rol" required style="color:white; background-color: #464646; border-radius:20px; width: 100%; height: 30px; display:flex;">
                    <option value="">Seleccione un rol</option>
                    <?php while ($rol = $roles_result->fetch_assoc()): ?>
                        <option value="<?php echo $rol['id_rol']; ?>"><?php echo $rol['nombre_rol']; ?></option>
                    <?php endwhile; ?>
                </select>
        
        <button class="submit" type="submit" name="asignar_rol" style="margin-top:30px;">Guardar Cambios</button>
       
    </div>
</form>

</div>
</center>
   
  <div class="izquierda2" style="height: 100%; width: 50%; position: relative; display: flex; overflow-x: hidden; overflow-y: scroll; scrollbar-width: none; -ms-overflow-style: none; ">

  <div class="lista_usus" style="display:flex; flex-direction: column; justify-content: center; align-items: center;">
    <Em>Lista de Usuarios</Em>
    <div class="contenedor-scroll">
    <?php
$resultado->data_seek(0); 
while ($row = $resultado->fetch_assoc()) { ?>
    <div class="card-client">
        <div class="user-picture">
            <img src="https://i.postimg.cc/T3ZGBW81/ingenieria-electronica-uch-universidad-560x416.png" alt="Usuario">
        </div>
        <p class="name-client"><?php echo $row['nombre']; ?><br></p>
      <em style="color:cyan;"><?php echo $row['nombre_rol'] ? $row['nombre_rol'] : 'Sin rol'; ?></em><br>

        
        <!-- From Uiverse.io by Allyhere --> 
<!-- From Uiverse.io by Navarog21 --> 
 <style>
    /* From Uiverse.io by Navarog21 */ 
.button1 {
  width: 5em;
  position: relative;
  height: 2em;
  border: 3px ridge #149CEA;
  outline: none;
  background-color: transparent;
  color: white;
  transition: 1s;
  border-radius: 0.3em;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
}



.button1:hover::before, button:hover::after {
  transform: scale(0)
}

.button1:hover {
  box-shadow: inset 0px 0px 25px #1479EA;
}
/*Button 2 color red*/

.button2 {
  width: 5em;
  position: relative;
  height: 2em;
  border: 3px rgb(234, 27, 20);
  outline: none;
  background-color: transparent;
  color: white;
  transition: 1s;
  border-radius: 0.3em;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
}


.button2:hover::before, button:hover::after {
  transform: scale(0)
}

.button2:hover {
  box-shadow: inset  0px 0px 25px rgb(234, 20, 20);
}

 </style>
<button class="button1">
    Historial
</button>
<form method="POST" action="" style="display:inline;">
            <input type="hidden" name="id_usuario_eliminar" value="<?php echo $row['id_usuario']; ?>">
            <button type="submit" onclick="return confirm('¿Seguro que quieres desactivar este usuario?');" class="button2">Eliminar</button>
        </form><br>
    </div>
<?php } ?>
        
    </div>
</div>

   </div>
</div>
<div class="derecha" style="width: 60%; height: 100%; display: flex; flex-direction: column; gap: 20px;">
  
<div class="derechasuperior" style="postion: relative; display: flex; width: 100%; height:30%;">    
  <div class="mt-5">
    <center><em class="text-center text-white" style="color:white; ">Historial de Movimientos</em></center>
    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>ID Movimiento</th>
                    <th>ID Equipo</th>
                    <th>ID Usuario</th>
                    <th>Cantidad</th>
                    <th>Fecha Salida</th>
                    <th>Fecha Devolución</th>
                    <th>Estado Entrega</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_historial = "SELECT id_movimiento, id_equipo, id_usuario, cantidad, fecha_salida, fecha_entrega, estado_entrega, observacion FROM movimientos ORDER BY id_movimiento DESC";
                $result_historial = $conexion->query($sql_historial);

                if ($result_historial->num_rows > 0) {
                    while ($row = $result_historial->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_movimiento'] . "</td>";
                        echo "<td>" . $row['id_equipo'] . "</td>";
                        echo "<td>" . $row['id_usuario'] . "</td>";
                        echo "<td>" . $row['cantidad'] . "</td>";
                        echo "<td>" . $row['fecha_salida'] . "</td>";
                        echo "<td>" . ($row['fecha_entrega'] ? $row['fecha_entrega'] : '---') . "</td>";
                        echo "<td>" . ($row['estado_entrega'] ? $row['estado_entrega'] : '---') . "</td>";
                        echo "<td>" . ($row['observacion'] ? $row['observacion'] : '---') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No se encontraron movimientos.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
   </div>
</div>

<div class="derechainferior" style="positon: relative; display: flex; width: 100%; height:70%;">
    
<div class="contenedor" style="display: flex; position: relative;  ">
<form method="get" >
<center><em class="text-center text-white" style="color:white; position:absolute; width:100%; ">Historial de Movimientos</em></center>
<!-- From Uiverse.io by Li-Deheng --> 
<div class="search" style="position:absolute;">
  <div class="search-box">
    <div class="search-field">
      <input placeholder="Search..." class="input" type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">
      <div class="search-box-icon" type="subtmit">
        <button class="btn-icon-content">
          <i class="search-icon" >
            <svg xmlns="://www.w3.org/2000/svg" version="1.1" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" fill="#fff"></path></svg>
          </i>
        </button>
      </div>
    </div>
  </div>
</div>
</form>
    <div class="panel" >
        <h2>Equipos Entregados ✅</h2>
        <?php if ($resultadoEntregados->num_rows > 0): ?>
            <?php while($fila = $resultadoEntregados->fetch_assoc()): ?>
                <div class="equipo">
                    <strong>Equipo:</strong> <?= htmlspecialchars($fila['nombre_equipo']) ?><br>
                    <strong>ID Movimiento:</strong> <?= $fila['id_movimiento'] ?><br>
                    <strong>Fecha Entrega:</strong> <?= $fila['fecha_actualizacion'] ?><br>
                    <strong>Observación:</strong> <?= $fila['observacion'] ?: 'Sin observaciones' ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay equipos entregados.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Equipos Pendientes ⏳</h2>
        <?php if ($resultadoPendientes->num_rows > 0): ?>
            <?php while($fila = $resultadoPendientes->fetch_assoc()): ?>
                <div class="equipo">
                    <strong>Equipo:</strong> <?= htmlspecialchars($fila['nombre_equipo']) ?><br>
                    <strong>ID Movimiento:</strong> <?= $fila['id_movimiento'] ?><br>
                    <strong>Última Actualización:</strong> <?= $fila['fecha_actualizacion'] ?><br>
                    <strong>Observación:</strong> <?= $fila['observacion'] ?: 'Sin observaciones' ?><br>

                    <form method="post">
                        <input type="hidden" name="id_movimiento" value="<?= $fila['id_movimiento'] ?>">
                        <label>Estado:</label>
                        <select name="nuevo_estado" required>
                            <option value="Sí">Entregado</option>
                        </select><br>
                        <label>Descripción:</label>
                        <input type="text" name="observacion" placeholder="Ingrese descripción" required><br>
                        <button type="submit">Marcar como entregado</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Todos los equipos han sido entregados 🎉</p>
        <?php endif; ?>
    </div>
</div>

   </div>
</div>
</div>
</body>
<script>
 const usuarios = <?php echo json_encode($usuarios); ?>;

const usuarioSelect = document.getElementById('usuario');
const nombreInput = document.getElementById('nombre_usuario');
const rolActualInput = document.getElementById('rol_actual');
const estadoActivoInput = document.getElementById('estado_activo');
const modificarRolDiv = document.getElementById('modificar-rol');

usuarioSelect.addEventListener('change', function() {
    const userId = this.value;
    if (userId === "") {
        nombreInput.value = "";
        rolActualInput.value = "";
        estadoActivoInput.value = "";
        modificarRolDiv.style.display = "none";
        return;
    }

    const usuarioSeleccionado = usuarios.find(user => user.id_usuario == userId);

    if (usuarioSeleccionado) {
        nombreInput.value = usuarioSeleccionado.nombre;
        rolActualInput.value = usuarioSeleccionado.nombre_rol || "Sin rol";
        estadoActivoInput.value = (usuarioSeleccionado.activo === "SI") ? "Activo" : "Inactivo";
        modificarRolDiv.style.display = "block";
    }
});
// Añadir funcionalidad para los botones de Editar Rol
document.querySelectorAll('.editar-rol-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const userId = this.dataset.id;

        // Simular la selección en el <select>
        usuarioSelect.value = userId;

        // Llenar los campos automáticamente
        const usuarioSeleccionado = usuarios.find(user => user.id_usuario == userId);
        if (usuarioSeleccionado) {
            nombreInput.value = usuarioSeleccionado.nombre;
            rolActualInput.value = usuarioSeleccionado.nombre_rol || "Sin rol";
            modificarRolDiv.style.display = "block";
        }

        // Opcional: hacer scroll al formulario para mayor comodidad
        document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
    });
});
</script>
</html>