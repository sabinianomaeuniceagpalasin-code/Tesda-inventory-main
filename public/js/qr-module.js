document.addEventListener("DOMContentLoaded", () => {

    let qrQueue = [];

    const nameInput = document.getElementById("item-name");
    const deptInput = document.getElementById("item-department"); // ✅ added
    const qtyInput = document.getElementById("item-quantity");
    const typeInput = document.getElementById("item-type");
    const addBtn = document.getElementById("add-to-queue-btn");
    const sendBtn = document.getElementById("send-request-btn");

    const tbody = document.getElementById("qr-queue-body");
    const qrResult = document.getElementById("qr-result");

    addBtn.addEventListener("click", async () => {
        const name = nameInput.value.trim();
        const department = deptInput ? deptInput.value : ""; // ✅ added
        const qty = parseInt(qtyInput.value);
        const type = typeInput.value;

        if (!name || isNaN(qty) || qty <= 0) {
            alert("Please enter valid item name and quantity.");
            return;
        }

        if (!department) { // ✅ added
            alert("Please select a department.");
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
                // ✅ store department per item
                qrQueue.push({ name, type, serial, department });
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
        // keep department selected (recommended)
        nameInput.focus();
    });

    function renderQueue() {
        tbody.innerHTML = "";

        const grouped = {};
        qrQueue.forEach(item => {
            // ✅ group also by department (so it doesn’t mix)
            const key = `${item.name}|${item.type}|${item.department}`;
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(item.serial);
        });

        Object.keys(grouped).forEach(key => {
            const [name, type, department] = key.split("|");
            const serials = grouped[key];

            const row = tbody.insertRow();
            row.insertCell(0).textContent = name;
            row.insertCell(1).textContent = department;
            row.insertCell(2).textContent = serials.length;
            row.insertCell(3).textContent = type;

            // If you also want to SHOW department in table,
            // add a new <th>Department</th and then:
            // row.insertCell(3).textContent = department;

            const actionCell = row.insertCell(4);
            const btn = document.createElement("button");
            btn.textContent = "Remove";
            btn.onclick = () => {
                qrQueue = qrQueue.filter(item => !(
                    item.name === name &&
                    item.type === type &&
                    item.department === department
                ));
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
                    displayValue: false
                });

                box.append(label, svg);
            }

            qrResult.appendChild(box);
        });
    }

    function updateSendButton() {
        sendBtn.disabled = qrQueue.length === 0;
    }

    // ✅ THIS is the exact place the department must be included
    sendBtn.addEventListener("click", async () => {
        if (qrQueue.length === 0) return;

        // optional: validate again before sending
        const missingDept = qrQueue.some(x => !x.department);
        if (missingDept) {
            Swal.fire({
                icon: "warning",
                title: "Missing Department",
                text: "Please select a department before sending."
            });
            return;
        }

        try {
            const res = await fetch("/item-approval/request", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                // ✅ department included because it is inside qrQueue items now
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

            Swal.fire({
                icon: 'success',
                title: 'Request Sent',
                text: data?.message || 'Items sent for approval successfully!',
                timer: 2000,
                showConfirmButton: false
            });

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