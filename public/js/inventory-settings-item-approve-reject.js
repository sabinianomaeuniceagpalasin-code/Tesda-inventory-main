document.addEventListener('DOMContentLoaded', function () {

    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        console.error('CSRF token meta tag not found.');
        return;
    }

    const csrfToken = meta.getAttribute('content');

    function handleRequest(buttonClass, action, successText) {

        document.querySelectorAll(buttonClass).forEach(button => {
            button.addEventListener('click', function () {

                const id = this.dataset.id;
                const buttonElement = this;

                Swal.fire({
                    title: action === 'approve'
                        ? 'Approve this request?'
                        : 'Reject this request?',
                    text: action === 'approve'
                        ? "This will move the request to archive."
                        : "This action cannot be undone.",
                    icon: action === 'approve' ? 'question' : 'warning',
                    showCancelButton: true,
                    confirmButtonText: action === 'approve'
                        ? 'Yes, Approve'
                        : 'Yes, Reject',
                    confirmButtonColor: action === 'approve'
                        ? '#198754'
                        : '#dc3545'
                }).then(result => {

                    if (!result.isConfirmed) return;

                    // Prevent double click
                    buttonElement.disabled = true;

                    fetch(`/item-approval/${id}/${action}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        if (!response.ok) {
                            const text = await response.text();
                            throw new Error(text);
                        }

                        return response.json();
                    })
                    .then(data => {

                        Swal.fire({
                            icon: 'success',
                            title: successText,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });

                    })
                    .catch(error => {
                        console.error(error);

                        Swal.fire(
                            'Error',
                            'Server error occurred. Check console.',
                            'error'
                        );

                        buttonElement.disabled = false;
                    });

                });

            });
        });
    }

    // Initialize actions
    handleRequest('.approve-item', 'approve', 'Approved!');
    handleRequest('.reject-item', 'reject', 'Rejected!');

});