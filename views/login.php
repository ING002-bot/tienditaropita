<?php require_once __DIR__ . '/../includes/config.php'; require_once __DIR__ . '/../includes/auth.php'; $u = fs_current_user(); ?>
<section>
  <h2>Iniciar sesión</h2>
  <?php if($u): ?>
    <p>Ya has iniciado sesión como <strong><?php echo htmlspecialchars($u['name']); ?></strong> (<?php echo htmlspecialchars($u['role_name']); ?>).</p>
    <form method="post" action="<?php echo FS_BASE_URL; ?>/api/auth.php">
      <input type="hidden" name="action" value="logout">
      <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">
      <button class="btn" type="submit">Cerrar sesión</button>
    </form>
  <?php else: ?>
    <form class="form" method="post" action="<?php echo FS_BASE_URL; ?>/api/auth.php">
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">
      <input type="email" name="email" placeholder="Correo" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <button class="btn" type="submit">Entrar</button>
    </form>
    <div style="margin:.8rem 0;color:var(--muted)">o</div>
    <a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/api/google_start.php">Continuar con Google</a>
  <?php endif; ?>
</section>
