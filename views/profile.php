<?php require_once __DIR__ . '/../includes/config.php'; require_once __DIR__ . '/../includes/auth.php'; $u = fs_current_user(); ?>
<section>
  <h2>Perfil</h2>
  <?php if(!$u): ?>
    <p class="muted">Accede para ver y gestionar tu perfil.</p>
    <a class="btn" href="<?php echo FS_BASE_URL; ?>/index.php?page=login">Iniciar sesión</a>
  <?php else: ?>
    <form class="form" method="post" action="<?php echo FS_BASE_URL; ?>/api/profile.php">
      <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">
      <label>Nombre</label>
      <input class="input" type="text" name="name" value="<?php echo htmlspecialchars($u['name'] ?? ''); ?>" required>
      <label>Correo</label>
      <input class="input" type="email" name="email" value="<?php echo htmlspecialchars($u['email'] ?? ''); ?>" required>
      <label>Rol</label>
      <input class="input" type="text" value="<?php echo htmlspecialchars($u['role_name'] ?? ''); ?>" disabled>
      <button class="btn" type="submit">Guardar cambios</button>
    </form>
    <p class="muted" style="margin-top:.5rem">La contraseña es privada y no se muestra aquí.</p>
  <?php endif; ?>
</section>
