function openAddFormModal(type) {
    const addModal = document.getElementById("addFormModal");
    const typeInput = document.getElementById("form_type_input");
    const title = document.getElementById("addFormTitle");

    if (addModal) {
        if (typeInput) typeInput.value = type;
        if (title) title.textContent = `${type} - New Form`;

        closeFormTypeModal();
        addModal.style.display = "flex";

        if (typeof loadAvailableSerialsForModal === "function") {
            loadAvailableSerialsForModal();
        }
    }
}

function closeAddFormModal() {
    const addModal = document.getElementById("addFormModal");
    if (addModal) addModal.style.display = "none";

    const form = document.getElementById("addForm");
    if (form) form.reset();

    const elementsToClear = ['studentSuggestion', 'serialScannerInput', 'scannedItems'];
    elementsToClear.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (el.tagName === 'INPUT') el.value = "";
            else el.innerHTML = "";
        }
    });
}

function closeViewFormModal() {
    const modal = document.getElementById("viewFormModal");
    if (modal) {
        modal.style.display = "none";
        const body = modal.querySelector(".modal-body");
        if (body) body.innerHTML = "";
    }
}

async function handleViewFormClick(e) {
  e.preventDefault();
  const row = e.target.closest("tr");
  const referenceNo = row.cells[1].textContent.trim();

  try {
    const res = await fetch(`/issued/view/${encodeURIComponent(referenceNo)}`, {
      headers: { "Accept": "application/json" }
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = await res.json();
    if (data.error) return alert(data.error);

    // ✅ Use new keys
    const borrowerName = data.borrower_name || "N/A";
    const processedBy = data.issued_by || "N/A";

    const grouped = {};
    data.details.forEach((d) => {
      if (!grouped[d.property_no]) {
        grouped[d.property_no] = {
          property_no: d.property_no,
          tool_name: d.tool_name,
          quantity: 0,
          unit_cost: Number(d.unit_cost) || 0,
          total_cost: 0,
          serials: [],
        };
      }
      grouped[d.property_no].quantity += 1;
      grouped[d.property_no].total_cost += Number(d.unit_cost) || 0;
      grouped[d.property_no].serials.push(d.serial_no);
    });

    

    let html = `

    <div class="no-print issued-meta">
    <div><strong>Issued To:</strong> <u>${borrowerName}</u></div>
    <div><strong>Processed By:</strong> <u>${processedBy}</u></div>
    </div>

    <p><strong>Reference No.:</strong> <u>${data.reference_no}</u></p>

      <table border="1" cellpadding="5" style="width:100%; margin-top:10px;">
        <thead>
          <tr>
            <th>Property No</th>
            <th>Article Name</th>
            <th>Qty</th>
            <th>Unit Cost</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          ${Object.values(grouped).map(item => `
            <tr>
              <td>${item.property_no}</td>
              <td>${item.tool_name}</td>
              <td>${item.quantity}</td>
              <td>${item.unit_cost.toFixed(2)}</td>
              <td>${item.total_cost.toFixed(2)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>

      <h4 style="margin-top:15px;">Serial Numbers</h4>
      <table border="1" cellpadding="5" style="width:100%;">
        <tbody>
          ${data.details.map(d => `<tr><td>${d.property_no}</td><td>${d.serial_no}</td></tr>`).join('')}
        </tbody>
      </table>
    `;

    const modal = document.getElementById("viewFormModal");

    // ✅ Save values for printing
    modal.dataset.formType = data.form_type || '';
    modal.dataset.borrowerName = borrowerName;
    modal.dataset.issuedBy = processedBy;

    modal.querySelector(".modal-body").innerHTML = html;
    modal.style.display = "flex";

  } catch (err) {
    alert("Failed to load: " + err.message);
  }
}