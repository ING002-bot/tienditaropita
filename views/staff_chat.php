<?php require_once __DIR__ . '/../includes/config.php'; require_once __DIR__ . '/../includes/auth.php'; fs_require_role(['admin','empleado']); ?>
<section>
  <h2>Chat interno</h2>
  <div class="controls" style="margin-bottom:.5rem">
    <select id="room" class="input">
      <option value="1">General</option>
    </select>
    <button class="btn secondary" id="reload-rooms">Recargar salas</button>
  </div>
  <div id="staff-messages" style="height:360px;overflow:auto;border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:.75rem;background:#0e121a"></div>
  <div class="controls" style="margin-top:.5rem">
    <input class="input" id="staff-input" placeholder="Escribe un mensaje...">
    <button class="btn" id="staff-send">Enviar</button>
  </div>
</section>
<script>
(async function(){
  const roomSel=document.getElementById('room');
  const messages=document.getElementById('staff-messages');
  const input=document.getElementById('staff-input');
  const sendBtn=document.getElementById('staff-send');
  const reloadBtn=document.getElementById('reload-rooms');
  let afterId=0; let timer;

  async function loadRooms(){
    const res=await fetch('<?php echo FS_BASE_URL; ?>/api/staff_chat.php?action=rooms');
    const data=await res.json();
    roomSel.innerHTML='';
    (data.rooms||[]).forEach(r=>{
      const o=document.createElement('option'); o.value=r.id; o.textContent=r.name; roomSel.appendChild(o);
    });
  }
  async function fetchMessages(){
    const r=roomSel.value||1;
    const res=await fetch(`<?php echo FS_BASE_URL; ?>/api/staff_chat.php?action=fetch&room_id=${r}&after_id=${afterId}`);
    const data=await res.json();
    (data.messages||[]).forEach(m=>{
      const d=document.createElement('div');
      d.innerHTML=`<small style="color:var(--muted)">#${m.id} · ${m.name} · ${m.created_at}</small><br>${escapeHtml(m.message)}`;
      messages.appendChild(d); afterId=Math.max(afterId, m.id);
    });
    messages.scrollTop=messages.scrollHeight;
  }
  async function sendMessage(){
    const r=roomSel.value||1; const text=input.value.trim(); if(!text) return;
    input.value='';
    await fetch('<?php echo FS_BASE_URL; ?>/api/staff_chat.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'send',room_id:Number(r),message:text})
    });
    fetchMessages();
  }
  function escapeHtml(s){return s.replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",
    ">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}

  sendBtn.addEventListener('click',sendMessage);
  input.addEventListener('keydown',e=>{if(e.key==='Enter'){sendMessage();}});
  reloadBtn.addEventListener('click',loadRooms);

  await loadRooms();
  await fetchMessages();
  timer=setInterval(fetchMessages, 1500);
})();
</script>
