<?php
// ... (resto de tu código PHP al inicio, como session_start, include, funciones, etc.) ...

// --- VARIABLES Y LÓGICA PARA FILTROS DEL HISTORIAL DE MOVIMIENTOS ---
$filtro_fecha_inicio = isset($_GET['filtro_fecha_inicio']) ? $_GET['filtro_fecha_inicio'] : '';
$filtro_fecha_fin = isset($_GET['filtro_fecha_fin']) ? $_GET['filtro_fecha_fin'] : '';
$filtro_usuario = isset($_GET['filtro_usuario']) ? $conexion->real_escape_string(trim($_GET['filtro_usuario'])) : '';
$filtro_equipo = isset($_GET['filtro_equipo']) ? $conexion->real_escape_string(trim($_GET['filtro_equipo'])) : '';

$where_clauses_historial = [];
$bind_params_historial = [];
$bind_types_historial = '';

if (!empty($filtro_fecha_inicio)) {
    $where_clauses_historial[] = "m.fecha_salida >= ?";
    $bind_params_historial[] = $filtro_fecha_inicio . " 00:00:00";
    $bind_types_historial .= 's';
}
if (!empty($filtro_fecha_fin)) {
    $where_clauses_historial[] = "m.fecha_salida <= ?";
    $bind_params_historial[] = $filtro_fecha_fin . " 23:59:59";
    $bind_types_historial .= 's';
}
if (!empty($filtro_usuario)) {
    $where_clauses_historial[] = "m.nombre_usuario LIKE ?";
    $bind_params_historial[] = "%" . $filtro_usuario . "%";
    $bind_types_historial .= 's';
}
if (!empty($filtro_equipo)) {
    $where_clauses_historial[] = "m.nombre_equipo LIKE ?";
    $bind_params_historial[] = "%" . $filtro_equipo . "%";
    $bind_types_historial .= 's';
}

$where_sql_historial = "";
if (!empty($where_clauses_historial)) {
    $where_sql_historial = " WHERE " . implode(" AND ", $where_clauses_historial);
}

// Historial de Movimientos (Paginado con Filtros)
$items_per_page_historial = 10;
$page_historial = isset($_GET['page_historial']) ? (int)$_GET['page_historial'] : 1;
$offset_historial = ($page_historial - 1) * $items_per_page_historial;

// Construir query string para paginación con filtros
$query_string_filtros = "&filtro_fecha_inicio=" . urlencode($filtro_fecha_inicio) .
                        "&filtro_fecha_fin=" . urlencode($filtro_fecha_fin) .
                        "&filtro_usuario=" . urlencode($filtro_usuario) .
                        "&filtro_equipo=" . urlencode($filtro_equipo);


$sql_total_historial = "SELECT COUNT(*) as total FROM movimientos m" . $where_sql_historial;
$stmt_total_historial = $conexion->prepare($sql_total_historial);
if (!empty($bind_params_historial)) {
    $stmt_total_historial->bind_param($bind_types_historial, ...$bind_params_historial);
}
$stmt_total_historial->execute();
$total_historial_res = $stmt_total_historial->get_result();
$total_historial_items = 0;
if ($total_historial_res) {
    $total_historial_items = $total_historial_res->fetch_assoc()['total'];
}
$stmt_total_historial->close();
$total_historial_pages = ceil($total_historial_items / $items_per_page_historial);

$sql_historial = "SELECT m.id_movimiento, m.nombre_equipo, m.nombre_usuario, m.cantidad, m.fecha_salida, m.fecha_entrega, m.estado_entrega, m.observacion 
                  FROM movimientos m" . $where_sql_historial .
                 " ORDER BY m.id_movimiento DESC
                  LIMIT ? OFFSET ?";

$stmt_historial = $conexion->prepare($sql_historial);
$current_bind_types = $bind_types_historial . 'ii'; // 'i' para LIMIT, 'i' para OFFSET
$current_bind_params = array_merge($bind_params_historial, [$items_per_page_historial, $offset_historial]);

// Necesitamos pasar los parámetros por referencia para bind_param
$ref_params = [];
foreach($current_bind_params as $key => $value) {
    $ref_params[$key] = &$current_bind_params[$key];
}

$stmt_historial->bind_param($current_bind_types, ...$ref_params);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();

// ... (resto de tu código PHP como la definición de $current_section, etc.) ...
?>

<?php // La parte HTML/PHP de la sección de historial quedaría así: ?>

