{
    const propertyFilter = document.getElementById("propertyFilter");

    if (propertyFilter) {
        propertyFilter.addEventListener("input", function () {
            loadAvailableSerials(this.value.trim());
        });
    }
}