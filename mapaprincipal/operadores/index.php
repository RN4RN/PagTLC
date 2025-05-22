<?php
// 1. Iniciar sesión ANTES de cualquier output
session_start();
require 'conexion.php'; // Asegúrate que la conexión funciona y $conexion está disponible

// 2. Verificar sesión
if (!isset($_SESSION['nombre'])) {
    header("Location: http://localhost/nuevo/contraseña/indexlogin.php");
    exit();
}

// --- Obtener datos para el perfil del sidebar (USUARIO LOGUEADO) ---
$sidebarUserData = null;
$nombreUsuarioLogueado = $_SESSION['nombre'];
$sqlSidebarUser = "SELECT u.nombre, u.email, u.perfil, r.nombre_rol
                   FROM usuarios u
                   LEFT JOIN roles r ON u.id_rol = r.id_rol
                   WHERE u.nombre = ? LIMIT 1";

$stmtSidebar = $conexion->prepare($sqlSidebarUser);
if ($stmtSidebar) {
    $stmtSidebar->bind_param("s", $nombreUsuarioLogueado);
    $stmtSidebar->execute();
    $resultSidebarUser = $stmtSidebar->get_result();
    if ($resultSidebarUser && $resultSidebarUser->num_rows > 0) {
        $sidebarUserData = $resultSidebarUser->fetch_assoc();
    }
    $stmtSidebar->close();
} else {
    error_log("Error preparando consulta sidebar: " . $conexion->error);
}

// --- Obtener lista de TODOS los usuarios para las tarjetas ---
$sqlAllUsers = "SELECT usuarios.id_usuario, usuarios.nombre, usuarios.dni, usuarios.email, usuarios.telefono,
                       usuarios.perfil, usuarios.fecha_registro, usuarios.activo, usuarios.descripcion,
                       roles.nombre_rol
                FROM usuarios
                LEFT JOIN roles ON usuarios.id_rol = roles.id_rol";

$resultAllUsers = $conexion->query($sqlAllUsers);

if (!$resultAllUsers) {
    error_log("Error en la consulta de usuarios: " . $conexion->error);
    die("Hubo un error al cargar los datos de usuarios. Inténtalo más tarde.");
}

// --- OBTENER TODOS LOS MOVIMIENTOS PARA JS ---
$allMovements = [];
$sqlAllMovements = "SELECT id_movimiento, id_equipo, id_usuario, cantidad, fecha_salida, fecha_entrega, estado_entrega, observacion, nombre_equipo, nombre_usuario 
                    FROM movimientos ORDER BY fecha_salida DESC";
$resultAllMovements = $conexion->query($sqlAllMovements);
if ($resultAllMovements) {
    while ($mov_row = $resultAllMovements->fetch_assoc()) {
        $allMovements[] = $mov_row;
    }
} else {
    error_log("Error al cargar movimientos: " . $conexion->error);
}


// --- Registrar Acceso ---
$seccion = 'gestion_usuarios';
$nombreUsuario = $_SESSION['nombre'];
$fechaActual = date('Y-m-d H:i:s');

$sqlAccess = "INSERT INTO registro_acceso (seccion, nombre_usuario, fecha_ultimo_acceso)
              VALUES (?, ?, ?)
              ON DUPLICATE KEY UPDATE
              nombre_usuario = VALUES(nombre_usuario),
              fecha_ultimo_acceso = VALUES(fecha_ultimo_acceso)";
