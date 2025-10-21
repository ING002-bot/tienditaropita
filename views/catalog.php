<?php $products = fs_load_products(); ?>
<section>
  <h2>Catálogo</h2>
  <p class="muted">Descubre nuestra selección de ropa para hombres.</p>
  <div class="card-grid">
    <?php foreach($products as $p): ?>
      <article class="card">
        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
        <div class="card-body">
          <h3><?php echo htmlspecialchars($p['name']); ?></h3>
          <div class="price">$<?php echo number_format($p['price'], 2); ?></div>
          <div class="controls">
            <button class="btn" data-add-to-cart data-id="<?php echo $p['id']; ?>">Agregar</button>
            <a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/index.php?page=cart">Ver carrito</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
