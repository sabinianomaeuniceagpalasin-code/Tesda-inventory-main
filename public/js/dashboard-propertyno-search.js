/* ============================
   AUTO-FILL PROPERTY NUMBER
============================ */
document.addEventListener('DOMContentLoaded', function() {
  const propertyInput = document.getElementById('property_no');
  if (!propertyInput) return;

  propertyInput.addEventListener('blur', async function() {
    const propertyNo = this.value.trim();
    if (!propertyNo) return;

    try {
      const res = await fetch(`/check-property-no/${encodeURIComponent(propertyNo)}`);
      const data = await res.json();

      if (data.exists) {
        document.getElementById('item_name').value = data.data.item_name;
        document.getElementById('classification').value = data.data.classification;
        document.getElementById('source_of_fund').value = data.data.source_of_fund;
        document.getElementById('unit_cost').value = data.data.unit_cost;
      } else {
        document.getElementById('item_name').value = '';
        document.getElementById('classification').value = '';
        document.getElementById('source_of_fund').value = '';
        document.getElementById('unit_cost').value = 0;
      }
    } catch(err) { console.error(err); }
  });
});

