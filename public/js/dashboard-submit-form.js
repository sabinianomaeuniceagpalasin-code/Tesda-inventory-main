async function submitForm(e) {
    e.preventDefault();

    const studentName = document.getElementById("studentSearch").value.trim();
    const issuedDate = document.getElementById("issuedDate").value;
    const returnDate = document.getElementById("returnDate").value;
    const formType = document.getElementById("form_type_input").value;

    const checkedSerials = Array.from(
        document.querySelectorAll(".serial-checkbox:checked")
    ).map((cb) => cb.dataset.serial);

    if (!studentName || !issuedDate || checkedSerials.length === 0) {
        return Swal.fire({
            icon: "warning",
            title: "Incomplete Data",
            text: "Please select a student, date, and at least one serial number from the list.",
        });
    }

    const payload = {
        student_name: studentName,
        selected_serials: checkedSerials,
        form_type: formType,
        issued_date: issuedDate,
        return_date: returnDate
    };

    try {
        const res = await fetch("/issued/store", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Accept": "application/json",
            },
            body: JSON.stringify(payload),
        });

        const json = await res.json();

        if (json.success) {
            Swal.fire({
                icon: "success",
                title: "Saved!",
                text: `Reference No: ${json.reference_no}`,
            }).then(() => {
                closeAddFormModal();
                window.location.reload();
            });
        } else {
            throw new Error(json.message);
        }
    } catch (err) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: err.message
        });
    }
}