<?php if ($current_section == 'movement_history'): ?>
    <h2 class="mb-4 text-light"><i class="fas fa-history me-2"></i>Historial de Movimientos</h2>
    
    <div class="card card-custom mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="section" value="movement_history">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filtro_fecha_inicio" class="form-label form-label-sm">Fecha Desde:</label>
                        <input type="date" class="form-control form-control-sm form-control-dark" id="filtro_fecha_inicio" name="filtro_fecha_inicio" value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro_fecha_fin" class="form-label form-label-sm">Fecha Hasta:</label>
                        <input type="date" class="form-control form-control-sm form-control-dark" id="filtro_fecha_fin" name="filtro_fecha_fin" value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_usuario" class="form-label form-label-sm">Usuario:</label>
                        <input type="text" class="form-control form-control-sm form-control-dark" id="filtro_usuario" name="filtro_usuario" placeholder="Nombre de usuario" value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="filtro_equipo" class="form-label form-label-sm">Equipo:</label>
                        <input type="text" class="form-control form-control-sm form-control-dark" id="filtro_equipo" name="filtro_equipo" placeholder="Nombre de equipo" value="<?php echo htmlspecialchars($filtro_equipo); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-cyan w-100 mb-md-0 mb-2"><i class="fas fa-search me-1"></i>Filtrar</button>
                        <a href="?section=movement_history" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID Mov. <a href="?section=movement_history<?php echo $query_string_filtros; ?>&sort=id_movimiento&dir=asc"><i class="fas fa-sort-up"></i></a> <a href="?section=movement_history<?php echo $query_string_filtros; ?>&sort=id_movimiento&dir=desc"><i class="fas fa-sort-down"></i></a></th>
                            <th>Equipo</th>
                            <th>Usuario</th>
                            <th>Cant.</th>
                            <th>Fecha Salida</th>
                            <th>Fecha Entrega</th>
                            <th>Estado Físico (Salida)</th>
                            <th style="min-width: 200px;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_historial && $result_historial->num_rows > 0): ?>
                            <?php while ($row = $result_historial->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id_movimiento']; ?></td>
                                <td class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($row['nombre_equipo']); ?>"><?php echo htmlspecialchars($row['nombre_equipo']); ?></td>
                                <td class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($row['nombre_usuario']); ?>"><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
                                <td class="text-center"><?php echo $row['cantidad']; ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_salida']))); ?></td>
                                <td><?php echo $row['fecha_entrega'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_entrega']))) : '<span class="text-muted fst-italic">---</span>'; ?></td>
                                <td>
                                    <?php 
                                    $estado_fisico = htmlspecialchars($row['estado_entrega'] ?? 'N/A');
                                    $badge_class = 'bg-secondary';
                                    if ($estado_fisico == 'Bueno') $badge_class = 'bg-success';
                                    if ($estado_fisico == 'Malo') $badge_class = 'bg-danger';
                                    if ($estado_fisico == 'Incompleto') $badge_class = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $estado_fisico; ?></span>
                                </td>
                                <td class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($row['observacion'] ?? '---'); ?>">
                                    <?php echo htmlspecialchars($row['observacion'] ?? '---'); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4">
                                <i class="fas fa-info-circle fa-2x text-muted mb-2"></i><br>
                                No se encontraron movimientos con los filtros aplicados.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_historial_pages > 1): ?>
            <nav aria-label="Paginación historial" class="mt-4">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php if ($page_historial > 1): ?>
                        <li class="page-item">
                            <a class="page-link text-cyan bg-transparent border-secondary" href="?section=movement_history&page_historial=<?php echo $page_historial - 1; ?><?php echo $query_string_filtros; ?>" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php 
                    // Lógica para mostrar un rango de páginas y no todas si son muchas
                    $rango_paginas = 2; // Cuántas páginas mostrar antes y después de la actual
                    $inicio_rango = max(1, $page_historial - $rango_paginas);
                    $fin_rango = min($total_historial_pages, $page_historial + $rango_paginas);

                    if ($inicio_rango > 1) {
                        echo '<li class="page-item"><a class="page-link text-cyan bg-transparent border-secondary" href="?section=movement_history&page_historial=1' . $query_string_filtros . '">1</a></li>';
                        if ($inicio_rango > 2) {
                            echo '<li class="page-item disabled"><span class="page-link text-cyan bg-transparent border-secondary">...</span></li>';
                        }
                    }

                    for ($i = $inicio_rango; $i <= $fin_rango; $i++): ?>
                    <li class="page-item <?php echo ($page_historial == $i) ? 'active' : ''; ?>">
                        <a class="page-link <?php echo ($page_historial == $i) ? 'bg-cyan border-cyan text-dark' : 'text-cyan bg-transparent border-secondary'; ?>" 
                           href="?section=movement_history&page_historial=<?php echo $i; ?><?php echo $query_string_filtros; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php
                    if ($fin_rango < $total_historial_pages) {
                        if ($fin_rango < $total_historial_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link text-cyan bg-transparent border-secondary">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link text-cyan bg-transparent border-secondary" href="?section=movement_history&page_historial=' . $total_historial_pages . $query_string_filtros . '">' . $total_historial_pages . '</a></li>';
                    }
                    ?>

                    <?php if ($page_historial < $total_historial_pages): ?>
                        <li class="page-item">
                            <a class="page-link text-cyan bg-transparent border-secondary" href="?section=movement_history&page_historial=<?php echo $page_historial + 1; ?><?php echo $query_string_filtros; ?>" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
             <p class="text-center text-muted mt-3">Mostrando <?php echo $result_historial ? $result_historial->num_rows : 0; ?> de <?php echo $total_historial_items; ?> registros.</p>
        </div>
    </div>
<?php endif; ?>