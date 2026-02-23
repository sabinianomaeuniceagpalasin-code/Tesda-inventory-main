const dashboardConfigs = {
    inventory: {
        title: "List of All Items",
        headers: ["Item Name", "Classification", "Stock", "Location", "Status"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-total-items-and-equipment",
        targetSection: "inventory",
    },
    available: {
        title: "List of All Available Items",
        headers: ["Item name", "Classification", "Stock", "Location"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-available-items",
        targetSection: "inventory",
    },
    issued: {
        title: "List of Issued Items",
        headers: ["Item name", "Classification", "Issued to", "Date Issued", "Expected return"],
        buttonText: "View Issued Item Section",
        apiUrl: "/dashboard/get-issued-items",
        targetSection: "issued",
    },
    repair: {
        title: "Under Maintenance List",
        headers: ["Item name", "Classification", "Date sent for Repair", "Repair Status", "Location"],
        buttonText: "View Maintenance Section",
        apiUrl: "/dashboard/get-under-maintenance",
        targetSection: "reports",
    },
    lowstock: {
        title: "Low Stock Items",
        headers: ["Item name", "Classification", "Current Quantity", "Minimum Quantity", "Suggested Qty Order"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-low-stock-items",
        targetSection: "inventory",
    },
    missing: {
        title: "Missing Items",
        headers: ["Item name", "Classification", "Last Known Location", "Date Reported Missing"],
        buttonText: "View Inventory Section",
        apiUrl: "/dashboard/get-missing-items",
        targetSection: "inventory",
    },
};

let html5QrCode = null;