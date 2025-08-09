<?php /** @var array $assistant */ ?>
<article class="assistant">
  <header>
    <h2><?= htmlspecialchars($assistant['name']) ?></h2>
    <p class="muted">Expertise: <?= htmlspecialchars($assistant['expertise'] ?? '') ?></p>
    <p><?= nl2br(htmlspecialchars($assistant['description'] ?? '')) ?></p>
  </header>
  <section class="chat">
    <div id="chat-log" class="chat-log"></div>
    <form id="chat-form" class="chat-form" action="#" onsubmit="return false;">
      <input type="text" id="chat-input" placeholder="Type your message..." required>
      <button class="btn" id="chat-send">Send</button>
    </form>
  </section>
</article>
<script>
(function(){
  const log = document.getElementById('chat-log');
  const input = document.getElementById('chat-input');
  let conversationId = 0;
  async function send(){
    const msg = input.value.trim();
    if (!msg) return;
    append('user', msg);
    input.value='';
    const form = new FormData();
    form.append('message', msg);
    if (conversationId>0) form.append('conversation_id', conversationId);
    const resp = await fetch('/a/<?= htmlspecialchars($assistant['slug']) ?>/message', { method:'POST', body: form });
    const data = await resp.json();
    if (data.conversation_id) conversationId = data.conversation_id;
    append('assistant', data.reply || '...');
  }
  document.getElementById('chat-send').addEventListener('click', send);
  input.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); send(); }});
  function append(sender, text){
    const div = document.createElement('div');
    div.className = 'msg ' + sender;
    div.textContent = text;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }
})();
</script>
<style>
.chat{border:1px solid #23262b;border-radius:.5rem;background:#0f1115;margin-top:1rem}
.chat-log{max-height:380px;overflow:auto;padding:1rem}
.msg{padding:.4rem .6rem;border-radius:.4rem;margin:.3rem 0;max-width:80%}
.msg.user{background:#13293d;color:#cbd5e1;margin-left:auto}
.msg.assistant{background:#1e293b;color:#e5e7eb;margin-right:auto}
.chat-form{display:flex;border-top:1px solid #23262b}
.chat-form input{flex:1;border:none;background:transparent;color:#e8eaed;padding:.75rem 1rem}
.chat-form button{margin:.5rem}
</style>