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

                        <!-- Item Lifespan Limits -->
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label fw-semibold mb-0">Item Lifespan Limits</label>
        <a href="#" class="text-primary small" data-bs-toggle="modal" data-bs-target="#viewAllLifespanModal">
            View all
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle lifespan-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th class="text-center">Lifespan</th>
                    <th class="text-center">Edit</th>
                    <th class="text-center">Delete</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lifespanPreview as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="text-center">{{ $item->expected_life_years ?? 0 }}</td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="btn btn-sm btn-link p-0 open-edit-lifespan"
                                data-bs-toggle="modal"
                                data-bs-target="#editLifespanModal"
                                data-item-name="{{ $item->item_name }}"
                                data-description="{{ $item->description }}"
                                data-lifespan="{{ $item->expected_life_years ?? 0 }}"
                            >
                                <i class="bi bi-pencil text-primary"></i>
                            </button>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('inventory.settings.lifespan.delete') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->item_id }}">
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-link p-0"
                                    onclick="return confirm('Reset lifespan to 0 for this item?')"
                                >
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

                        

                        

                        
                    </div>
                </div>

                <!-- Category & Classification Management Card -->
<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-4">Category & Classification Management</h5>

        <!-- Manage Item Classification -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">Manage Item Classification</label>
                <a href="#" class="text-primary small" data-bs-toggle="modal" data-bs-target="#viewAllClassificationModal">
                    View all
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle classification-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th class="text-center">Classification</th>
                            <th class="text-center">Edit</th>
                            <th class="text-center">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classificationsPreview as $item)
                            <tr>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->classification ?: 'Unclassified' }}</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link p-0 open-edit-classification"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editClassificationModal"
                                        data-item-name="{{ $item->item_name }}"
                                        data-description="{{ $item->description }}"
                                        data-classification="{{ $item->classification ?: '' }}"
                                    >
                                        <i class="bi bi-pencil text-primary"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('inventory.settings.classification.delete') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                        <input type="hidden" name="description" value="{{ $item->description }}">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-link p-0"
                                            onclick="return confirm('Reset classification for this item group?')"
                                        >
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Manage Source of Fund -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">Manage Source of Fund</label>
                <a href="#" class="text-primary small" data-bs-toggle="modal" data-bs-target="#viewAllSourceOfFundModal">
                    View all
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle classification-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th class="text-center">Source of Fund</th>
                            <th class="text-center">Edit</th>
                            <th class="text-center">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sourceOfFundsPreview as $item)
                            <tr>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->source_of_fund ?: 'Not set' }}</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link p-0 open-edit-source-of-fund"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editSourceOfFundModal"
                                        data-item-name="{{ $item->item_name }}"
                                        data-description="{{ $item->description }}"
                                        data-source-of-fund="{{ $item->source_of_fund ?: '' }}"
                                    >
                                        <i class="bi bi-pencil text-primary"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('inventory.settings.source-of-fund.delete') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                        <input type="hidden" name="description" value="{{ $item->description }}">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-link p-0"
                                            onclick="return confirm('Reset source of fund for this item group?')"
                                        >
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                                        <th>First Name</th>
                                        <th>Last Name</th>
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
                                            <td>{{ $user->first_name }}</td>
                                            <td>{{ $user->last_name }}</td>
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
                <h5 class="modal-title fw-bold">Generation Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            @php
                // ✅ Group pending rows by batch_id
                $pendingBatches = $itemRequests->groupBy('batch_id');

                // ✅ Group archive rows by batch_id (each batch should have same status)
                $archiveBatches = $archiveRequests->groupBy('batch_id');
            @endphp

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="approvalTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active"
                            id="pending-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#pendingBatchesTab"
                            type="button">
                        Item Requests
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link"
                            id="archive-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#archiveBatchesTab"
                            type="button">
                        Archive
                    </button>
                </li>
            </ul>

            <!-- Modal Body -->
            <div class="modal-body px-4">
                <div class="tab-content mt-3">

                    <!-- =========================
                         ITEM REQUESTS (PENDING)
                    ========================== -->
                    <div class="tab-pane fade show active" id="pendingBatchesTab" role="tabpanel">

                        <div class="alert alert-warning small mb-3">
                            {{ $pendingBatches->count() }} batch(es) pending approval
                        </div>

                        @if($pendingBatches->count() === 0)
                            <div class="text-center text-muted py-4">No pending batches.</div>
                        @else
                            @foreach($pendingBatches as $batchId => $rows)
                                @php
                                    $first = $rows->first();
                                    $totalQty = $rows->sum('quantity');
                                    $types = $rows->pluck('request_type')->unique()->map(fn($t) => strtoupper($t))->implode(', ');
                                    $dateValue = \Carbon\Carbon::parse($first->requested_at)->format('Y-m-d');
                                @endphp

                                <div class="approval-card mb-3" data-date="{{ $dateValue }}">
                                    <div class="row approval-row">
                                        <div class="col-md-4 info-col">
                                            <div class="fw-semibold">Batch #: {{ $batchId }}</div>
                                            <div class="text-muted small">
                                                Types: {{ $types }} • Lines: {{ $rows->count() }}
                                            </div>
                                        </div>

                                        <div class="col-md-4 total-qty-col">
                                            <div class="total-qty-box">
                                                <div class="fw-semibold">Total Qty: {{ $totalQty }}</div>
                                                <div class="text-muted small">
                                                    {{ \Carbon\Carbon::parse($first->requested_at)->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4 action-col">
                                            <button type="button"
                                                    class="btn btn-success btn-sm approve-batch"
                                                    data-batch="{{ $batchId }}">
                                                Approve
                                            </button>

                                            <button type="button"
                                                    class="btn btn-danger btn-sm reject-batch"
                                                    data-batch="{{ $batchId }}">
                                                Decline
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- =========================
                                     BATCH DETAILS MODAL
                                ========================== -->
                                <div class="modal fade" id="batchModal-{{ $batchId }}" tabindex="-1">
                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Batch #{{ $batchId }} — Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle text-center">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Item Name</th>
                                                                <th>Serial Range</th>
                                                                <th>Qty</th>
                                                                <th>Type</th>
                                                                <th>Requested</th>
                                                                <th>Preview</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($rows as $r)
                                                                @php
                                                                    $serials = array_map('trim', explode(',', $r->serial_number));
                                                                    $firstSerial = $serials[0] ?? '';
                                                                    $lastSerial  = end($serials);
                                                                    $serialDisplay = count($serials) > 1 ? "$firstSerial - $lastSerial" : $firstSerial;
                                                                    $collapseId = "codes-{$batchId}-{$r->request_id}";
                                                                @endphp
                                                                <tr>
                                                                    <td class="text-start">{{ $r->item_name }}</td>
                                                                    <td class="text-start">{{ $serialDisplay }}</td>
                                                                    <td>{{ $r->quantity }}</td>
                                                                    <td>{{ strtoupper($r->request_type) }}</td>
                                                                    <td>{{ \Carbon\Carbon::parse($r->requested_at)->format('d M Y') }}</td>
                                                                    <td>
                                                                        <button class="btn btn-outline-secondary btn-sm"
                                                                                type="button"
                                                                                data-bs-toggle="collapse"
                                                                                data-bs-target="#{{ $collapseId }}">
                                                                            View Codes
                                                                        </button>
                                                                    </td>
                                                                </tr>

                                                                <tr class="collapse" id="{{ $collapseId }}">
                                                                    <td colspan="6">
                                                                        <div class="d-flex flex-wrap gap-3 justify-content-start">
                                                                            @foreach($serials as $serial)
                                                                                <div class="text-center border rounded p-2" style="width:130px">
                                                                                    @if($r->request_type === 'qr')
                                                                                        <img
                                                                                            src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($serial) }}"
                                                                                            alt="QR {{ $serial }}">
                                                                                    @else
                                                                                        <img
                                                                                            src="https://barcode.tec-it.com/barcode.ashx?data={{ urlencode($serial) }}&code=Code128&multiplebarcodes=false&translate-esc=false"
                                                                                            alt="Barcode {{ $serial }}"
                                                                                            style="max-width:100%; height:auto;">
                                                                                    @endif
                                                                                    <div class="small mt-1">{{ $serial }}</div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            @endforeach
                        @endif
                    </div>


                    <!-- =========================
                         ARCHIVE (APPROVED/REJECTED/PENDING)
                    ========================== -->
                    <div class="tab-pane fade" id="archiveBatchesTab" role="tabpanel">

                        <!-- FILTER SECTION (kept from your archive) -->
                        <div class="row g-3 mb-3 mt-2 align-items-end">

                            <div class="col-md-3" style="margin-top: -50px;">
                                <label class="form-label fw-semibold small">Filter by Status</label>
                                <select class="form-select form-select-sm" id="archiveStatusFilter">
                                    <option value="all">Show All</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Specific Date</label>
                                <input type="date" class="form-control form-control-sm" id="archiveSpecificDate">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">From</label>
                                <input type="date" class="form-control form-control-sm" id="archiveFromDate">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">To</label>
                                <input type="date" class="form-control form-control-sm" id="archiveToDate">
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary btn-sm w-100" id="archiveResetBtn">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                                </button>
                            </div>
                        </div>

                        @if($archiveBatches->count() > 0)
                            <table class="table table-bordered table-striped mt-2" id="archiveTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Batch #</th>
                                        <th>Item Names</th>
                                        <th>Types</th>
                                        <th>Total Qty</th>
                                        <th>Requested Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                <tbody>
                                    @foreach($archiveBatches as $batchId => $rows)
                                        @php
                                            $first = $rows->first();

                                            // ✅ combine item names inside the same batch
                                            $itemNames = $rows->pluck('item_name')->unique()->implode(', ');

                                            $totalQty = $rows->sum('quantity');

                                            $types = $rows->pluck('request_type')
                                                        ->unique()
                                                        ->map(fn($t) => strtoupper($t))
                                                        ->implode(', ');

                                            $status = $first->status;

                                            $dateValue = \Carbon\Carbon::parse($first->requested_at)->format('Y-m-d');
                                        @endphp

                                        <tr data-status="{{ $status }}" data-date="{{ $dateValue }}">
                                            <td>{{ $batchId }}</td>

                                            <td class="text-start">
                                                {{ $itemNames }}
                                            </td>

                                            <td>{{ $types }}</td>

                                            <td>{{ $totalQty }}</td>

                                            <td>{{ \Carbon\Carbon::parse($first->requested_at)->format('d M Y') }}</td>

                                            <td>
                                                @if($status == 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($status == 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>

                                            <td>
                                                @if($status == 'approved')
                                                    <button
                                                        class="btn btn-sm btn-primary openBatchPrintModal"
                                                        data-batch="{{ $batchId }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#printPreviewModal"
                                                    >
                                                        <i class="bi bi-printer"></i> Print
                                                    </button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <script type="application/json" id="batch-data-{{ $batchId }}">
                                            {!! json_encode($rows->values(), JSON_UNESCAPED_UNICODE) !!}
                                            </script>

                                                    
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center text-muted mt-3">No requests in archive.</div>
                        @endif
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>

                </div>
            </div>
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

<!-- Edit Lifespan Modal -->
<div class="modal fade" id="editLifespanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('inventory.settings.lifespan.update') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Item Lifespan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                   <input type="hidden" name="item_name" id="edit_item_name_hidden">
                    <input type="hidden" name="description" id="edit_description_hidden">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Item Name</label>
                        <input type="text" id="edit_item_name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" id="edit_description" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Expected Life Years</label>
                        <input
                            type="number"
                            name="expected_life_years"
                            id="edit_lifespan"
                            class="form-control"
                            min="0"
                            required
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View All Lifespan Modal -->
<div class="modal fade" id="viewAllLifespanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">All Item Lifespan Limits</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th class="text-center">Lifespan</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lifespanItems as $item)
                                <tr>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td class="text-center">{{ $item->expected_life_years ?? 0 }}</td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-link p-0 open-edit-lifespan"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editLifespanModal"
                                            data-item-id="{{ $item->item_id }}"
                                            data-item-name="{{ $item->item_name }}"
                                            data-description="{{ $item->description }}"
                                            data-lifespan="{{ $item->expected_life_years ?? 0 }}"
                                        >
                                            <i class="bi bi-pencil text-primary"></i>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('inventory.settings.lifespan.delete') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                            <input type="hidden" name="description" value="{{ $item->description }}">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-link p-0"
                                                onclick="return confirm('Reset lifespan to 0 for this item?')"
                                            >
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Classification Modal -->
<div class="modal fade" id="editClassificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('inventory.settings.classification.update') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Classification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="item_name" id="edit_class_item_name_hidden">
                    <input type="hidden" name="description" id="edit_class_description_hidden">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Item Name</label>
                        <input type="text" id="edit_class_item_name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" id="edit_class_description" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Classification</label>
                        <input
                            type="text"
                            name="classification"
                            id="edit_classification"
                            class="form-control"
                            required
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View All Classification Modal -->
<div class="modal fade" id="viewAllClassificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">All Item Classifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th class="text-center">Classification</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classifications as $item)
                                <tr>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td class="text-center">{{ $item->classification ?: 'Unclassified' }}</td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-link p-0 open-edit-classification"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editClassificationModal"
                                            data-item-name="{{ $item->item_name }}"
                                            data-description="{{ $item->description }}"
                                            data-classification="{{ $item->classification ?: '' }}"
                                        >
                                            <i class="bi bi-pencil text-primary"></i>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('inventory.settings.classification.delete') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                            <input type="hidden" name="description" value="{{ $item->description }}">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-link p-0"
                                                onclick="return confirm('Reset classification for this item group?')"
                                            >
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editSourceOfFundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('inventory.settings.source-of-fund.update') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Source of Fund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="item_name" id="edit_sof_item_name_hidden">
                    <input type="hidden" name="description" id="edit_sof_description_hidden">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Item Name</label>
                        <input type="text" id="edit_sof_item_name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" id="edit_sof_description" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Source of Fund</label>
                        <input
                            type="text"
                            name="source_of_fund"
                            id="edit_source_of_fund_value"
                            class="form-control"
                            required
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewAllSourceOfFundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">All Source of Fund Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th class="text-center">Source of Fund</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sourceOfFunds as $item)
                                <tr>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td class="text-center">{{ $item->source_of_fund ?: 'Not set' }}</td>
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-link p-0 open-edit-source-of-fund"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSourceOfFundModal"
                                            data-item-name="{{ $item->item_name }}"
                                            data-description="{{ $item->description }}"
                                            data-source-of-fund="{{ $item->source_of_fund ?: '' }}"
                                        >
                                            <i class="bi bi-pencil text-primary"></i>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('inventory.settings.source-of-fund.delete') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                            <input type="hidden" name="description" value="{{ $item->description }}">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-link p-0"
                                                onclick="return confirm('Reset source of fund for this item group?')"
                                            >
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="{{ asset('js/inventory-settings-print.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/inventory-settings-lifespan.js') }}"></script>
    



</body>
</html>