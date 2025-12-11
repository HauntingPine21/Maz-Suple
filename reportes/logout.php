<?php
// ============================================================
// RESPONSABLE: Rol 5 (Sesiones)
// REQUERIMIENTO: "Política de Acceso... y logout"
// ============================================================
session_start();
session_destroy();
header("Location: index.php");
exit;
// ATENCIOOOOON , YO NO SOY LA IA, SOY MARKO, PERO COMENTO ESTO PARA DECIR QUE ESTE LOGOUT ES
// EL LOGOUT PATITO, NO EL BUENO, EL BUENO ESTA EN INCLUDES, PERO DEJE ESTE POR SI ACASO 
?>