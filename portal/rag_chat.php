<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}
include '../includes/header_portal.html';
?>
<style>
/* ── Chat UI ── */
#chat-messages {
    height: 420px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    padding: 1.25rem;
    background: #f8f9fa;
    border-radius: .5rem;
    scroll-behavior: smooth;
}

/* Bubble user */
.msg-user {
    align-self: flex-end;
    max-width: 78%;
    background: #0d6efd;
    color: #fff;
    padding: .6rem 1rem;
    border-radius: 1rem 1rem 0 1rem;
    font-size: .9rem;
    word-break: break-word;
}

/* Bubble AI */
.msg-ai {
    align-self: flex-start;
    max-width: 85%;
    background: #fff;
    border: 1px solid #dee2e6;
    padding: .6rem 1rem;
    border-radius: 1rem 1rem 1rem 0;
    font-size: .9rem;
    word-break: break-word;
    white-space: pre-wrap;
}

/* Typing indicator */
.msg-typing {
    align-self: flex-start;
    display: flex;
    gap: 4px;
    padding: .6rem 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 1rem 1rem 1rem 0;
}
.msg-typing span {
    width: 8px;
    height: 8px;
    background: #adb5bd;
    border-radius: 50%;
    animation: bounce 1.2s infinite;
}
.msg-typing span:nth-child(2) { animation-delay: .2s; }
.msg-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes bounce {
    0%, 80%, 100% { transform: translateY(0); }
    40%           { transform: translateY(-6px); }
}

#chat-input { resize: none; }
</style>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="mb-4">
                <h1 class="h3 mb-1"><i class="bi bi-robot me-2 text-primary"></i>Tanya AI</h1>
                <p class="text-muted mb-0">Asisten AI KMS Computer Club — menjawab berdasarkan arsip pengetahuan klub.</p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0 d-flex flex-column" style="min-height:520px">

                    <!-- Area percakapan -->
                    <div id="chat-messages">
                        <div class="msg-ai">
                            👋 Halo! Saya asisten AI KMS Computer Club SMAN 1 Rancaekek.<br>
                            Silakan ajukan pertanyaan seputar materi, catatan pengalaman, atau alur belajar yang tersedia di klub.
                        </div>
                    </div>

                    <!-- Input area -->
                    <div class="border-top p-3">
                        <div class="d-flex gap-2 align-items-end">
                            <textarea
                                id="chat-input"
                                class="form-control"
                                rows="2"
                                placeholder="Tulis pertanyaan Anda… (Enter untuk kirim, Shift+Enter untuk baris baru)"
                                style="flex:1"
                            ></textarea>
                            <button id="btn-send" class="btn btn-primary" style="height:fit-content; padding: .55rem 1.1rem">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-info-circle me-1"></i>
                            AI hanya menjawab berdasarkan arsip pengetahuan yang ada di database klub.
                        </small>
                    </div>

                </div>
            </div><!-- /card -->

        </div>
    </div>
</main>

<script>
(function () {
    const msgBox   = document.getElementById('chat-messages');
    const input    = document.getElementById('chat-input');
    const btnSend  = document.getElementById('btn-send');

    // Scroll ke bawah
    function scrollBottom() {
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    // Tambah bubble pesan
    function addMessage(text, role) {
        const div       = document.createElement('div');
        div.className   = role === 'user' ? 'msg-user' : 'msg-ai';
        div.textContent = text;
        msgBox.appendChild(div);
        scrollBottom();
        return div;
    }

    // Tampilkan typing indicator
    function showTyping() {
        const div     = document.createElement('div');
        div.className = 'msg-typing';
        div.id        = 'typing-indicator';
        div.innerHTML = '<span></span><span></span><span></span>';
        msgBox.appendChild(div);
        scrollBottom();
    }

    function hideTyping() {
        const el = document.getElementById('typing-indicator');
        if (el) el.remove();
    }

    // Kirim pertanyaan ke prompt_llm.php
    async function sendQuestion() {
        const pertanyaan = input.value.trim();
        if (!pertanyaan) return;

        input.value    = '';
        input.disabled = true;
        btnSend.disabled = true;

        addMessage(pertanyaan, 'user');
        showTyping();

        try {
            const res = await fetch('../rag/prompt_llm.php', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/json' },
                body    : JSON.stringify({ pertanyaan }),
            });

            const data = await res.json();
            hideTyping();

            if (data.jawaban) {
                addMessage(data.jawaban, 'ai');
            } else if (data.error) {
                addMessage('⚠️ ' + data.error, 'ai');
            } else {
                addMessage('Terjadi kesalahan yang tidak diketahui.', 'ai');
            }
        } catch (err) {
            hideTyping();
            addMessage('⚠️ Gagal terhubung ke server. Coba lagi.', 'ai');
            console.error(err);
        } finally {
            input.disabled   = false;
            btnSend.disabled = false;
            input.focus();
        }
    }

    // Klik tombol kirim
    btnSend.addEventListener('click', sendQuestion);

    // Enter untuk kirim, Shift+Enter untuk baris baru
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendQuestion();
        }
    });
})();
</script>

<?php include '../includes/footer_portal.html'; ?>
