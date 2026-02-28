function openModal() {
  // Sample data (can come from API, form, or table row)
  const itemData = {
    serialNumber: "SN-20451",
    itemName: "Hydraulic Jack",
    reason: "Oil leakage and worn seals",
    lastUse: "2025-01-12"
  };

  document.getElementById("serialNumber").textContent = itemData.serialNumber;
  document.getElementById("itemName").textContent = itemData.itemName;
  document.getElementById("reason").textContent = itemData.reason;
  document.getElementById("lastUse").textContent = itemData.lastUse;

  document.getElementById("itemModal").style.display = "block";
}

function closeModal() {
  document.getElementById("itemModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("itemModal");
  if (event.target === modal) {
    closeModal();
  }
};