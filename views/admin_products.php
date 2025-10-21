<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/products.php';
fs_require_role(['admin']);
$csrf = fs_csrf_token();
?>
<section>
  <h2>Productos (Admin)</h2>
  <div class="card" style="padding:1rem;margin-bottom:1rem">
    <h3>Nuevo/Editar producto</h3>
    <div class="form" id="prod-form">
      <input type="hidden" id="p-id" value="">
      <input class="input" id="p-sku" placeholder="SKU">
      <input class="input" id="p-name" placeholder="Nombre">
      <input class="input" id="p-price" placeholder="Precio" type="number" step="0.01">
      <input class="input" id="p-stock" placeholder="Stock" type="number">
      <input class="input" id="p-image" placeholder="URL de imagen">
      <div class="controls">
        <input type="file" id="p-file" accept="image/*">
        <button class="btn secondary" type="button" id="btn-upload">Subir archivo</button>
      </div>
      <label><input type="checkbox" id="p-active" checked> Activo</label>
      <div class="controls"><button class="btn" id="save-prod">Guardar</button></div>
    </div>
  </div>

  <div class="controls" style="margin-bottom:.5rem">
    <input class="input" id="search" placeholder="Buscar por nombre o SKU">
    <button class="btn" id="btn-search">Buscar</button>
    <button class="btn secondary" id="import-json">Importar JSON inicial</button>
  </div>
  <div id="grid" class="card-grid"></div>
</section>
<script>
(function(){
  const csrf = <?php echo json_encode($csrf); ?>;
  const grid = document.getElementById('grid');
  const search = document.getElementById('search');
  const btnSearch = document.getElementById('btn-search');
  const saveBtn = document.getElementById('save-prod');
  const fileInput = document.getElementById('p-file');
  const btnUpload = document.getElementById('btn-upload');
  const form = {
    id: document.getElementById('p-id'), sku: document.getElementById('p-sku'),
    name: document.getElementById('p-name'), price: document.getElementById('p-price'),
    stock: document.getElementById('p-stock'), image: document.getElementById('p-image'),
    active: document.getElementById('p-active')
  };

  async function searchProducts(){
    const url = new URL('<?php echo FS_BASE_URL; ?>/api/products.php', location.origin);
    url.searchParams.set('action','search');
    url.searchParams.set('q', search.value||'');
    const res = await fetch(url);
    const data = await res.json();
    render(data.items||[]);
  }

  function render(items){
    grid.innerHTML = '';
    (items||[]).forEach(p=>{
      const el = document.createElement('div'); el.className='card';
      el.innerHTML = `
        <div class="card-body">
          <strong>${escapeHtml(p.name)}</strong>
          <div>SKU: ${escapeHtml(p.sku)}</div>
          <div>$${Number(p.price).toFixed(2)} · Stock: ${p.stock}</div>
          <div class="controls">
            <button class="btn" data-edit='${p.id}'>Editar</button>
            <button class="btn secondary" data-del='${p.id}'>Eliminar</button>
          </div>
        </div>`;
      grid.appendChild(el);
    });
    grid.querySelectorAll('[data-edit]')?.forEach(b=>b.addEventListener('click',()=>edit(b.getAttribute('data-edit'))));
    grid.querySelectorAll('[data-del]')?.forEach(b=>b.addEventListener('click',()=>delP(b.getAttribute('data-del'))));
  }

  async function save(){
    const payload = {
      action:'upsert', csrf,
      id: form.id.value||undefined,
      sku: form.sku.value.trim(), name: form.name.value.trim(),
      price: parseFloat(form.price.value||'0'), stock: parseInt(form.stock.value||'0'),
      image: form.image.value.trim(), active: form.active.checked?1:0
    };
    const res = await fetch('<?php echo FS_BASE_URL; ?>/api/products.php',{
      method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.ok){ clearForm(); searchProducts(); }
  }

  function edit(id){
    const card = [...grid.children].find(c=>c.querySelector(`[data-edit="${id}"]`));
    if(!card) return;
    form.id.value = id;
    form.sku.value = card.querySelector('.card-body div:nth-child(2)').textContent.replace('SKU: ','');
    form.name.value = card.querySelector('strong').textContent;
    const line = card.querySelector('.card-body div:nth-child(3)').textContent;
    const m = /\$(\d+\.?\d*).+Stock: (\d+)/.exec(line);
    form.price.value = m?m[1]:''; form.stock.value = m?m[2]:'';
  }

  async function delP(id){
    if(!confirm('¿Eliminar producto?')) return;
    const res = await fetch('<?php echo FS_BASE_URL; ?>/api/products.php',{
      method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'delete', csrf, id:Number(id)})
    });
    const data = await res.json(); if(data.ok){ searchProducts(); }
  }

  async function importJson(){
    const res = await fetch('<?php echo FS_BASE_URL; ?>/api/products.php?action=search');
    await res.json();
    // llamar a import en servidor: reutilizamos products.php helper con un endpoint adicional si lo deseas
    alert('Para importar desde JSON, avísame y habilito endpoint admin específico.');
  }

  function clearForm(){ Object.values(form).forEach(el=>{ if(el.type==='checkbox'){el.checked=true;} else {el.value='';} }); }
  function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",
    ">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}

  btnSearch.addEventListener('click', searchProducts);
  saveBtn.addEventListener('click', save);
  document.getElementById('import-json').addEventListener('click', importJson);
  btnUpload.addEventListener('click', async ()=>{
    const f = fileInput.files?.[0]; if(!f){ alert('Selecciona un archivo'); return; }
    const fd = new FormData();
    fd.append('csrf', <?php echo json_encode($csrf); ?>);
    fd.append('file', f);
    const res = await fetch('<?php echo FS_BASE_URL; ?>/api/upload_image.php',{ method:'POST', body: fd });
    const data = await res.json();
    if(data.ok && data.path){ form.image.value = '<?php echo FS_BASE_URL; ?>/' + data.path; }
  });
  searchProducts();
})();
</script>
