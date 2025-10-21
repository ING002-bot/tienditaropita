<?php require_once __DIR__ . '/../includes/config.php'; require_once __DIR__ . '/../includes/auth.php'; $u = fs_current_user(); $isAdmin = $u && $u['role_name']==='admin'; ?>
<section>
  <h2>Crear cuenta</h2>
  <form class="form" method="post" action="<?php echo FS_BASE_URL; ?>/api/auth.php">
    <input type="hidden" name="action" value="register">
    <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">
    <input type="text" name="name" placeholder="Nombre" required>
    <input type="email" name="email" placeholder="Correo" required>
    <input type="password" name="password" placeholder="Contraseña (mín. 8)" minlength="8" required>
    <?php if($isAdmin): ?>
      <select name="role" class="input">
        <option value="cliente">Cliente</option>
        <option value="empleado">Empleado</option>
        <option value="admin">Admin</option>
      </select>
    <?php else: ?>
      <input type="hidden" name="role" value="cliente">
    <?php endif; ?>
    <button class="btn" type="submit">Registrarme</button>
  </form>
  <div style="margin:.8rem 0;color:var(--muted)">o</div>
  <a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/api/google_start.php">Continuar con Google</a>
</section>
