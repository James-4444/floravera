<!-- FLORA CHATBOT WIDGET -->
<style>
/* Chat Toggle Button */
.flora-chat-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d63384, #ff6b9d);
    border: none;
    box-shadow: 0 4px 20px rgba(214,51,132,0.4);
    cursor: pointer;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flora-chat-btn:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 24px rgba(214,51,132,0.5);
}

/* Chat Window */
.flora-chat-window {
    position: fixed;
    bottom: 100px;
    right: 28px;
    width: 340px;
    height: 480px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.15);
    z-index: 9998;
    display: none;
    flex-direction: column;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}
.flora-chat-window.open { display: flex; }

/* Chat Header */
.flora-chat-header {
    background: linear-gradient(135deg, #d63384, #ff6b9d);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.flora-chat-header .flora-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.flora-chat-header .flora-info .flora-name {
    color: #fff;
    font-weight: 700;
    font-size: 15px;
}
.flora-chat-header .flora-info .flora-status {
    color: rgba(255,255,255,0.8);
    font-size: 11px;
}
.flora-chat-close {
    margin-left: auto;
    background: none;
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
    opacity: 0.8;
}
.flora-chat-close:hover { opacity: 1; }

/* Messages Area */
.flora-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #fdf8f5;
}
.flora-msg {
    max-width: 82%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}
.flora-msg.bot {
    background: #fff;
    color: #2d2d2d;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    align-self: flex-start;
}
.flora-msg.user {
    background: linear-gradient(135deg, #d63384, #ff6b9d);
    color: #fff;
    border-bottom-right-radius: 4px;
    align-self: flex-end;
}
.flora-msg.typing {
    background: #fff;
    color: #999;
    font-style: italic;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    align-self: flex-start;
}

/* Quick Replies */
.flora-quick-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 8px 16px 0;
    background: #fdf8f5;
}
.flora-quick-btn {
    background: #fff;
    border: 1px solid #f0a0c0;
    color: #d63384;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.flora-quick-btn:hover {
    background: #d63384;
    color: #fff;
}

/* Input Area */
.flora-chat-input {
    padding: 12px 16px;
    border-top: 1px solid #f0e6ec;
    display: flex;
    gap: 8px;
    background: #fff;
    align-items: center;
}
.flora-chat-input input {
    flex: 1;
    border: 1.5px solid #f0d0e0;
    border-radius: 20px;
    padding: 8px 14px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
}
.flora-chat-input input:focus { border-color: #d63384; }
.flora-chat-input button {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d63384, #ff6b9d);
    border: none;
    color: #fff;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
    flex-shrink: 0;
}
.flora-chat-input button:hover { transform: scale(1.1); }
</style>

<!-- Chat Toggle Button -->
<button class="flora-chat-btn" onclick="toggleFloraChat()" title="Chat with Flora">🌸</button>

<!-- Chat Window -->
<div class="flora-chat-window" id="floraChatWindow">
    <div class="flora-chat-header">
        <div class="flora-avatar">🌸</div>
        <div class="flora-info">
            <div class="flora-name">Flora</div>
            <div class="flora-status">Floravera Assistant • Online</div>
        </div>
        <button class="flora-chat-close" onclick="toggleFloraChat()">×</button>
    </div>

    <div class="flora-chat-messages" id="floraChatMessages">
        <div class="flora-msg bot">Hi! I'm Flora 🌸 your Floravera assistant. How can I help you today?</div>
    </div>

    <div class="flora-quick-replies" id="floraQuickReplies">
        <button class="flora-quick-btn" onclick="sendQuick('Where is my order?')">📦 My Order</button>
        <button class="flora-quick-btn" onclick="sendQuick('What bouquets are available?')">💐 Bouquets</button>
        <button class="flora-quick-btn" onclick="sendQuick('How do I become a vendor?')">🏪 Become Vendor</button>
        <button class="flora-quick-btn" onclick="sendQuick('How does delivery work?')">🚚 Delivery</button>
    </div>

    <div class="flora-chat-input">
        <input type="text" id="floraChatInput" placeholder="Type your message..." onkeydown="if(event.key==='Enter') sendFloraMessage()">
        <button onclick="sendFloraMessage()">➤</button>
    </div>
</div>

<script>
function toggleFloraChat(){
    const win = document.getElementById('floraChatWindow');
    win.classList.toggle('open');
    if(win.classList.contains('open')){
        document.getElementById('floraChatInput').focus();
    }
}

function sendQuick(msg){
    document.getElementById('floraChatInput').value = msg;
    sendFloraMessage();
}

function sendFloraMessage(){
    const input = document.getElementById('floraChatInput');
    const msg = input.value.trim();
    if(!msg) return;

    // Hide quick replies after first message
    document.getElementById('floraQuickReplies').style.display = 'none';

    // Show user message
    appendMessage(msg, 'user');
    input.value = '';

    // Show typing indicator
    const typingId = appendMessage('Flora is typing...', 'typing');

    // Send to backend
    fetch('../chatbot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(data => {
        removeMessage(typingId);
        appendMessage(data.reply || "I'm sorry, I couldn't process your request. Please try again! 🌸", 'bot');
    })
    .catch(err => {
        removeMessage(typingId);
        appendMessage("Sorry, I'm having trouble connecting. Please try again! 🌸", 'bot');
    });
}

function appendMessage(text, type){
    const messages = document.getElementById('floraChatMessages');
    const div = document.createElement('div');
    const id = 'msg_' + Date.now();
    div.className = 'flora-msg ' + type;
    div.id = id;
    div.textContent = text;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return id;
}

function removeMessage(id){
    const el = document.getElementById(id);
    if(el) el.remove();
}
</script>
