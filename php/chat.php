<?php // Fichier : php/chat_widget.php — inclus dans toutes les pages ?>

<!-- ── Chat Flottant ── -->
<div class="chat-bubble" id="chat-bubble" onclick="toggleChat()">
    <i class="fa-solid fa-seedling"></i>
</div>

<div class="chat-window" id="chat-window">
    <div class="chat-header">
        <div class="chat-header-info">
            <div class="chat-avatar"><i class="fa-solid fa-seedling"></i></div>
            <div>
                <div class="chat-title">Assistant Botanique</div>
                <div class="chat-status"><span class="dot"></span> En ligne</div>
            </div>
        </div>
        <button class="chat-close" onclick="toggleChat()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="chat-messages" id="chat-messages">
        <div class="msg bot">
            <div class="msg-bubble">Bonjour ! Je suis votre assistant botanique 🌿 Posez-moi vos questions sur les plantes.</div>
        </div>
    </div>

    <div class="chat-input-row">
        <input type="text" id="chat-input" placeholder="Posez votre question..." onkeydown="if(event.key==='Enter') sendMessage()">
        <button onclick="sendMessage()" id="send-btn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    const BACKEND_URL = "http://localhost:5001/chat";
    const SAVE_CHAT_URL = "php/save_chat.php"; // URL pour sauvegarder les conversations

    function toggleChat() {
        document.getElementById('chat-window').classList.toggle('open');
        document.getElementById('chat-bubble').classList.toggle('hidden');
    }

    function appendMessage(text, sender) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = 'msg ' + sender;
        div.innerHTML = `<div class="msg-bubble">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function setLoading(on) {
        const btn = document.getElementById('send-btn');
        btn.disabled = on;
        btn.innerHTML = on
            ? '<i class="fa-solid fa-circle-notch fa-spin"></i>'
            : '<i class="fa-solid fa-paper-plane"></i>';
    }

    // Fonction pour sauvegarder la conversation en base de données
    async function saveChatToDatabase(message, reponse) {
        try {
            await fetch(SAVE_CHAT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message, reponse: reponse })
            });
        } catch (e) {
            console.error("Erreur sauvegarde chat:", e);
        }
    }

    async function sendMessage() {
        const inputEl = document.getElementById('chat-input');
        const message = inputEl.value.trim();
        if (!message) return;

        appendMessage(message, 'user');
        inputEl.value = '';
        setLoading(true);

        try {
            const res = await fetch(BACKEND_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });
            const data = await res.json();
            const reply = data.reply || "Une erreur est survenue.";
            
            appendMessage(reply, 'bot');
            
            // Sauvegarder automatiquement la conversation
            await saveChatToDatabase(message, reply);
            
        } catch (e) {
            appendMessage("Impossible de joindre le serveur. Vérifiez que Flask tourne sur le port 5000.", 'bot');
        } finally {
            setLoading(false);
        }
    }
</script>