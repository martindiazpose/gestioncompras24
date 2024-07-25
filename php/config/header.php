<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/auth.php';
verificarSesion(['usuario', 'administrador', 'compras', 'aprobador']); 

$user_role = $_SESSION['perfil'];

?>

<header>
    <link rel="stylesheet" href="/gestioncompras/css/header_styles.css"> 
    <nav id="header" class="navbar navbar-expand-md navbar-dark">
        <a class="navbar-brand" href="/gestioncompras/index.php">
            <img id="header-logo" src="/gestioncompras/img/Etarey.png" alt="Etarey">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#links" aria-controls="links"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="links" class="collapse navbar-collapse justify-content-end row">
            <ul class="navbar-nav">
                <?php if ($user_role == 'administrador' || $user_role == 'usuario'): ?>
                    <li class="nav-item col-2">
                        <a class="nav-link" href="/gestioncompras/pages/crear_solicitud.php">Crear solicitud</a>
                    </li>
                <?php endif; ?>

                <?php if ($user_role == 'administrador' || $user_role == 'compras' || $user_role == 'aprobador'): ?>
                    <li class="nav-item col-2">
                        <a class="nav-link" href="/gestioncompras/pages/mis_solicitudes.php">Mis solicitudes</a>
                    </li>
                <?php endif; ?>

                <?php if ($user_role == 'administrador' || $user_role == 'aprobador'): ?>
                    <li class="nav-item col-3">
                        <a class="nav-link" href="/gestioncompras/pages/solicitudes_para_aprobar.php">Solicitudes para aprobar</a>
                    </li>
                <?php endif; ?>

                <li class="nav-item col-3 dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Perfil
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <?php if ($user_role == 'administrador'): ?>
                            <a class="dropdown-item" href="/gestioncompras/pages/registro.php">Gestión Usuarios</a>
                        <?php endif; ?>
                        <a class="dropdown-item" href="/gestioncompras/pages/reporte_solicitudes.php">Reportes</a>
                        <a class="dropdown-item" href="/gestioncompras/pages/perfil.php">Perfil</a>
                        <a class="dropdown-item" href="/gestioncompras/pages/ver_registro_correos.php">Log Correos</a>
                        <a class="dropdown-item" href="/gestioncompras/php/logout.php">Cerrar sesión</a>

                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>
