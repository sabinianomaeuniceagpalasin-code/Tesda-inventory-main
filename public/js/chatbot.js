// public/js/chatbot.js
(function () {
  const toggle = document.getElementById("chat-toggle");
  const popup = document.getElementById("chat-popup");
  const closeBtn = document.getElementById("chat-close");

  const input = document.getElementById("chat-input");
  const sendBtn = document.getElementById("chat-send");
  const messagesDiv = document.getElementById("chat-messages");
  const suggestionsBox = document.getElementById("chat-suggestions");

  if (!toggle || !popup || !closeBtn || !input || !sendBtn || !messagesDiv) return;

  function hideAllSuggestions() {
    hideSuggestions();
  }

    function getCategory(text) {
    const t = String(text).toLowerCase();
    if (t.includes("sn")) return "SERIAL";
    if (t.includes("list") || t.includes("show") || t.includes("stock") || t.includes("how many")) return "INVENTORY";
    if (t.includes("qr") || t.includes("barcode")) return "CODES";
    if (t.includes("approval")) return "APPROVAL";
    if (t.includes("why") || t.includes("how")) return "FAQ";
    return "HELP";
  }

  /* =========================
     SETTINGS
  ========================= */
  const TYPEWRITER = {
    enabled: true,
    msPerChar: 12, // typing speed
    msPerTag: 0,   // keep 0 so tags appear instantly
    maxMsPerChar: 22,
  };

  const SUGGEST_UI = {
    maxItems: 8,
    debounceMs: 200,
    // Show curated professional questions if input is empty and user focuses
    showOnFocusWhenEmpty: true,
  };

  /* =========================
     SCROLL HELPER
  ========================= */
  function scrollToBottom() {
    requestAnimationFrame(() => {
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    });
  }

  /* =========================
     OPEN / CLOSE CHAT (TOGGLE)
  ========================= */
  function openChat() {
    popup.style.display = "flex";
    popup.setAttribute("aria-hidden", "false");
    input.focus();
    scrollToBottom();

    // Optional: show suggestions when opening
    if (SUGGEST_UI.showOnFocusWhenEmpty) {
      const q = input.value.trim();
      fetchSuggestions(q);
    }
  }

  function closeChat() {
    popup.style.display = "none";
    popup.setAttribute("aria-hidden", "true");
    hideSuggestions();
  }

  function isChatOpen() {
    return popup.getAttribute("aria-hidden") === "false";
  }

  toggle.addEventListener("click", (e) => {
    e.stopPropagation();
    if (isChatOpen()) closeChat();
    else openChat();
  });

  closeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    closeChat();
  });

  popup.addEventListener("click", (e) => e.stopPropagation());

  popup.addEventListener("click", (e) => {
  if (!e.target.closest("#chat-input")) {
    hideSuggestions();
  }
});

  input.addEventListener("click", (e) => e.stopPropagation());

  document.addEventListener("click", (e) => {
    const clickedSuggestion = suggestionsBox && suggestionsBox.contains(e.target);
    const clickedPopup = popup.contains(e.target);
    const clickedToggle = toggle.contains(e.target);

    if (!clickedPopup && !clickedToggle && !clickedSuggestion) {
      closeChat();
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
    hideSuggestions();

    showTypingIndicator();

    fetch("/chatbot/message", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document
          .querySelector('meta[name="csrf-token"]')
          .getAttribute("content"),
      },
      body: JSON.stringify({ message: text }),
    })
      .then((res) => {
        if (!res.ok) throw new Error("Server error");
        return res.json();
      })
      .then((data) => {
        removeTypingIndicator();
        appendBotMessage(data.reply, { typewriter: true });
      })
      .catch(() => {
        removeTypingIndicator();
        appendBotMessage("Sorry, something went wrong.", { typewriter: true });
      });
  }

  sendBtn.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    sendMessage();
  });

  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      sendMessage();
    }
  });

  /* =========================
     SUGGESTIONS (BACKEND)
     - Displays more "professional" labels (client-facing)
  ========================= */
  let suggestTimer = null;

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function escapeHtmlAttr(str) {
    return escapeHtml(str);
  }

  // Professional display mapping (optional)
  // If backend returns "raw" suggestions, we can present a polished label while keeping the actual text.
  function toProfessionalLabel(text) {
    const t = String(text).trim();

    // Common clean-ups
    if (/^qr vs barcode$/i.test(t)) return "Compare QR Codes vs. Barcodes";
    if (/^low stock items$/i.test(t)) return "Show Low-Stock Items";
    if (/^list available items$/i.test(t)) return "Show Available Items";
    if (/^list damaged items$/i.test(t)) return "Show Damaged Items";
    if (/^list unserviceable items$/i.test(t)) return "Show Unserviceable Items";
    if (/^list all items$/i.test(t)) return "Show All Inventory Items";
    if (/^how many items are in inventory\?$/i.test(t)) return "Show Total Inventory Count";
    if (/^what is item approval request\?$/i.test(t)) return "Explain the Item Approval Request Process";
    if (/^how to check item status\?$/i.test(t)) return "How to Check an Item’s Status";
    if (/^why is serial number tracking important\?$/i.test(t)) return "Why Serial Number Tracking Matters";
    if (/^why validate serial numbers before inserting\?$/i.test(t)) return "Why Serial Validation Is Required";
    if (/^how to track items under maintenance\?$/i.test(t)) return "How Maintenance Tracking Works";
    if (/^show damaged items with borrower$/i.test(t)) return "Damaged Items (with Last Borrower)";
    if (/^show unserviceable items with borrower$/i.test(t)) return "Unserviceable Items (with Last Borrower)";

    // Template improvements
    if (/^who borrowed sn/i.test(t)) return t.replace(/^who borrowed/i, "Who Borrowed");
    if (/^when was sn/i.test(t)) return t.replace(/^when was/i, "When Was");
    if (/^what is the status of sn/i.test(t)) return t.replace(/^what is the status of/i, "Item Status:");
    if (/^who reported damage of sn/i.test(t)) return t.replace(/^who reported damage of/i, "Damage Reported By:");

    // For dynamic "How many {Item}?"
    if (/^how many .+\?$/i.test(t)) return t.replace(/^how many/i, "Check Stock Level for");

    return t;
  }

    let activeSuggestionIndex = -1;

  function showSuggestions(list, headerText = "Suggestions") {
    if (!suggestionsBox) return;

    if (!list || !list.length) {
      hideSuggestions();
      return;
    }

    activeSuggestionIndex = -1;

    const itemsHtml = list
      .slice(0, SUGGEST_UI.maxItems)
      .map((text, idx) => {
        const label = toProfessionalLabel(text);
        const cat = getCategory(text);
        return `
          <div class="chat-suggestion-item" data-index="${idx}" data-text="${escapeHtmlAttr(text)}">
            <div class="chat-suggestion-meta">
              <div class="chat-suggestion-title">${escapeHtml(label)}</div>
              <div class="chat-suggestion-sub">${escapeHtml(text)}</div>
            </div>
            <div class="chat-suggestion-badge">${escapeHtml(cat)}</div>
          </div>
        `;
      })
      .join("");

    suggestionsBox.innerHTML = `
      <div class="suggestions-header">${escapeHtml(headerText)}</div>
      <div class="suggestions-list">${itemsHtml}</div>
    `;

    suggestionsBox.style.display = "block";
  }

  function hideSuggestions() {
    if (!suggestionsBox) return;
    suggestionsBox.style.display = "none";
    suggestionsBox.innerHTML = "";
  }

  function fetchSuggestions(query) {
    const q = query ?? "";
    fetch(`/chatbot/suggestions?q=${encodeURIComponent(q)}`)
      .then((res) => res.json())
      .then((data) => {
        showSuggestions(data.suggestions || []);
      })
      .catch(() => hideSuggestions());
  }

   input.addEventListener("input", () => {
      const q = input.value.trim();

      clearTimeout(suggestTimer);
      suggestTimer = setTimeout(() => {
        fetchSuggestions(q); // ✅ show suggestions even if empty
      }, SUGGEST_UI.debounceMs);
    });

  input.addEventListener("focus", () => {
    e.stopPropagation();
  fetchSuggestions(input.value.trim()); // ✅ show on focus
});

  // Click suggestion => store in textbox only
