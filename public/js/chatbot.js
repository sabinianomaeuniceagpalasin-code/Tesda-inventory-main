
(function () {
  const toggle = document.getElementById("chat-toggle");
  const popup = document.getElementById("chat-popup");
  const closeBtn = document.getElementById("chat-close");

  const input = document.getElementById("chat-input");
  const sendBtn = document.getElementById("chat-send");
  const messagesDiv = document.getElementById("chat-messages");

  /* =========================
     SCROLL HELPER (IMPORTANT)
  ========================= */
  function scrollToBottom() {
    requestAnimationFrame(() => {
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    });
  }

  /* =========================
     OPEN / CLOSE CHAT
  ========================= */
  toggle.addEventListener("click", () => {
    popup.style.display = "flex";
    popup.setAttribute("aria-hidden", "false");
    input.focus();
    scrollToBottom();
  });

  closeBtn.addEventListener("click", () => {
    popup.style.display = "none";
    popup.setAttribute("aria-hidden", "true");
  });

  document.addEventListener("click", (e) => {
    if (!popup.contains(e.target) && !toggle.contains(e.target)) {
      popup.style.display = "none";
      popup.setAttribute("aria-hidden", "true");
    }
  });

  /* =========================
     SEND MESSAGE
  ========================= */
  function sendMessage() {
  const text = input.value.trim();
  if (!text) return;

  appendUserMessage(text);
  input.value = "";

  showTypingIndicator();

  fetch("/chatbot/message", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content")
    },
    body: JSON.stringify({ message: text })
  })
    .then(res => {
      if (!res.ok) throw new Error("Server error");
      return res.json();
    })
    .then(data => {
      removeTypingIndicator();
      appendBotMessage(data.reply);
    })
    .catch(() => {
      removeTypingIndicator();
      appendBotMessage("Sorry, something went wrong.");
    });
}


  sendBtn.addEventListener("click", sendMessage);

  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      sendMessage();
    }
  });

  /* =========================
     RENDERING
  ========================= */
  function appendUserMessage(text) {
    const row = document.createElement("div");
    row.className = "chat-row chat-you-row";

    const bubble = document.createElement("div");
    bubble.className = "chat-bubble you-bubble";
    bubble.textContent = text;

    row.appendChild(bubble);
    messagesDiv.appendChild(row);
    scrollToBottom();
  }

  function appendBotMessage(text) {
  const row = document.createElement("div");
  row.className = "chat-row chat-bot-row";

  const avatar = document.createElement("img");
  avatar.src = "/images/chatbot.jpg";
  avatar.className = "chat-avatar";
  avatar.alt = "Chatbot";

  const bubble = document.createElement("div");
  bubble.className = "chat-bubble bot-bubble";

  // ✅ ALLOW HTML FROM BACKEND
  bubble.innerHTML = text;

  row.appendChild(avatar);
  row.appendChild(bubble);
  messagesDiv.appendChild(row);
  scrollToBottom();
}


function showTypingIndicator() {
  const row = document.createElement("div");
  row.className = "chat-row chat-bot-row";
  row.id = "typing-indicator";

  const avatar = document.createElement("img");
  avatar.src = "/images/chatbot.png";
  avatar.className = "chat-avatar";

  const bubble = document.createElement("div");
  bubble.className = "chat-bubble bot-bubble typing-bubble";
  bubble.innerHTML = `
    <span class="dot"></span>
    <span class="dot"></span>
    <span class="dot"></span>
  `;

  row.appendChild(avatar);
  row.appendChild(bubble);
  messagesDiv.appendChild(row);
  scrollToBottom();
}

function removeTypingIndicator() {
  const indicator = document.getElementById("typing-indicator");
  if (indicator) indicator.remove();
}

})();
