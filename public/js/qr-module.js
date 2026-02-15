document.addEventListener("DOMContentLoaded", () => {

    let qrQueue = [];

    const nameInput = document.getElementById("item-name");
    const qtyInput = document.getElementById("item-quantity");
    const typeInput = document.getElementById("item-type");
    const addBtn = document.getElementById("add-to-queue-btn");
    const sendBtn = document.getElementById("send-request-btn");

    const tbody = document.getElementById("qr-queue-body");
    const qrResult = document.getElementById("qr-result");

    addBtn.addEventListener("click", async () => {
        const name = nameInput.value.trim();
        const qty = parseInt(qtyInput.value);
        const type = typeInput.value;

        if (!name || isNaN(qty) || qty <= 0) {
            alert("Please enter valid item name and quantity.");
            return;
        }

        const existingSerials = qrQueue.map(item => item.serial);

        try {
            const res = await fetch(`/serials/next/${qty}?exclude=${existingSerials.join(',')}`, {
                credentials: 'same-origin'
            });

            if (!res.ok) throw new Error("Server error");

            const data = await res.json();

            data.serials.forEach(serial => {
                qrQueue.push({ name, type, serial });
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
        qtyInput.value = "";
        typeInput.value = "qr";
        nameInput.focus();
    });

    function renderQueue() {
        tbody.innerHTML = "";

        const grouped = {};
        qrQueue.forEach(item => {
            const key = `${item.name}|${item.type}`;
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(item.serial);
        });

        Object.keys(grouped).forEach(key => {
            const [name, type] = key.split("|");
            const serials = grouped[key];

            const row = tbody.insertRow();
            row.insertCell(0).textContent = name;
            row.insertCell(1).textContent = serials.length;
            row.insertCell(2).textContent = type;

            const actionCell = row.insertCell(3);
            const btn = document.createElement("button");
            btn.textContent = "Remove";
            btn.onclick = () => {
                qrQueue = qrQueue.filter(item => !(item.name === name && item.type === type));
                renderQueue();
                renderCodes();
                updateSendButton();
            };
            actionCell.appendChild(btn);
        });
    }

    function renderCodes() {
        qrResult.innerHTML = "";

        qrQueue.forEach(item => {
            const box = document.createElement("div");
            box.className = "qr-box";

            if (item.type === "qr") {
                const top = document.createElement("div");
                top.style.textAlign = "center";
                top.style.fontWeight = "bold";
                top.textContent = item.name;

                const codeDiv = document.createElement("div");
                codeDiv.style.textAlign = "center";

                new QRCode(codeDiv, {
                    text: `${item.name}|${item.serial}`,
                    width: 120,
                    height: 120
                });

                const bottom = document.createElement("div");
                bottom.style.textAlign = "center";
                bottom.textContent = `${item.name} | ${item.serial}`;

                box.append(top, codeDiv, bottom);

            } else {
                const label = document.createElement("strong");
                label.textContent = item.name;

                const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");

                JsBarcode(svg, `${item.name}|${item.serial}`, {
                    format: "CODE128",
                    width: 2,
                    height: 80,
                    displayValue: true
                });

                box.append(label, svg);
            }

            qrResult.appendChild(box);
        });
    }

    function updateSendButton() {
        sendBtn.disabled = qrQueue.length === 0;
    }

    sendBtn.addEventListener("click", async () => {
    if (qrQueue.length === 0) return;

    try {
        const res = await fetch("/item-approval/request", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ items: qrQueue })
        });

        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch { data = null; }

        if (!res.ok) {
            Swal.fire({
                icon: 'error',
                title: 'Failed to Send Request',
                text: data?.message || 'Something went wrong!',
            });
            return;
        }

        // Success
        Swal.fire({
            icon: 'success',
            title: 'Request Sent',
            text: data?.message || 'Items sent for approval successfully!',
            timer: 2000,
            showConfirmButton: false
        });

        // Clear queue
        qrQueue = [];
        renderQueue();
        renderCodes();
        updateSendButton();

    } catch (err) {
        console.error("Send Request Error:", err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'Failed to send request',
        });
    }
});

    updateSendButton();
});
