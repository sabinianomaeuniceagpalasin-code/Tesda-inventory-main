const dashboardConfigs = {
    inventory: {
        title: "List of All Items",
        headers: ["Serial Number", "Item Name", "Status"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-total-items-and-equipment",
        targetSection: "inventory",
    },
    available: {
        title: "List of All Available Items",
        headers: ["Serial Number", "Item name"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-available-items",
        targetSection: "inventory",
    },
    issued: {
        title: "List of Issued Items",
        headers: ["Serial Number", "Item name", "Borrower Name", "Issued By", "Form Type", "Reference Item"],
        buttonText: "View Issued Item Section",
        apiUrl: "/dashboard/get-issued-items",
        targetSection: "issued",
    },
    repair: {
        title: "Under Maintenance List",
        headers: ["Serial Number", "Item name", "Observation", "Borrower Name"],
        buttonText: "View Maintenance Section",
        apiUrl: "/dashboard/get-under-maintenance",
        targetSection: "reports",
    },
    lowstock: {
        title: "Low Stock Items",
        headers: ["Serial Number", "Item name", "Classification", "Current Quantity"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-low-stock-items",
        targetSection: "inventory",
    },
    missing: {
        title: "Missing Items",
        headers: ["Serial Number", "Item name", "Classification", "Borrower Name", "Issued Date", "Date Report Missing"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-missing-items",
        targetSection: "inventory",
    },
    unserviceable: {
        title: "Unserviceable Items",
        headers: ["Serial Number", "Item name", "Reason", "Borrower Name", "Reported By", "Reported"],
        buttonText: "View Unserviceable Section",
        apiUrl: "/dashboard/get-unserviceable-items",
        targetSection: "inventory",
    },
};

let html5QrCode = null;