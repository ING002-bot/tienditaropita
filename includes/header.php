<?php require_once __DIR__ . '/config.php'; require_once __DIR__ . '/auth.php'; $__fs_u = fs_current_user(); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($title ?? FS_NAME); ?></title>
  <meta name="description" content="Moda masculina con estilo: Ferre Style. Ropa para hombres moderna y elegante.">
  <link rel="icon" href="data:,">
  <link rel="stylesheet" href="<?php echo FS_BASE_URL; ?>/assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="<?php echo FS_BASE_URL; ?>/index.php?page=home">Ferre <span>Style</span></a>
      <nav class="nav" id="nav">
        <a href="<?php echo FS_BASE_URL; ?>/index.php?page=home">Inicio</a>
        <a href="<?php echo FS_BASE_URL; ?>/index.php?page=catalog">Catálogo</a>
        <a href="<?php echo FS_BASE_URL; ?>/index.php?page=profile">Perfil</a>
        <?php if($__fs_u): ?>
          <a href="<?php echo FS_BASE_URL; ?>/index.php?page=orders_history">Mis pedidos</a>
        <?php endif; ?>
        <a href="<?php echo FS_BASE_URL; ?>/index.php?page=contact">Contacto</a>
        <a class="cart-link" href="<?php echo FS_BASE_URL; ?>/index.php?page=cart" aria-label="Carrito">
          <span>Carrito</span>
          <span class="badge" id="cart-count">0</span>
        </a>
        <?php if($__fs_u): ?>
          <span style="color:var(--muted)">Hola, <strong><?php echo htmlspecialchars($__fs_u['name']); ?></strong> (<?php echo htmlspecialchars($__fs_u['role_name']); ?>)</span>
          <?php if($__fs_u['role_name']==='admin' || $__fs_u['role_name']==='empleado'): ?>
            <a href="<?php echo FS_BASE_URL; ?>/index.php?page=staff_chat">Chat interno</a>
            <?php if($__fs_u['role_name']==='admin'): ?>
              <a href="<?php echo FS_BASE_URL; ?>/index.php?page=admin_dashboard">Admin</a>
            <?php endif; ?>
          <?php endif; ?>
          <form method="post" action="<?php echo FS_BASE_URL; ?>/api/auth.php" style="display:inline">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">
            <button class="btn secondary" type="submit">Salir</button>
          </form>
        <?php else: ?>
          <a href="<?php echo FS_BASE_URL; ?>/index.php?page=login">Login</a>
          <a href="<?php echo FS_BASE_URL; ?>/index.php?page=register">Registro</a>
        <?php endif; ?>
      </nav>
      <button class="nav-toggle" id="nav-toggle" aria-label="Menú">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <main class="site-main container">
