async function submitForm(e) {
  e.preventDefault();

  const borrowerName = document.getElementById("borrowerName")?.value.trim();
  const issuedDate = document.getElementById("issuedDate")?.value;
  const returnDate = document.getElementById("returnDate")?.value;
  const formType = document.getElementById("form_type_input")?.value || "ICS";

  // ✅ serials come from hidden inputs added by renderScanned()
  const serials = Array.from(
    document.querySelectorAll('#scannedItems input[name="serials[]"]')
  ).map((el) => el.value.trim());

  if (!borrowerName || !issuedDate || serials.length === 0) {
    return Swal.fire({
      icon: "warning",
      title: "Incomplete Data",
      text: "Please enter borrower name, issued date, and scan at least one item.",
    });
  }

  const payload = {
    borrower_name: borrowerName,
    form_type: formType,
    issued_date: issuedDate,
    return_date: returnDate || null,
    serials: serials, // ✅ matches controller: $request->input('serials', [])
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

    if (!res.ok) {
      throw new Error(json?.message || "Failed to save form.");
    }

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
      throw new Error(json?.message || "Failed to save form.");
    }
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: err.message,
    });
  }
}