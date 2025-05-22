<?php require 'conexion.php'; ?>  
<?php  
$sql = "SELECT tipo_cambio, detalle, realizado_por, fecha
        FROM historial_cambios";  
$result = $conexion->query($sql);  
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<div class="contenedor_equipos">  
        <div class="antenas">  
            <?php if ($result->num_rows > 0): ?>  
                <?php while ($row = $result->fetch_assoc()): ?>  
                    <div class="card">    
                        <div class="card-body">  
                            <p><strong>Tipo de cambio:</strong> <?= $row['tipo_cambio'] ?><br>  
                            <strong>Detalle</strong> <?= $row['detalle'] ?><br>  
                            <strong>Realizado por</strong> <?= $row['realizado_por'] ?><br>  
                            <strong>Fecha</strong> <?= $row['fecha'] ?><br>  
                        </div>  
                    </div>  
                <?php endwhile; ?>  
            <?php else: ?>  
                <p>No hay registro de cambios.</p>  
            <?php endif; ?>  
        </div>  
    </div>  
</body>
</html>