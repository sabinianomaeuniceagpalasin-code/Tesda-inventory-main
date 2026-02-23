const modals = {
    addItem: document.getElementById("addItemModal"),
    formType: document.getElementById("formTypeModal"),
    addForm: document.getElementById("addFormModal"),
};

document.getElementById("addItemBtn")?.addEventListener("click", () => {
    modals.addItem.style.display = "flex";
});

document.getElementById("closeModal")?.addEventListener("click", () => {
    modals.addItem.style.display = "none";
});

function openFormTypeModal() {
    const typeModal = document.getElementById("formTypeModal");
    if (typeModal) typeModal.style.display = "flex";
}

function closeFormTypeModal() {
    const typeModal = document.getElementById("formTypeModal");
    if (typeModal) typeModal.style.display = "none";
}

window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal-overlay")) {
        e.target.style.display = "none";
    }
});

function closeUsageHistory() {
    const modal = document.getElementById("usageHistoryModal");
    if (modal) modal.style.display = "none";
}