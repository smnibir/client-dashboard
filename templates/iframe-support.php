<div class="common-padding">
    <div class="tab-head" style="z-index: 9999;">
    <h2>Support Center</h2>
    <span>Get help with any questions or issues you're experiencing</span>
    
</div>
    <div class="" style="z-index: 9;gap: 1rem;display: grid
;
    grid-template-columns: 8fr 4fr;">
    <div class="response-times">
        <h3 style="display: flex; gap: 7px;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" color="#44da67" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square w-5 h-5 text-primary" data-lov-id="src/components/portal/SupportForm.tsx:91:14" data-lov-name="MessageSquare" data-component-path="src/components/portal/SupportForm.tsx" data-component-line="91" data-component-file="SupportForm.tsx" data-component-name="MessageSquare" data-component-content="%7B%22className%22%3A%22w-5%20h-5%20text-primary%22%7D"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> Submit Support Request</h3>
                <?php
      // Prefer gravity_form() if available; fallback to shortcode.
      if (function_exists('gravity_form')) {
          gravity_form(1, false, false, false, '', true); // id=1, no title/desc, AJAX on
      } else {
          echo do_shortcode('[gravityform id="1" title="false" description="false" ajax="true"]');
      }
      ?>
    </div>
        <div>
            <div class="support-container">
              <div class="response-times">
                <h3>Response Times</h3>
                <ul>
                  <li><span class="dot low"></span> <div class="priority-status"><span>Normal Priority</span> <span>24–48 hours</span></div></li>
                  <li><span class="dot medium"></span> <div class="priority-status"><span>High Priority</span> <span>4–24 hours</span></div></li>
                  <li><span class="dot urgent"></span><div class="priority-status"> <span>Urgent Priority</span> <span>1–4 hours</span></div></li>
                </ul>
              </div>
            
              <div class="immediate-help">
                <h3>Need Immediate Help?</h3>
                <p style="color:#999;margin-bottom: 0.9rem;">For urgent issues, contact your account manager directly:</p>
                <p class="manager-name" style="font-size: 0.975rem;margin-bottom: 0.6em;">Webgrowth Support</p>
                <p class="email" style="margin-bottom: 0em;"><a href="mailto:support@webgrowth.io">support@webgrowth.io</a></p>
                <p class="phone"><a href="te;:480.331.5849">480.331.5849</a></p>
               <a href="tel:480.331.5849" class="quick-call">
  <span class="icon">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap">
      <path
        d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
      </path>
    </svg>
  </span>
  Quick Call
</a>

              </div>
            </div>
        </div>
    </div>

    <style>

.support-container {
  min-width: 500px;
  margin:  auto;
  /*padding: 20px;*/
  border-radius: 10px;
  /*height: 100vh;*/
  background: #111111;
}

.response-times,
.immediate-help {
  background: #161616;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  border: 1px solid #2e2e2e;
}

.response-times h3,
.immediate-help h3 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 18px;
  font-weight: 600;
}

.response-times ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.response-times li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 15px;
}

.dot {
  height: 10px;
  width: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 8px;
}

.low {
  background-color: #3e63dd;
}

.medium {
  background-color: #ffc53d;
}

.urgent {
  background-color: rgb(255, 133, 137);
}

.manager-name {
  font-weight: bold;
}

.email,
.phone {

  font-size: 0.75rem;
  color: #999;
}

.quick-call {
  margin-top: 15px;
  width: 100%;
  padding: 10px;
  background-color: #1f1f1f;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: background 0.3s;
}

.quick-call:hover {
  color: #44da67;
}

.icon {
  margin-right: 8px;
  font-size: 16px;
}

/* Hide the real select */
#input_1_7.gfield_select {
  position: absolute !important;
  width: 1px !important; height: 1px !important;
  overflow: hidden !important; clip: rect(0 0 0 0) !important;
  white-space: nowrap !important; border: 0 !important;
}

/* Custom wrapper */
.urgency-select { position: relative; min-height: 48px;}

