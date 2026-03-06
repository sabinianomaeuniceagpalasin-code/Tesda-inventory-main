document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.open-edit-lifespan').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('edit_item_name_hidden').value = this.dataset.itemName;
            document.getElementById('edit_description_hidden').value = this.dataset.description;
            document.getElementById('edit_item_name').value = this.dataset.itemName;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_lifespan').value = this.dataset.lifespan || 0;
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.open-edit-classification').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('edit_class_item_name_hidden').value = this.dataset.itemName;
            document.getElementById('edit_class_description_hidden').value = this.dataset.description;
            document.getElementById('edit_class_item_name').value = this.dataset.itemName;
            document.getElementById('edit_class_description').value = this.dataset.description;
            document.getElementById('edit_classification').value = this.dataset.classification || '';
        });
    });
});