<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
include 'conexion.php'; 

echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $nombre= trim($_POST['nombre']);

    if (empty($email) || empty($nombre)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Campos vacíos',
                    text: 'Debes ingresar tu correo y nombre de usuario.',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>";
        exit;
    }

    $sql = "SELECT id_usuario FROM usuarios WHERE email = ? AND nombre = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $email, $nombre);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Datos incorrectos',
                    text: 'Los datos ingresados no son correctos.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'http://localhost/nuevo/contrase%C3%B1a/olvidocontra.php';
                });
            });
        </script>";
        exit;
    }

    $codigo = rand(100000, 999999);

    $sql = "INSERT INTO tokens_recuperacion (email, codigo, expiracion) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE)) 
            ON DUPLICATE KEY UPDATE codigo = VALUES(codigo), expiracion = VALUES(expiracion)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $email, $codigo);
    $stmt->execute();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'grijalvauve@gmail.com';
        $mail->Password = 'ndrg qqgw qwwv tfnb'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('MtcTelecomunicaciones@gmail.com', 'Telecomunicaciones');
        $mail->addAddress($email);
        $mail->Subject = 'Codigo de Recuperacion - Telecomunicaciones';

        $logo_url = 'https://i.postimg.cc/cHG4yWw5/LOGO-TRANSPARENTE-TRASNPORTES.png';

        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; text-align: center;'>
                <img src='$logo_url' width='200' alt='Logo de Telecomunicaciones'><br>
                <h2 style='color: #0056b3;'>Código de Recuperación</h2>
                <p>Tu código de recuperación es:</p>
                <h1 style='color: #d9534f;'>$codigo</h1>
                <p>Este código es válido por <strong>5 minutos</strong>.</p>
                <p>Introduce este código en el siguiente formulario para continuar.</p>
                <br>
                <p>Atentamente,</p>
                <p><strong>Equipo de Soporte de Telecomunicaciones</strong></p>
            </div>";

        $mail->send();

        header("Location: verificaciondecontra.php?email=" . urlencode($email));
        exit();
    } catch (Exception $e) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar',
                    text: 'No se pudo enviar el correo. {$mail->ErrorInfo}',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificacion de usuario</title>
    <link rel="shortcut icon" href="telecomunicaciones0.ico" />
    <link rel="stylesheet" href="styles1.css">
</head>
<body>
    <div class="fondo"></div>
    <div class="form">
        <center><h2>Verificar Usuario</h2>

        <form method="POST">
        <input class="usuario" type="text" name="nombre" placeholder="Nombre de Usuario" required> <br>
            <input class="contra" type="email" name="email" placeholder="Correo Electrónico" required> <br>
            <button type="submit">Enviar Código</button>
        </form>
    </div>
    </center>
    <footer class="footer">
        © 2025 Todos los derechos reservados | <a href="#">RNcorp</a> | <a href="#">Términos de uso</a>
    </footer>
</body>
</html>