/* Button */
.urgency-button {
  display:flex; justify-content:space-between; align-items:center;
  width:100%; min-height: 48px; border:1px solid #2e2e2e; border-radius:8px;
  background:#1f1f1f; cursor:pointer;
}
.light-theme .urgency-button {
  display:flex; justify-content:space-between; align-items:center;
  width:100%; min-height: 48px; border:1px solid #e5e5e5; border-radius:8px;
  background:#f5f5f5; cursor:pointer;
}
.urgency-select button{
    color: #999999;
}
.urgency-current , .urgency-current span{ display:flex; align-items:center; gap:.5rem; }

/* Menu */
.urgency-menu {
  position:absolute; top:100%; left:0; right:0; margin-top:4px;
 border:1px solid #ccc; border-radius:6px;
  box-shadow:0 4px 12px rgba(0,0,0,.1); z-index:1000;
}
.light-theme .urgency-menu {
  position:absolute; top:100%; left:0; right:0; margin-top:4px;
  background:#fff; border:1px solid #ccc; border-radius:6px;
  box-shadow:0 4px 12px rgba(0,0,0,.1); z-index:1000;
}
.urgency-menu[hidden]{display:none;}
.urgency-option {
  width:100%; text-align:left; padding:.5rem .8rem; border:0; background:#fff;
  display:flex; align-items:center; gap:.5rem; cursor:pointer;
}
.urgency-option:hover{background:#f3f4f6;}

/* Badge icons */
.urgency-icon{width:14px;height:14px;border-radius:50%;display:inline-block;}
.urgency--Urgent .urgency-icon{background:#dc2626;margin-right: 8px;}
.urgency--High   .urgency-icon{background:#ea580c;margin-right: 8px;}
.urgency--Normal .urgency-icon{background:#2563eb;margin-right: 8px;}
.urgency--Low    .urgency-icon{background:#9ca3af;margin-right: 8px;}

button.urgency-option span{
    display: flex
;
    align-content: center;
    align-items: center;
}
    </style>

<script>
(function(){
  const SELECT_ID = 'input_1_7';
  const COLORS = {
    'Urgent': 'urgency--Urgent',
    'High': 'urgency--High',
    'Normal': 'urgency--Normal',
    'Low': 'urgency--Low'
  };

  function buildItem(label){
    const cls = COLORS[label] || 'urgency--Low';
    return `<span class="${cls} urgency-badge"><span class="urgency-icon"></span><span>${label}</span></span>`;
  }

  function enhance(select){
    if(!select || select.dataset.enhanced) return;
    select.dataset.enhanced = "1";

    const wrap = document.createElement('div');
    wrap.className = 'urgency-select';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    const btn = document.createElement('button');
    btn.type = "button"; btn.className = "urgency-button";
    const current = document.createElement('span');
    current.className = 'urgency-current';
    btn.appendChild(current);
    wrap.appendChild(btn);

    const menu = document.createElement('div');
    menu.className = 'urgency-menu'; menu.hidden = true;
    wrap.appendChild(menu);

    // Build options
    Array.from(select.options).forEach(opt=>{
      const val = opt.value, lbl = opt.text.trim();
      const item = document.createElement('button');
      item.type="button"; item.className="urgency-option";
      item.innerHTML = (val ? buildItem(lbl) : lbl);
      item.addEventListener('click', ()=>{
        select.value = val;
        select.dispatchEvent(new Event('change',{bubbles:true}));
        render();
        menu.hidden=true;
      });
      menu.appendChild(item);
    });

    function render(){
      const opt = select.options[select.selectedIndex];
      current.innerHTML = opt.value ? buildItem(opt.text) : opt.text;
    }

    btn.addEventListener('click', ()=>{ menu.hidden = !menu.hidden; });
    document.addEventListener('click', e=>{
      if(!wrap.contains(e.target)) menu.hidden = true;
    });

    render();
  }

  function init(){
    const s = document.getElementById(SELECT_ID);
    if(s) enhance(s);
  }
  document.addEventListener('DOMContentLoaded', init);
  document.addEventListener('gform_post_render', init);
})();
</script>
