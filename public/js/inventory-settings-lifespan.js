document.addEventListener('DOMContentLoaded', function () {
    const editLifespanModal = document.getElementById('editLifespanModal');
    if (editLifespanModal) {
        editLifespanModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('edit_item_name_hidden').value = button.getAttribute('data-item-name') || '';
            document.getElementById('edit_description_hidden').value = button.getAttribute('data-description') || '';
            document.getElementById('edit_item_name').value = button.getAttribute('data-item-name') || '';
            document.getElementById('edit_description').value = button.getAttribute('data-description') || '';
            document.getElementById('edit_lifespan').value = button.getAttribute('data-lifespan') || 0;
        });
    }

    const editClassificationModal = document.getElementById('editClassificationModal');
    if (editClassificationModal) {
        editClassificationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('edit_class_item_name_hidden').value = button.getAttribute('data-item-name') || '';
            document.getElementById('edit_class_description_hidden').value = button.getAttribute('data-description') || '';
            document.getElementById('edit_class_item_name').value = button.getAttribute('data-item-name') || '';
            document.getElementById('edit_class_description').value = button.getAttribute('data-description') || '';
            document.getElementById('edit_classification').value = button.getAttribute('data-classification') || '';
        });
    }

    const editSourceOfFundModal = document.getElementById('editSourceOfFundModal');
    if (editSourceOfFundModal) {
        editSourceOfFundModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('edit_sof_item_name_hidden').value = button.getAttribute('data-item-name') || '';
            document.getElementById('edit_sof_description_hidden').value = button.getAttribute('data-description') || '';
            document.getElementById('edit_sof_item_name').value = button.getAttribute('data-item-name') || '';
            document.getElementById('edit_sof_description').value = button.getAttribute('data-description') || '';
            document.getElementById('edit_source_of_fund_value').value = button.getAttribute('data-source-of-fund') || '';
        });
    }
});