suggestionsBox?.addEventListener("click", (e) => {
  e.preventDefault();
  e.stopPropagation();

  const item = e.target.closest(".chat-suggestion-item");
  if (!item) return;

  input.value = item.dataset.text;
  hideAllSuggestions();
  input.focus(); // ✅ keep focus so user can edit before sending
});

    input.addEventListener("keydown", (e) => {
    const isDropdownOpen = suggestionsBox && suggestionsBox.style.display === "block";
    if (!isDropdownOpen) {
      if (e.key === "Escape") hideAllSuggestions();
      return;
    }

    const items = Array.from(suggestionsBox.querySelectorAll(".chat-suggestion-item"));
    if (!items.length) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
    } else if (e.key === "Enter") {
      if (activeSuggestionIndex >= 0) {
        e.preventDefault();
        const picked = items[activeSuggestionIndex];
        input.value = picked.dataset.text;
        hideAllSuggestions();
        input.focus(); // ✅ user can edit, then press Enter again to send
      }
      return;
    } else if (e.key === "Escape") {
      e.preventDefault();
      hideAllSuggestions();
      return;
    } else {
      return;
    }

    // Apply active style
    items.forEach((el, idx) => el.classList.toggle("active", idx === activeSuggestionIndex));

    // Keep active item visible
    items[activeSuggestionIndex]?.scrollIntoView({ block: "nearest" });
  });

  /* =========================
     TYPEWRITER (BOT MESSAGE)
     - Supports HTML from backend.
     - Reveals text nodes letter-by-letter while keeping tags intact.
  ========================= */
  function setCaretToEnd(el) {
    // Keeps scroll behavior smooth as we append
    scrollToBottom();
  }

  function tokenizeHtml(html) {
    // returns array of tokens: {type:"tag"|"text", value:string}
    const tokens = [];
    const re = /(<[^>]+>)|([^<]+)/g;
    let m;
    while ((m = re.exec(html)) !== null) {
      if (m[1]) tokens.push({ type: "tag", value: m[1] });
      else if (m[2]) tokens.push({ type: "text", value: m[2] });
    }
    return tokens;
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  async function typewriterIntoElement(el, html) {
    const tokens = tokenizeHtml(String(html ?? ""));
    el.innerHTML = "";

    for (const t of tokens) {
      if (t.type === "tag") {
        // insert tag instantly
        el.insertAdjacentHTML("beforeend", t.value);
        setCaretToEnd(el);
        if (TYPEWRITER.msPerTag > 0) await sleep(TYPEWRITER.msPerTag);
      } else {
        const text = t.value;

        // Find current "insertion point": append to last text node by using a temp span
        // We'll just append a text node and grow it char-by-char.
        const node = document.createTextNode("");
        el.appendChild(node);

        // dynamic delay (shorter for whitespace)
        for (let i = 0; i < text.length; i++) {
          node.nodeValue += text[i];
          setCaretToEnd(el);

          const isSpace = /\s/.test(text[i]);
          const base = TYPEWRITER.msPerChar;
          const delay = clamp(isSpace ? base * 0.35 : base, 0, TYPEWRITER.maxMsPerChar);
          await sleep(delay);
        }
      }
    }
  }

  function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
  }

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

  function appendBotMessage(text, opts = { typewriter: true }) {
    const row = document.createElement("div");
    row.className = "chat-row chat-bot-row";

    const avatar = document.createElement("img");
    avatar.src = "/images/chatbot.jpg";
    avatar.className = "chat-avatar";
    avatar.alt = "Chatbot";

    const bubble = document.createElement("div");
    bubble.className = "chat-bubble bot-bubble";

    row.appendChild(avatar);
    row.appendChild(bubble);
    messagesDiv.appendChild(row);
    scrollToBottom();

    const shouldType =
      TYPEWRITER.enabled && opts?.typewriter !== false && typeof text === "string";

    if (!shouldType) {
      bubble.innerHTML = text;
      scrollToBottom();
      return;
    }

    // Typewriter effect (supports HTML)
    typewriterIntoElement(bubble, text).catch(() => {
      // fallback if something fails
      bubble.innerHTML = text;
      scrollToBottom();
    });
  }

  function showTypingIndicator() {
    const row = document.createElement("div");
    row.className = "chat-row chat-bot-row";
    row.id = "typing-indicator";

    const avatar = document.createElement("img");
    avatar.src = "/images/chatbot.png";
    avatar.className = "chat-avatar";
    avatar.alt = "Chatbot";

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

  /* =========================
   HIDE SUGGESTIONS WHEN SCROLLING
    ========================= */
    messagesDiv.addEventListener("scroll", () => {
      hideAllSuggestions();
    });

})();