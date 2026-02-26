  const scanned = new Map(); // serial_no -> { serial_no, item_name, unit_cost }

  function showScanMessage(type, text){
    const el = document.getElementById('scanMessage');
    if (!el) return;
    el.className = 'scan-message ' + (type === 'ok' ? 'ok' : 'err');
    el.textContent = text;
    el.style.display = 'block';
    clearTimeout(window.__scanMsgTimer);
    window.__scanMsgTimer = setTimeout(() => { el.style.display = 'none'; }, 3000);
  }

  function renderScanned(){
    const box = document.getElementById('scannedItems');
    if (!box) return;
    box.innerHTML = '';

    if(scanned.size === 0){
      box.innerHTML = `<span style="color:#777;">No scanned items yet.</span>`;
      return;
    }

    scanned.forEach((item) => {
      const chip = document.createElement('div');
      chip.className = 'scanned-chip';
      chip.innerHTML = `
        <div class="meta">
          <strong>${item.serial_no}</strong>
          <small>${item.item_name || 'Item'} — ₱${Number(item.unit_cost || 0).toLocaleString()}</small>
        </div>
        <span class="remove" title="Remove" onclick="removeScanned('${item.serial_no}')">&times;</span>
        <input type="hidden" name="serials[]" value="${item.serial_no}">
      `;
      box.appendChild(chip);
    });
  }

  function removeScanned(serial){
    scanned.delete(serial);
    renderScanned();
  }

  async function handleScanAdd(){
    const input = document.getElementById('serialScannerInput');
    let code = (input?.value || '').trim();
    if(!code) return;

    // prevent duplicates
    if(scanned.has(code)){
      showScanMessage('err', 'Already scanned.');
      input.value = '';
      return;
    }

    const formType = document.getElementById('form_type_input')?.value || 'ICS';

    try{
      // ✅ WEB ROUTE (NOT /api)
      const res = await fetch(
        `/items/scan?code=${encodeURIComponent(code)}&form_type=${encodeURIComponent(formType)}`,
        { headers: { 'Accept': 'application/json' } }
      );

      // Handle case where Laravel redirects to HTML (not logged in)
      const contentType = res.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        showScanMessage('err', 'Not logged in or route returned HTML. Please login.');
        return;
      }

      const data = await res.json();

      if(!res.ok){
        showScanMessage('err', data?.message || 'Scan failed.');
        input.value = '';
        return;
      }

      scanned.set(data.serial_no, data);
      renderScanned();
      showScanMessage('ok', `Added: ${data.item_name} (${data.serial_no})`);
      input.value = '';

    } catch(e){
      showScanMessage('err', 'Network error. Please try again.');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('serialScannerInput');
    if(input){
      input.addEventListener('keydown', (e) => {
        if(e.key === 'Enter'){
          e.preventDefault();
          handleScanAdd();
        }
      });
    }
    renderScanned();

    // Optional: when switching ICS/PAR, clear scanned items so you can't mix rules
    const icsBtn = document.getElementById('chooseIcs');
    const parBtn = document.getElementById('choosePar');

    if (icsBtn) icsBtn.addEventListener('click', () => {
      document.getElementById('form_type_input').value = 'ICS';
      scanned.clear();
      renderScanned();
      showScanMessage('ok', 'Form type set to ICS. Scanned items cleared.');
    });

    if (parBtn) parBtn.addEventListener('click', () => {
      document.getElementById('form_type_input').value = 'PAR';
      scanned.clear();
      renderScanned();
      showScanMessage('ok', 'Form type set to PAR. Scanned items cleared.');
    });
  });