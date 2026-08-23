<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}
include '../includes/header_portal.html';
?>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
<style>
/* Chat scroll area */
#chat-messages {
  scroll-behavior: smooth;
}
/* Typing dots */
@keyframes bounce {
  0%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-6px); }
}
.dot { animation: bounce 1.2s infinite; }
.dot:nth-child(2) { animation-delay: .2s; }
.dot:nth-child(3) { animation-delay: .4s; }
</style>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header -->
  <div class="mb-6">
    <div class="flex items-center gap-3 mb-1">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-200">
        <i data-lucide="bot" class="w-5 h-5 text-white"></i>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Tanya AI</h1>
    </div>
    <p class="text-gray-500 text-sm ml-12">Asisten AI KMS Computer Club — menjawab berdasarkan arsip pengetahuan klub.</p>
  </div>

  <!-- Chat Container -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col" style="height: 580px;">

    <!-- Messages Area -->
    <div id="chat-messages" class="flex-1 overflow-y-auto p-5 space-y-3 bg-gray-50">
      <!-- Initial AI message -->
      <div class="flex items-start gap-2.5 ai-msg">
        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shrink-0 mt-0.5">
          <i data-lucide="bot" class="w-3.5 h-3.5 text-white"></i>
        </div>
        <div class="max-w-[80%] bg-white rounded-2xl rounded-tl-sm border border-gray-200 px-4 py-3 shadow-sm text-sm text-gray-800 leading-relaxed">
          👋 Halo! Saya asisten AI <strong>KMS Computer Club</strong>.<br>
          Silakan ajukan pertanyaan seputar materi, catatan pengalaman, atau alur belajar yang ada di klub.
        </div>
      </div>
    </div>

    <!-- Input Area -->
    <div class="border-t border-gray-100 p-4 bg-white">
      <div class="flex gap-3 items-end">
        <textarea
          id="chat-input"
          rows="2"
          placeholder="Tulis pertanyaan… (Enter kirim, Shift+Enter baris baru)"
          class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
        ></textarea>
        <button id="btn-send"
          class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-500 transition-all shadow-sm shadow-indigo-200 shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
          <i data-lucide="send" class="w-4 h-4"></i>
        </button>
      </div>
      <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        AI hanya menjawab berdasarkan arsip pengetahuan yang ada di database klub.
      </p>
    </div>
  </div>

</main>

<script>
lucide.createIcons();

(function () {
  const msgBox  = document.getElementById('chat-messages');
  const input   = document.getElementById('chat-input');
  const btnSend = document.getElementById('btn-send');

  function scrollBottom() { msgBox.scrollTop = msgBox.scrollHeight; }

  function addUserMsg(text) {
    const div = document.createElement('div');
    div.className = 'flex justify-end';
    div.innerHTML = `
      <div class="max-w-[75%] bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 text-sm leading-relaxed shadow-sm shadow-indigo-200">
        ${escHtml(text)}
      </div>
    `;
    msgBox.appendChild(div);
    scrollBottom();
  }

  function addAIMsg(text) {
    const div = document.createElement('div');
    div.className = 'flex items-start gap-2.5 ai-msg';
    div.innerHTML = `
      <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shrink-0 mt-0.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75h4.5m-9 2.25h13.5M5.25 9.75h13.5M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6z"/></svg>
      </div>
      <div class="max-w-[80%] bg-white rounded-2xl rounded-tl-sm border border-gray-200 px-4 py-3 shadow-sm text-sm text-gray-800 leading-relaxed prose prose-sm max-w-none">${DOMPurify.sanitize(marked.parse(text), { ADD_ATTR: ['target', 'download', 'class'] })}</div>
    `;
    msgBox.appendChild(div);
    scrollBottom();
    return div;
  }

  function showTyping() {
    const div = document.createElement('div');
    div.id = 'typing';
    div.className = 'flex items-start gap-2.5';
    div.innerHTML = `
      <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shrink-0 mt-0.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l3-3m0 0l3 3m-3-3v12"/></svg>
      </div>
      <div class="bg-white rounded-2xl rounded-tl-sm border border-gray-200 px-4 py-3.5 flex gap-1.5 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-gray-400 dot"></span>
        <span class="w-2 h-2 rounded-full bg-gray-400 dot"></span>
        <span class="w-2 h-2 rounded-full bg-gray-400 dot"></span>
      </div>
    `;
    msgBox.appendChild(div);
    scrollBottom();
  }

  function hideTyping() {
    const el = document.getElementById('typing');
    if (el) el.remove();
  }

  function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  async function sendQuestion() {
    const pertanyaan = input.value.trim();
    if (!pertanyaan) return;
    input.value = '';
    input.disabled = true;
    btnSend.disabled = true;

    addUserMsg(pertanyaan);
    showTyping();

    try {
      const res = await fetch('prompt_llm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pertanyaan }),
      });
      const data = await res.json();
      hideTyping();
      if (data.jawaban) addAIMsg(data.jawaban);
      else if (data.error) addAIMsg('⚠️ ' + data.error);
      else addAIMsg('Terjadi kesalahan yang tidak diketahui.');
    } catch (err) {
      hideTyping();
      addAIMsg('⚠️ Gagal terhubung ke server. Coba lagi.');
      console.error(err);
    } finally {
      input.disabled = false;
      btnSend.disabled = false;
      input.focus();
    }
  }

  btnSend.addEventListener('click', sendQuestion);
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendQuestion(); }
  });
})();
</script>

<?php include '../includes/footer_portal.html'; ?>