$stmtAccess = $conexion->prepare($sqlAccess);
if ($stmtAccess) {
    $stmtAccess->bind_param("sss", $seccion, $nombreUsuario, $fechaActual);
    if (!$stmtAccess->execute()) {
        error_log("Error ejecutando registro de acceso: " . $stmtAccess->error);
    }
    $stmtAccess->close();
} else {
    error_log("Error preparando registro de acceso: " . $conexion->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESTACIONES - Gestión de Usuarios</title>
    <link rel="stylesheet" href="estyles3.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .user-activity-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
        .user-activity-section h4 { margin-bottom: 15px; color: #333; }
        .activity-buttons button { margin-right: 10px; margin-bottom: 10px; background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 0.9em; }
        .activity-buttons button:hover { background-color: #0056b3; }
        #userActivityChart { max-width: 100%; margin-top: 20px; }
        .movements-list { list-style: none; padding: 0; max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding:10px; border-radius: 4px; margin-top: 10px; }
        .movements-list li { padding: 8px 0; border-bottom: 1px dashed #f0f0f0; font-size: 0.9em; }
        .movements-list li:last-child { border-bottom: none; }
        .movements-list .mov-equipo { font-weight: bold; }
        .movements-list .mov-details { font-size: 0.85em; color: #555; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <header class="top-bar">
        <button class="hamburger-button" id="hamburger-button" aria-label="Abrir menú lateral" aria-expanded="false">
            <span class="line"></span><span class="line"></span><span class="line"></span>
        </button>
        <div class="search">
            <label for="search-input" class="sr-only">Buscar usuarios</label>
            <input id="search-input" placeholder="Buscar usuarios..." type="search" aria-label="Campo de búsqueda">
            <button type="button" aria-label="Buscar"><i class="fas fa-search"></i></button>
        </div>
    </header>

    <div class="overlay" id="overlay" aria-hidden="true"></div>

    <aside class="sidebar" id="sidebar" aria-label="Menú principal">
        <div class="sidebar-header">
            <h2>Menú</h2>
            <button class="close-sidebar" id="close-sidebar" aria-label="Cerrar menú"><i class="fas fa-times"></i></button>
        </div>
        <div class="sidebar-content">
            <?php if ($sidebarUserData): ?>
            <div class="profile-section">
                <button class="profile-button" id="profile-button"
                        data-nombre="<?= htmlspecialchars($sidebarUserData['nombre']) ?>"
                        data-email="<?= htmlspecialchars($sidebarUserData['email']) ?>"
                        data-rol="<?= htmlspecialchars($sidebarUserData['nombre_rol'] ?? 'N/A') ?>"
                        data-descripcion="Perfil del usuario actual.">
                    <img src="<?= htmlspecialchars($sidebarUserData['perfil'] ?? 'default_avatar.png') ?>"
                         alt="Avatar de <?= htmlspecialchars($sidebarUserData['nombre']) ?>"
                         onerror="this.onerror=null; this.src='default_avatar.png';">
                    <span>Mi Perfil</span>
                </button>
                <div class="profile-description" id="profile-description"></div>
            </div>
            <?php else: ?>
             <div class="profile-section"><p>No se pudo cargar el perfil.</p></div>
            <?php endif; ?>
            <nav class="sidebar-nav" aria-label="Navegación principal">
                 <ul>
                    <li><a href="dashboard.php" class="sidebar-button"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li><a href="calendar.php" class="sidebar-button"><i class="fas fa-calendar-alt"></i><span>Calendario</span></a></li>
                    <li><a href="#" class="sidebar-button active"><i class="fas fa-users"></i><span>Usuarios</span></a></li>
                    <li><a href="settings.php" class="sidebar-button"><i class="fas fa-cog"></i><span>Configuración</span></a></li>
                </ul>
            </nav>
            <div class="btn-container">
                <a class="btn-content logout-button" href="logout.php" aria-label="Cerrar sesión">
                    <span class="btn-title">Cerrar sesión</span>
                    <span class="icon-arrow"><svg width="25px" height="16px" viewBox="0 0 66 43"> <path fill="#FFFFFF" d="M40.15...Z"></path> </svg></span>
                </a>
            </div>
        </div>
    </aside>

    <main class="main-container" id="main-content">
        <section class="left-panel" aria-labelledby="users-heading">
            <h2 id="users-heading" class="sr-only">Lista de usuarios</h2>
            <?php if ($resultAllUsers->num_rows > 0): ?>
                <div class="cards-grid">
                    <?php while ($row = $resultAllUsers->fetch_assoc()): ?>
                        <?php
                            $userId = $row['id_usuario'] ?? 'unknown';
                            $userNombre = $row['nombre'] ?? 'Sin nombre';
                            $userDni = $row['dni'] ?? 'N/A';
                            $userPerfil = $row['perfil'] ?? 'default_avatar.png';
                            $userEstado = $row['activo'] ?? 'Desconocido';
                            $userRol = $row['nombre_rol'] ?? 'N/A';
                            $userTelefono = $row['telefono'] ?? 'N/A';
                            $userFechaRegistro = $row['fecha_registro'] ? date("d/m/Y", strtotime($row['fecha_registro'])) : 'N/A';
                        ?>
                        <article class="card" id="user-<?= htmlspecialchars($userId) ?>">
                            <div class="card-image">
                                <img src="<?= htmlspecialchars($userPerfil) ?>"
                                     alt="Foto de <?= htmlspecialchars($userNombre) ?>"
                                     onerror="this.onerror=null; this.src='default_avatar.png';"
                                     class="user-image">
                                <span class="user-status <?= strtolower($userEstado) === 'si' ? 'active' : 'inactive' ?>">
                                    <?= htmlspecialchars($userEstado === 'SI' ? 'Activo' : 'Inactivo') ?>
                                </span>
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?= htmlspecialchars($userNombre) ?></h3>
                                <div class="card-body">
                                    <p><i class="fas fa-id-card"></i> <strong>DNI:</strong> <?= htmlspecialchars($userDni) ?></p>
                                    <p><i class="fas fa-user-tag"></i> <strong>Rol:</strong> <?= htmlspecialchars($userRol) ?></p>
                                    <p><i class="fas fa-phone"></i> <strong>Teléfono:</strong> <?= htmlspecialchars($userTelefono) ?></p>
                                    <p><i class="fas fa-calendar-day"></i> <strong>Registro:</strong> <?= htmlspecialchars($userFechaRegistro) ?></p>
                                </div>
                            </div>
                            <button class="card-button" onclick='mostrarDetallesUsuario(<?= json_encode($userId) ?>)'
                                    aria-label="Ver detalles de <?= htmlspecialchars($userNombre) ?>"
                                    data-userid="<?= htmlspecialchars($userId) ?>">
                                Ver Detalles
                            </button>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results placeholder-content">
                    <i class="fas fa-users-slash"></i><p>No hay usuarios registrados.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="right-panel" aria-labelledby="user-details-heading">
            <h2 id="user-details-heading" class="sr-only">Detalles del Usuario</h2>
            <div id="user-details-placeholder" class="placeholder-content">
                <i class="fas fa-user-circle"></i><p>Selecciona un usuario para ver sus detalles</p>
            </div>
            <div id="user-details-content" class="user-details" style="display: none;">
                <div class="user-header">
                    <img id="detail-user-image" src="default_avatar.png" alt="Foto del usuario"
                         onerror="this.onerror=null; this.src='default_avatar.png';">
                    <div>
                        <h3 id="detail-user-name"></h3>
                        <span id="detail-user-role" class="user-role"></span>
                        <span id="detail-user-status" class="user-status"></span>
                    </div>
                </div>
                <div class="user-info">
                    <div class="info-group">
                        <h4><i class="fas fa-info-circle"></i> Información Básica</h4>
                        <p><i class="fas fa-id-card"></i> <strong>DNI:</strong> <span id="detail-user-dni"></span></p>
                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <span id="detail-user-email"></span></p>
                        <p><i class="fas fa-phone"></i> <strong>Teléfono:</strong> <span id="detail-user-phone"></span></p>
                        <p><i class="fas fa-calendar-plus"></i> <strong>Fecha Registro:</strong> <span id="detail-user-register"></span></p>
                    </div>
                    <div class="info-group">
                        <h4><i class="fas fa-file-alt"></i> Descripción</h4>
                        <p id="detail-user-description"></p>
                    </div>
                </div>
                <div class="user-actions">
                    <button class="button edit-button" id="edit-user-btn"><i class="fas fa-edit"></i> Editar</button>
                    <button class="button delete-button" id="delete-user-btn"><i class="fas fa-trash-alt"></i> Eliminar</button>
                </div>

                <div class="user-activity-section">
                    <h4><i class="fas fa-chart-line"></i> Actividad del Usuario</h4>
                    <div class="activity-buttons">
                        <button id="btn-view-movements" class="button"><i class="fas fa-exchange-alt"></i> Ver Movimientos</button>
                        <!-- <button id="btn-view-accesslog" class="button"><i class="fas fa-history"></i> Ver Log de Acceso</button> -->
                    </div>
                    <div id="movements-container" style="display:none;">
                        <h5>Movimientos Recientes:</h5>
                        <ul id="movements-list-ul" class="movements-list"></ul>
                    </div>
                    <canvas id="userActivityChart" style="display:none;"></canvas>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>© <?= date('Y') ?> ESTACIONES - Todos los derechos reservados</p>
            <div class="footer-links">
                <a href="privacy.php">Política de Privacidad</a> | <a href="terms.php">Términos de Uso</a> | <a href="contact.php">Contacto</a>
            </div>
        </div>
    </footer>
</div>

<script>
const usersData = {
    <?php
    if ($resultAllUsers->num_rows > 0) {
        $resultAllUsers->data_seek(0);
        $first = true;
        while ($row = $resultAllUsers->fetch_assoc()) {
            if (!$first) echo ',';
            $first = false;
            $userData = [
                'id_usuario' => $row['id_usuario'] ?? null,
                'nombre' => $row['nombre'] ?? 'N/A',
                'dni' => $row['dni'] ?? 'N/A',
                'email' => $row['email'] ?? 'N/A',
                'telefono' => $row['telefono'] ?? 'N/A',
                'perfil' => $row['perfil'] ?? 'default_avatar.png',
                'rol' => $row['nombre_rol'] ?? 'N/A',
                'estado' => $row['activo'] ?? 'Desconocido',
                'estado_display' => ($row['activo'] ?? '') === 'SI' ? 'Activo' : 'Inactivo',
                'fecha_registro' => $row['fecha_registro'] ? date("d/m/Y", strtotime($row['fecha_registro'])) : 'N/A',
                'descripcion' => $row['descripcion'] ?? 'No hay descripción disponible.'
            ];
            echo json_encode($userData['id_usuario']) . ': ' . json_encode($userData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    ?>
};

// Pasamos todos los movimientos desde PHP a JS
const allMovementsData = <?= json_encode($allMovements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];

let activityChartInstance = null;

function displayUserMovements(userId) {
    const movementsList = document.getElementById('movements-list-ul');
    const movementsContainer = document.getElementById('movements-container');
    movementsList.innerHTML = ''; // Limpiar lista anterior

    // Filtrar movimientos para el usuario seleccionado
    const userMovements = allMovementsData.filter(mov => mov.id_usuario == userId); // Usar == para comparación flexible (string vs number)

    if (userMovements && userMovements.length > 0) {
        userMovements.forEach(mov => {
            const listItem = document.createElement('li');
            const fechaSalida = new Date(mov.fecha_salida).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            let tipoMovimiento = mov.estado_entrega || 'Salida'; // 'Salida' si estado_entrega es null o vacío

            listItem.innerHTML = `
                <span class="mov-equipo">${mov.nombre_equipo || 'Equipo Desconocido'} (Cant: ${mov.cantidad})</span><br>
                <span class="mov-details">Fecha: ${fechaSalida} - Tipo: ${tipoMovimiento}</span><br>
                <span class="mov-details">Obs: ${mov.observacion || 'Ninguna'}</span>
            `;
            movementsList.appendChild(listItem);
        });
        movementsContainer.style.display = 'block';
    } else {
        movementsList.innerHTML = '<li>No hay movimientos registrados para este usuario.</li>';
        movementsContainer.style.display = 'block';
    }
    return userMovements; // Devolver para el gráfico
}

function renderUserActivityChart(userMovements, userName) {
    const chartCanvas = document.getElementById('userActivityChart');
    const ctx = chartCanvas.getContext('2d');

    if (activityChartInstance) {
        activityChartInstance.destroy();
    }

    if (!userMovements || userMovements.length === 0) {
        chartCanvas.style.display = 'none'; // Ocultar canvas si no hay datos
        // Opcional: mostrar un mensaje
        // const chartContainer = chartCanvas.parentNode;
        // let noDataMsg = chartContainer.querySelector('.no-chart-data');
        // if (!noDataMsg) {
        //     noDataMsg = document.createElement('p');
        //     noDataMsg.className = 'no-chart-data';
        //     noDataMsg.textContent = 'No hay datos de actividad para graficar.';
        //     chartContainer.insertBefore(noDataMsg, chartCanvas);
        // }
        // noDataMsg.style.display = 'block';
        return;
    }
    // else {
    //     const noDataMsg = chartCanvas.parentNode.querySelector('.no-chart-data');
    //     if (noDataMsg) noDataMsg.style.display = 'none';
    // }


    chartCanvas.style.display = 'block';

    // Agrupar movimientos por nombre_equipo y contar
    const equipmentCounts = userMovements.reduce((acc, mov) => {
        const equipmentName = mov.nombre_equipo || 'Desconocido';
        acc[equipmentName] = (acc[equipmentName] || 0) + parseInt(mov.cantidad, 10);
        return acc;
    }, {});

    const labels = Object.keys(equipmentCounts);
    const dataValues = Object.values(equipmentCounts);

    const data = {
        labels: labels,
        datasets: [{
            label: `Equipos Movidos por ${userName}`,
            data: dataValues,
            backgroundColor: [ // Colores variados para gráfico de barras o pie
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)',
                'rgba(75, 192, 192, 0.5)',
                'rgba(153, 102, 255, 0.5)',
                'rgba(255, 159, 64, 0.5)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };

    activityChartInstance = new Chart(ctx, {
        type: 'bar', // Cambia a 'pie' o 'doughnut' si prefieres
        data: data,
        options: {
            responsive: true,
            indexAxis: 'y', // Para barras horizontales si hay muchos equipos
            scales: {
                x: { // 'x' para 'bar', sería diferente para 'line'
                    beginAtZero: true,
                    title: { display: true, text: 'Cantidad Movida' }
                },
                y: {
                     title: { display: true, text: 'Equipo' }
                }
            },
            plugins: {
                legend: { display: false }, // Puede ser redundante si el título es claro
                title: { display: true, text: `Resumen de Movimientos de Equipos para ${userName}` }
            }
        }
    });
}


function mostrarDetallesUsuario(userId) {
    const user = usersData[userId];
    const placeholder = document.getElementById('user-details-placeholder');
    const content = document.getElementById('user-details-content');
    const movementsContainer = document.getElementById('movements-container');
    const chartCanvas = document.getElementById('userActivityChart');

    if (!user) {
        console.error("Usuario no encontrado en usersData para ID:", userId);
        content.style.display = 'none';
        placeholder.style.display = 'flex';
        placeholder.innerHTML = `<i class="fas fa-exclamation-triangle"></i><p>Error al cargar datos del usuario.</p>`;
        if (activityChartInstance) activityChartInstance.destroy();
        movementsContainer.style.display = 'none';
        chartCanvas.style.display = 'none';
        return;
    }

    placeholder.style.display = 'none';
    content.style.display = 'block';
    movementsContainer.style.display = 'none'; // Ocultar por defecto
    chartCanvas.style.display = 'none'; // Ocultar por defecto

    document.getElementById('detail-user-name').textContent = user.nombre;
    document.getElementById('detail-user-role').textContent = user.rol;
    document.getElementById('detail-user-status').textContent = user.estado_display;
    document.getElementById('detail-user-status').className = `user-status ${user.estado && user.estado.toLowerCase() === 'si' ? 'active' : 'inactive'}`;
    document.getElementById('detail-user-dni').textContent = user.dni;
    document.getElementById('detail-user-email').textContent = user.email;
    document.getElementById('detail-user-phone').textContent = user.telefono;
    document.getElementById('detail-user-register').textContent = user.fecha_registro;
    document.getElementById('detail-user-description').textContent = user.descripcion;

    const userImg = document.getElementById('detail-user-image');
    userImg.src = user.perfil || 'default_avatar.png';
    userImg.alt = `Foto de ${user.nombre}`;
    userImg.onerror = function() { this.onerror=null; this.src='../uploads/perfiles/'; };

    document.getElementById('edit-user-btn').onclick = () => {
        window.location.href = `edit-user.php?id=${user.id_usuario}`;
    };
    document.getElementById('delete-user-btn').onclick = () => {
        if (confirm(`¿Estás seguro de que deseas eliminar a ${user.nombre}?`)) {
            console.log(`Solicitando eliminar usuario ID: ${user.id_usuario}`);
        }
    };

    // Botón de movimientos
    document.getElementById('btn-view-movements').onclick = () => {
        const userMovements = displayUserMovements(user.id_usuario); // Muestra la lista
        renderUserActivityChart(userMovements, user.nombre); // Actualiza el gráfico con esos movimientos
    };
    
    // Limpiar gráfico y lista si no se ha hecho clic en "Ver Movimientos"
    if (activityChartInstance) activityChartInstance.destroy();
    document.getElementById('movements-list-ul').innerHTML = '';


    document.querySelectorAll('.card').forEach(card => card.classList.remove('selected'));
    const selectedCard = document.getElementById(`user-${userId}`);
    if (selectedCard) selectedCard.classList.add('selected');

    if (window.innerWidth < 992) {
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerButton = document.getElementById('hamburger-button');
    const closeSidebar = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    function toggleSidebar() {
        const isOpen = sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        if (hamburgerButton) hamburgerButton.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
    if(hamburgerButton && sidebar && overlay && closeSidebar) {
        hamburgerButton.addEventListener('click', toggleSidebar);
        closeSidebar.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }

    const profileButton = document.getElementById('profile-button');
    const profileDescription = document.getElementById('profile-description');
    if (profileButton && profileDescription) {
        profileButton.addEventListener('click', (event) => {
            event.stopPropagation();
            if (profileDescription.style.display === 'block') {
                profileDescription.style.display = 'none';
            } else {
                profileDescription.innerHTML = `<p><strong>Nombre:</strong> ${profileButton.dataset.nombre || 'N/A'}</p><p><strong>Email:</strong> ${profileButton.dataset.email || 'N/A'}</p><p><strong>Rol:</strong> ${profileButton.dataset.rol || 'N/A'}</p><hr><p>${profileButton.dataset.descripcion || 'N/A'}</p>`;
                profileDescription.style.display = 'block';
            }
        });
    }

    const searchInput = document.getElementById('search-input');
    const cardsGrid = document.querySelector('.cards-grid');
    if (searchInput && cardsGrid) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            const cards = cardsGrid.querySelectorAll('.card');
            let found = false;
            cards.forEach(card => {
                const userNameElement = card.querySelector('.card-title');
                const userDniElement = card.querySelector('.card-body p:nth-of-type(1)');
                const userName = userNameElement ? userNameElement.textContent.toLowerCase() : '';
                const userDniText = userDniElement ? userDniElement.textContent.toLowerCase() : ''; // Contiene "DNI: ..."
                const isMatch = userName.includes(searchTerm) || userDniText.includes(searchTerm);
                card.style.display = isMatch ? '' : 'none';
                if (isMatch) found = true;
            });
             let noResultsMsg = document.getElementById('no-search-results');
             if (!found && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div'); // Cambiado a div para mejor estructura
                    noResultsMsg.id = 'no-search-results';
                    noResultsMsg.className = 'no-results placeholder-content';
                    noResultsMsg.innerHTML = '<i class="fas fa-search-minus"></i><p>No se encontraron usuarios.</p>';
                    // Asegurarse que el padre de cardsGrid existe
                    if (cardsGrid.parentNode) {
                        cardsGrid.parentNode.insertBefore(noResultsMsg, cardsGrid.nextSibling);
                    } else {
                         console.warn("El contenedor de tarjetas no tiene un nodo padre para insertar el mensaje de 'no resultados'.")
                    }
                }
                noResultsMsg.style.display = 'flex'; // Usar flex para centrar si placeholder-content lo usa
             } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
             }
        });
    }
});

document.addEventListener('error', (e) => {
    if (e.target.tagName === 'IMG' && (e.target.classList.contains('user-image') || e.target.id === 'detail-user-image' || (e.target.parentNode && e.target.parentNode.classList.contains('profile-section')) )) {
      
        e.target.onerror = null;
    }
}, true);
</script>

<?php
if (isset($conexion) && $conexion instanceof mysqli) {
    $conexion->close();
}
?>
</body>
</html>