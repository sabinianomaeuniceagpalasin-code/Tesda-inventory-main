document.addEventListener("DOMContentLoaded", () => {
  let qrQueue = [];

  const nameInput = document.getElementById("item-name");
  const descInput = document.getElementById("item-description"); // ✅ NEW
  const deptInput = document.getElementById("item-department");
  const qtyInput = document.getElementById("item-quantity");
  const typeInput = document.getElementById("item-type");
  const addBtn = document.getElementById("add-to-queue-btn");
  const sendBtn = document.getElementById("send-request-btn");

  const tbody = document.getElementById("qr-queue-body");
  const qrResult = document.getElementById("qr-result");

  addBtn.addEventListener("click", async () => {
    const name = (nameInput?.value || "").trim();
    const description = (descInput?.value || "").trim(); // ✅ NEW
    const department = deptInput ? deptInput.value : "";
    const qty = parseInt(qtyInput?.value, 10);
    const type = (typeInput?.value || "qr").trim();

    if (!name || isNaN(qty) || qty <= 0) {
      alert("Please enter valid item name and quantity.");
      return;
    }

    if (!department) {
      alert("Please select a department.");
      return;
    }

    if (!description) {
      alert("Please enter Description / Model.");
      return;
    }

    const existingSerials = qrQueue.map((item) => item.serial);

    try {
      const res = await fetch(`/serials/next/${qty}?exclude=${existingSerials.join(",")}`, {
        credentials: "same-origin",
      });

      if (!res.ok) throw new Error("Server error");

      const data = await res.json();

      data.serials.forEach((serial) => {
        // ✅ store description per item
        qrQueue.push({ name, description, type, serial, department });
      });
    } catch (err) {
      console.error(err);
      alert("Failed to get serials.");
      return;
    }

    renderQueue();
    renderCodes();
    updateSendButton();

    nameInput.value = "";
    descInput.value = ""; // ✅ clear
    qtyInput.value = "";
    typeInput.value = "qr";
    nameInput.focus();
  });

  function renderQueue() {
    tbody.innerHTML = "";

    const grouped = {};
    qrQueue.forEach((item) => {
      // ✅ group by name + description + type + department
      const key = `${item.name}|${item.description}|${item.type}|${item.department}`;
      if (!grouped[key]) grouped[key] = [];
      grouped[key].push(item.serial);
    });

    Object.keys(grouped).forEach((key) => {
      const [name, description, type, department] = key.split("|");
      const serials = grouped[key];

      const row = tbody.insertRow();
      row.insertCell(0).textContent = name;
      row.insertCell(1).textContent = department;
      row.insertCell(2).textContent = description; // ✅
      row.insertCell(3).textContent = serials.length;
      row.insertCell(4).textContent = type;

      const actionCell = row.insertCell(5);
      const btn = document.createElement("button");
      btn.textContent = "Remove";
      btn.onclick = () => {
        qrQueue = qrQueue.filter(
          (item) =>
            !(
              item.name === name &&
              item.description === description &&
              item.type === type &&
              item.department === department
            )
        );
        renderQueue();
        renderCodes();
        updateSendButton();
      };
      actionCell.appendChild(btn);
    });
  }

  function renderCodes() {
    qrResult.innerHTML = "";

    qrQueue.forEach((item) => {
      const box = document.createElement("div");
      box.className = "qr-box";

      if (item.type === "qr") {
        const top = document.createElement("div");
        top.style.textAlign = "center";
        top.style.fontWeight = "bold";
        top.textContent = item.name;

        const mid = document.createElement("div");
        mid.style.textAlign = "center";
        mid.style.fontSize = "12px";
        mid.textContent = item.description; // ✅ show model

        const codeDiv = document.createElement("div");
        codeDiv.style.textAlign = "center";

        // ✅ keep scan data simple: SERIAL ONLY
        new QRCode(codeDiv, {
          text: item.serial,
          width: 120,
          height: 120,
        });

        const bottom = document.createElement("div");
        bottom.style.textAlign = "center";
        bottom.textContent = item.serial;

        box.append(top, mid, codeDiv, bottom);
      } else {
        const label = document.createElement("strong");
        label.textContent = item.name;

        const mid = document.createElement("div");
        mid.style.fontSize = "12px";
        mid.textContent = item.description;

        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");

        // ✅ barcode data: SERIAL ONLY
        JsBarcode(svg, item.serial, {
          format: "CODE128",
          width: 2,
          height: 80,
          displayValue: false,
        });

        box.append(label, mid, svg);
      }

      qrResult.appendChild(box);
    });
  }

  function updateSendButton() {
    sendBtn.disabled = qrQueue.length === 0;
  }

  sendBtn.addEventListener("click", async () => {
    if (qrQueue.length === 0) return;

    const missing = qrQueue.some((x) => !x.department || !x.description);
    if (missing) {
      Swal.fire({
        icon: "warning",
        title: "Missing data",
        text: "Please ensure Department and Description are filled.",
      });
      return;
    }

    try {
      const res = await fetch("/item-approval/request", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ items: qrQueue }), // ✅ description included inside items
      });

      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        data = null;
      }

      if (!res.ok) {
        Swal.fire({
          icon: "error",
          title: "Failed to Send Request",
          text: data?.message || "Something went wrong!",
        });
        return;
      }

      Swal.fire({
        icon: "success",
        title: "Request Sent",
        text: data?.message || "Items sent for approval successfully!",
        timer: 2000,
        showConfirmButton: false,
      });

      qrQueue = [];
      renderQueue();
      renderCodes();
      updateSendButton();
    } catch (err) {
      console.error("Send Request Error:", err);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: err.message || "Failed to send request",
      });
    }
  });

  updateSendButton();
});