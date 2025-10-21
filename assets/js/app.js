const navToggle=document.getElementById('nav-toggle');
const nav=document.getElementById('nav');
if(navToggle){navToggle.addEventListener('click',()=>{nav.classList.toggle('open')})}

async function cartRequest(action, payload={}){
  const params=new URLSearchParams({action});
  const res=await fetch(`./api/cart.php?${params.toString()}`,{
    method: payload && Object.keys(payload).length? 'POST':'GET',
    headers:{'Content-Type':'application/json'},
    body: payload && Object.keys(payload).length? JSON.stringify(payload): undefined
  });
  return res.json();
}

async function updateCartCount(){
  try{const data=await cartRequest('count');
    const el=document.getElementById('cart-count');
    if(el) el.textContent=data.count||0;
  }catch(e){/* noop */}
}

async function bindAddToCart(){
  document.querySelectorAll('[data-add-to-cart]')?.forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id=btn.getAttribute('data-id');
      const qty=parseInt(btn.getAttribute('data-qty')||'1',10);
      btn.disabled=true;btn.textContent='Agregado';
      try{await cartRequest('add',{id,qty});updateCartCount();}
      finally{setTimeout(()=>{btn.disabled=false;btn.textContent='Agregar';},700)}
    });
  })
}

async function bindCartActions(){
  document.querySelectorAll('[data-remove]')?.forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id=btn.getAttribute('data-id');
      await cartRequest('remove',{id});
      location.reload();
    });
  });
  const clearBtn=document.getElementById('clear-cart');
  if(clearBtn){clearBtn.addEventListener('click', async ()=>{await cartRequest('clear');location.reload();});}
}

document.addEventListener('DOMContentLoaded',()=>{
  updateCartCount();
  bindAddToCart();
  bindCartActions();
});
