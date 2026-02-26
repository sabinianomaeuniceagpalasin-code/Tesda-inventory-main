<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Settings - TESDA</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="inventory-settings.css">
</head>

<body>
    <div class="container-fluid px-4 py-3">
        <!-- Header -->
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('dashboard') }}" class="btn btn-link text-dark p-0 me-3">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
            <h2 class="mb-0">Inventory Settings</h2>
            <div class="ms-auto d-flex gap-3">
                <a href="dashboard.html" class="text-dark"><i class="bi bi-house-fill fs-5"></i></a>
                <a href="notifications.html" class="text-dark"><i class="bi bi-bell-fill fs-5"></i></a>
                <a href="profile.html" class="text-dark"><i class="bi bi-person-circle fs-5"></i></a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-md-4">
                <!-- General Settings Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">General Settings</h5>

                        <!-- Inventory Year Cycle -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Inventory / Year Cycle</label>
                            <select class="form-select" name="year_cycle" id="yearCycle">
                                <option value="2025" selected>2025</option>
                                <option value="2024">2024</option>
                                <option value="2026">2026</option>
                            </select>
                        </div>

                        <!-- Enable Notifications -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <label class="form-label fw-semibold mb-0">Enable Notifications</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableNotifications" checked>
                            </div>
                        </div>

                        <!-- Item Lifespan Limits -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">Item Lifespan Limits</label>
                                <a href="#" class="text-primary small">View all</a>
                            </div>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td>Laptop</td>
                                        <td class="text-end">5</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-link p-0 me-2"><i
                                                    class="bi bi-pencil text-primary"></i></button>
                                            <button class="btn btn-sm btn-link p-0"><i
                                                    class="bi bi-trash text-danger"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Printer</td>
                                        <td class="text-end">8</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-link p-0 me-2"><i
                                                    class="bi bi-pencil text-primary"></i></button>
                                            <button class="btn btn-sm btn-link p-0"><i
                                                    class="bi bi-trash text-danger"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Scanner</td>
                                        <td class="text-end">7</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-link p-0 me-2"><i
                                                    class="bi bi-pencil text-primary"></i></button>
                                            <button class="btn btn-sm btn-link p-0"><i
                                                    class="bi bi-trash text-danger"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-circle me-1"></i> Add new item
                            </button>
                        </div>

                        <!-- Default Settings -->
                        <h6 class="fw-bold mb-3 mt-4">Default Settings</h6>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Date Format</label>
                            <select class="form-select" name="date_format" id="dateFormat">
                                <option value="MM/DD/YYYY" selected>MM/DD/YYYY</option>
                                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label fw-semibold mb-0">Auto-Calculated Total Cost</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoCalculate" checked>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category & Classification Management Card -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Category & Classification Management</h5>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Manage Item Classification</label>
                            <div class="list-group mb-2">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Tools</span>
                                    <div>
                                        <button class="btn btn-sm btn-link p-0 me-2"><i
                                                class="bi bi-pencil text-primary"></i></button>
                                        <button class="btn btn-sm btn-link p-0"><i
                                                class="bi bi-trash text-danger"></i></button>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Equipment</span>
                                    <div>
                                        <button class="btn btn-sm btn-link p-0 me-2"><i
                                                class="bi bi-pencil text-primary"></i></button>
                                        <button class="btn btn-sm btn-link p-0"><i
                                                class="bi bi-trash text-danger"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-plus-circle me-1"></i> Add new classification
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-8">
                <!-- User Access & Roles Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">User Access & Roles</h5>

                        <div class="row g-3">
                            <!-- Admin Role -->
                            <div class="col-md-4">
                                <div class="role-card border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3">Admin</h6>
                                    <ul class="small mb-3 ps-3">
                                        <li>Approve or reject new accounts</li>
                                        <li>Manage all users</li>
                                        <li>Access all modules</li>
                                        <li>Modify item in the inventory</li>
                                        <li>View and generate ICS/PAR forms</li>
                                        <li>View audit logs</li>
                                        <li>Generate and export all reports</li>
                                        <li>Manage system settings</li>
                                    </ul>
                                    <h6 class="fw-semibold small mb-2">Access Scope</h6>
                                    <div class="access-badge bg-light p-2 rounded text-center small">All Modules</div>
                                </div>
                            </div>

                            <!-- Property Custodian Role -->
                            <div class="col-md-4">
                                <div class="role-card border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3">Property Custodian</h6>
                                    <ul class="small mb-3 ps-3">
                                        <li>View dashboard summary</li>
                                        <li>Add and edit inventory items</li>
                                        <li>Mark items</li>
                                        <li>Generate ICS/PAR forms</li>
                                        <li>Update repair schedules</li>
                                        <li>Generate and export reports related to inventory and issued items</li>
                                    </ul>
                                    <h6 class="fw-semibold small mb-2">Access Scope</h6>
                                    <div class="small">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pc1" checked>
                                            <label class="form-check-label" for="pc1">View dashboard</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pc2" checked>
                                            <label class="form-check-label" for="pc2">Manage inventory items</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pc3" checked>
                                            <label class="form-check-label" for="pc3">Mark items</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pc4" checked>
                                            <label class="form-check-label" for="pc4">Generate ICS/PAR forms</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pc5" checked>
                                            <label class="form-check-label" for="pc5">Update repair status</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pc6" checked>
                                            <label class="form-check-label" for="pc6">Export inventory reports</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Regular Employee Role -->
                            <div class="col-md-4">
                                <div class="role-card border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3">Regular Employee</h6>
                                    <ul class="small mb-3 ps-3">
                                        <li>View dashboard summary</li>
                                        <li>View available items in Invento (read-only)</li>
                                        <li>View items issued to them</li>
                                        <li>View own ICS/PAR records</li>
                                        <li>Download or print own form cc</li>
                                    </ul>
                                    <h6 class="fw-semibold small mb-2">Access Scope</h6>
                                    <div class="small">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="re1" checked>
                                            <label class="form-check-label" for="re1">View dashboard summary</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="re2" checked>
                                            <label class="form-check-label" for="re2">View inventory (read-only)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="re3" checked>
                                            <label class="form-check-label" for="re3">View issued items</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="re4" checked>
                                            <label class="form-check-label" for="re4">View ICS/PAR records</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="re5" checked>
                                            <label class="form-check-label" for="re5">Download or print form copy</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Account Requests Card -->
                <div class="card shadow-sm mx-auto mb-4" style="max-width: 900px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4 text-center">User Account Requests</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Role Requested</th>
                                        <th>Email Address</th>
                                        <th>Date Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        @if($user->is_verified == 1 && $user->is_approved == 0)
                                        <tr>
                                            <td>{{ $user->full_name }}</td>
                                            <td>{{ $user->role }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->created_at->format('F d, Y') }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center align-items-center gap-2">

                                                    <form action="{{ route('user.approve', $user->user_id) }}" method="POST" class="approve-user-form">
                                                        @csrf
                                                        <button type="button" class="btn action-btn btn-approve approve-user">
                                                            <i>✔</i>
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('user.reject', $user->user_id) }}" method="POST" class="reject-user-form">
                                                        @csrf
                                                        <button type="button" class="btn action-btn btn-reject reject-user">
                                                            <i>✖</i>
                                                        </button>
                                                    </form>

                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Item Approval Button -->
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemApprovalModal">
                        <i class="bi bi-qr-code-scan me-1"></i> Item Approval
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ITEM APPROVAL MODAL -->
<div class="modal fade" id="itemApprovalModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Generation Requests Pending Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="approvalTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qrRequests" type="button">
                        QR Code Requests
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="barcode-tab" data-bs-toggle="tab" data-bs-target="#barcodeRequests" type="button">
                        Bar Code Requests
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archiveRequests" type="button">
                        Item Approval Archive
                    </button>
                </li>
            </ul>

            <!-- Modal Body / Tab Content -->
            <div class="modal-body px-4">
                <div class="tab-content mt-3">

                    <!-- QR Code Requests Tab -->
                    <div class="tab-pane fade show active" id="qrRequests" role="tabpanel">
                        <div class="alert alert-warning small mb-3">
                            {{ $itemRequests->where('request_type', 'qr')->count() }} new QR request(s) pending approval
                        </div>

                        <!-- QR FILTERS -->
                            <div class="row g-3 mb-3 align-items-end">

                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">From</label>
                                    <input type="date" class="form-control form-control-sm" id="qrFromDate">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">To</label>
                                    <input type="date" class="form-control form-control-sm" id="qrToDate">
                                </div>

                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-sm w-100" id="qrResetBtn">
                                      <i class="bi bi-arrow-counterclockwise"></i>Reset
                                    </button>
                                </div>

                            </div>


                        @foreach ($itemRequests->where('request_type', 'qr') as $request)
                            @php
                                $dateValue = \Carbon\Carbon::parse($request->requested_at)->format('Y-m-d');
                            @endphp

                            <div class="approval-card mb-3 qr-card" data-date="{{ $dateValue }}">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="fw-semibold">Item: {{ $request->item_name }}</div>
                                        @php
                                            $serials = array_map('trim', explode(',', $request->serial_number));
                                            $firstSerial = $serials[0] ?? '';
                                            $lastSerial  = end($serials);
                                        @endphp
                                        <div class="text-muted small">
                                            SN: {{ count($serials) > 1 ? "$firstSerial - $lastSerial" : $firstSerial }}
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="fw-semibold">Qty: {{ $request->quantity }}</div>
                                        <div class="text-muted small">
                                            {{ \Carbon\Carbon::parse($request->requested_at)->format('d M Y') }}
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center d-flex justify-content-center gap-2 flex-wrap">
                                        <!-- View Button -->
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#qrModal-{{ $request->request_id }}">
                                            View QR
                                        </button>

                                        <!-- Approve Button -->
                                        <button type="button"
                                                class="btn btn-success btn-sm approve-item"
                                                data-id="{{ $request->request_id }}">
                                            Approve
                                        </button>

                                        <!-- Reject Button -->
                                        <button type="button"
                                                class="btn btn-danger btn-sm reject-item"
                                                data-id="{{ $request->request_id }}">
                                            Decline
                                        </button>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Barcode Requests Tab -->
                    <div class="tab-pane fade" id="barcodeRequests" role="tabpanel">
                        <div class="alert alert-warning small mb-3">
                            {{ $itemRequests->where('request_type', 'barcode')->count() }} new Barcode request(s) pending approval
                        </div>

                        <!-- BARCODE FILTERS -->
                    <div class="row g-3 mb-3 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" class="form-control form-control-sm" id="barcodeFromDate">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" class="form-control form-control-sm" id="barcodeToDate">
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary btn-sm w-100" id="barcodeResetBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>

                    </div>


                        @foreach ($itemRequests->where('request_type', 'barcode') as $request)
                            @php
                                $dateValue = \Carbon\Carbon::parse($request->requested_at)->format('Y-m-d');
                            @endphp

                            <div class="approval-card mb-3 qr-card" data-date="{{ $dateValue }}">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="fw-semibold">Item: {{ $request->item_name }}</div>
                                        @php
                                            $serials = array_map('trim', explode(',', $request->serial_number));
                                            $firstSerial = $serials[0] ?? '';
                                            $lastSerial  = end($serials);
                                        @endphp
                                        <div class="text-muted small">
                                            SN: {{ count($serials) > 1 ? "$firstSerial - $lastSerial" : $firstSerial }}
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="fw-semibold">Qty: {{ $request->quantity }}</div>
                                        <div class="text-muted small">
                                            {{ \Carbon\Carbon::parse($request->requested_at)->format('d M Y') }}
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center d-flex justify-content-center gap-2 flex-wrap">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#barcodeModal-{{ $request->request_id }}">
                                            View Barcode
                                        </button>
                                        <form method="POST" action="{{ route('item.approve', $request->request_id) }}">
                                            @csrf
                                            <button class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('item.reject', $request->request_id) }}">
                                            @csrf
                                            <button class="btn btn-danger btn-sm">Decline</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Archive Tab -->
                <div class="tab-pane fade" id="archiveRequests" role="tabpanel">

                    <!-- FILTER SECTION -->
                <div class="row g-3 mb-3 mt-2 align-items-end">

                    <!-- Status Dropdown -->
                    <div class="col-md-3" style="margin-top: -50px;">
                        <label class="form-label fw-semibold small">Filter by Status</label>
                        <select class="form-select form-select-sm" id="archiveStatusFilter">
                            <option value="all">Show All</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <!-- Specific Date -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Specific Date</label>
                        <input type="date" class="form-control form-control-sm" id="archiveSpecificDate">
                    </div>

                    <!-- From Date -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">From</label>
                        <input type="date" class="form-control form-control-sm" id="archiveFromDate">
                    </div>

                    <!-- To Date -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">To</label>
                        <input type="date" class="form-control form-control-sm" id="archiveToDate">
                    </div>

                    <!-- Reset Button -->
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-sm w-100" id="archiveResetBtn">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                        </button>
                    </div>

                </div>

                    @if($archiveRequests->count() > 0)
                        <table class="table table-bordered table-striped mt-2" id="archiveTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Serial Number</th>
                                    <th>Quantity</th>
                                    <th>Request Type</th>
                                    <th>Requested Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($archiveRequests as $request)
                                    @php
                                        $serials = array_map('trim', explode(',', $request->serial_number));
                                        $first = $serials[0] ?? '';
                                        $last = end($serials);
                                        $serialDisplay = count($serials) > 1 ? "$first - $last" : $first;
                                        $dateValue = \Carbon\Carbon::parse($request->requested_at)->format('Y-m-d');
                                    @endphp
                                    <tr 
                                        data-status="{{ $request->status }}"
                                        data-date="{{ $dateValue }}"
                                    >
                                        <td>{{ $request->item_name }}</td>
                                        <td>{{ $serialDisplay }}</td>
                                        <td>{{ $request->quantity }}</td>
                                        <td>{{ ucfirst($request->request_type) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($request->requested_at)->format('d M Y') }}</td>
                                        <td>
                                            @if($request->status == 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($request->status == 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($request->status == 'approved')
                                                <button 
                                                    class="btn btn-sm btn-primary openPrintModal"
                                                    data-item="{{ $request->item_name }}"
                                                    data-serials="{{ $request->serial_number }}"
                                                    data-type="{{ $request->request_type }}"
                                                >
                                                    <i class="bi bi-printer"></i> Print
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center text-muted mt-3">No requests in archive.</div>
                    @endif
                </div>


                </div> <!-- tab-content -->
            </div> <!-- modal-body -->
        </div>
    </div>
</div>



<!-- QR Modals -->
@foreach ($itemRequests->where('request_type', 'qr') as $request)
    @php
        preg_match('/^(.+?)(\d+)$/', $request->serial_number, $m);
        $prefix = $m[1];
        $start  = (int)$m[2];
    @endphp
    <div class="modal fade" id="qrModal-{{ $request->request_id }}" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Codes — {{ $request->item_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex flex-wrap gap-3">
                   @php
                    $serials = array_map('trim', explode(',', $request->serial_number));
                    @endphp
                    <div class="modal-body d-flex flex-wrap gap-3">
                        @foreach ($serials as $serial)
                            <div class="text-center border rounded p-2" style="width:120px">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($serial) }}" alt="QR {{ $serial }}">
                                <div class="small mt-1">{{ $serial }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            onclick="returnToApproval('qrModal-{{ $request->request_id }}', 'qrRequests')">
                        ← Back
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Barcode Modals -->
@foreach ($itemRequests->where('request_type', 'barcode') as $request)
    @php
        preg_match('/^(.+?)(\d+)$/', $request->serial_number, $m);
        $prefix = $m[1];
        $start  = (int)$m[2];
    @endphp
    <div class="modal fade" id="barcodeModal-{{ $request->request_id }}" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Barcodes — {{ $request->item_name }}</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex flex-wrap gap-3">
                    @php
                        $serials = array_map('trim', explode(',', $request->serial_number));
                    @endphp
                    <div class="modal-body d-flex flex-wrap gap-3">
                        @foreach ($serials as $serial)
                            <div class="text-center border rounded p-2" style="width:120px">
                                <img src="https://barcode.tec-it.com/barcode.ashx?data={{ urlencode($serial) }}&code=Code128&multiplebarcodes=false&translate-esc=false" 
                                    alt="Barcode {{ $serial }}"
                                    style="max-width:100%; height:auto;">
                                <div class="small mt-1">{{ $serial }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            onclick="returnToApproval('barcodeModal-{{ $request->request_id }}', 'barcodeRequests')">
                        ← Back
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="printPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">QR Print Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="printArea" class="print-bond p-3">
                    <div class="d-flex flex-wrap gap-3" id="qrContainer"></div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success" onclick="printPreview()">Print</button>
            </div>

        </div>
    </div>
</div>

    <!-- SWEET ALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="inventory-settings.js"></script>
    <script src="{{ asset('js/item-approval-request-modal.js') }}"></script>
    <script src="{{ asset('js/inventory-settings-user-approval.js') }}"></script>
    <script src="{{ asset('js/inventory-settings-item-approve-reject.js') }}"></script>
    <script src="{{ asset('js/archive-filters.js') }}"></script>
    <script src="{{ asset('js/inventory-settings-print.js') }}"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>



</body>
</html>