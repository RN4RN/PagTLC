<?php
session_start();
session_destroy();
header("Location: http://localhost/nuevo/contrase%C3%B1a/indexlogin.php");
exit();
?>