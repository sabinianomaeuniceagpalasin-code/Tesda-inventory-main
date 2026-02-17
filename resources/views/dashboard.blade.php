@php
  $user = auth()->user();
  $isAdmin = $user && $user->role === 'Admin';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>TESDA Dashboard</title>
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  <link rel="stylesheet" href="{{ asset('css/scanner.css') }}">
  <link rel="stylesheet" href="{{ asset('css/notification.css') }}">
  <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="container">

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="{{ asset('images/Tesda logo 1.png') }}" alt="TESDA Logo">
      </div>

      <!-- NAVIGATION MENU -->
      <nav class="menu">

        <!-- Everyone -->
        <a href="#" class="active" data-target="dashboard">
          <img src="{{ asset('images/reports.png') }}" class="menu-icon">
          Dashboard
        </a>

        <a href="#" data-target="inventory">
          <img src="{{ asset('images/inventory.png') }}" class="menu-icon">
          Inventory
        </a>

        <a href="#" data-target="issued">
          <img src="{{ asset('images/issued.png') }}" class="menu-icon">
          Issued Item
        </a>

        <a href="#" data-target="form">
          <img src="{{ asset('images/form.png') }}" class="menu-icon">
          Form Records
        </a>

        <a href="#" data-target="damaged">
          <img src="{{ asset('images/form.png') }}" class="menu-icon">
          Damage Report
        </a>

        <!-- ADMIN ONLY -->
        @if($isAdmin)
          <a href="#" data-target="reports">
            <img src="{{ asset('images/maintenance.png') }}" class="menu-icon">
            Maintenance
          </a>

          <a href="#" data-target="Generate">
            <img src="{{ asset('images/maintenance.png') }}" class="menu-icon">
            QR Generator
          </a>
        @endif

      </nav>

      <!-- SETTINGS (ADMIN ONLY) -->
      @if($isAdmin)
        <a href="{{ route('inventory.settings') }}" class="bottom-menu">
          <span class="icon">⚙️</span> Settings
        </a>
      @endif

      <!-- LOGOUT (ALL USERS) -->
      <form method="POST" action="{{ route('logout') }}" class="bottom-menu">
        @csrf
        <button type="submit">
          <span class="icon">🚪</span> Log Out
        </button>
      </form>

    </aside>

    <!-- Main -->
    <main class="main">
      <header class="topbar">
        <h1 id="page-title">Dashboard</h1>
        <div class="right-section">
          <div class="icons">
            <span id="notifBell" style="cursor:pointer;">🔔</span>
            <span id="profileIcon" style="cursor:pointer;">👤</span>
          </div>

          @include('components.notifications')
          @include('components.profile')
        </div>
      </header>

      <section id="content-area">
        <!-- ======================
             DASHBOARD SECTION
        ======================= -->
        <div id="dashboard" class="content-section active">
          <section class="quick-status">
            <div class="status-card clickable-card" onclick="openDashboardModal('inventory')">
              <h2>{{ $totalItems }}</h2>
              <p>Total Items & Equipment</p>
            </div>
            <div class="status-card clickable-card" onclick="openDashboardModal('available')">
              <h2>{{ $availableItems }}</h2>
              <p>Available Items</p>
            </div>
            <div class="status-card clickable-card" onclick="openDashboardModal('issued')">
              <h2>{{ $issuedItems }}</h2>
              <p>Issued Items</p>
            </div>
            <div class="status-card clickable-card" onclick="openDashboardModal('repair')">
              <h2>{{ $forRepair }}</h2>
              <p>Under Maintenance</p>
            </div>

            <div class="summary-column">
              <div class="summary-item clickable-item" onclick="openDashboardModal('lowstock')">
                <p>| Low Stock</p><span>{{ $lowStock }}</span>
              </div>
              <div class="summary-item clickable-item" onclick="openDashboardModal('missing')">
                <p>| Missing Items</p><span>{{ $missingItems }}</span>
              </div>
              <div class="summary-item clickable-item" onclick="openDashboardModal('unserviceable')">
                <p>| Unserviceable Items</p><span>{{ $missingItems }}</span>
              </div>
            </div>

          </section>

          <section class="dashboard-layout">
            <div class="top-row">
              <div class="chart-box equal-box" id="usageTrendsBox">
                <h3>Usage Trends</h3>
                <div class="chart-wrapper">
                  <canvas id="usageChart"></canvas>
                </div>
              </div>


              <div class="chart-box equal-box clickable-card" onclick="openIssuedModal()">
                <h3>Issued Items Frequency</h3>
                <div class="chart-wrapper">
                  <canvas id="issuedChart"></canvas>
                </div>
              </div>
            </div>
          </section>


          <div id="usageModal" class="usage-modal-overlay">
            <div class="usage-modal-box">
              <button class="usage-modal-close" id="closeUsageModal">&times;</button>

              <h2>Usage Trends</h2>

              <div class="usage-modal-chart">
                <canvas id="usageModalChart"></canvas>
              </div>
            </div>
          </div>

          <div id="issuedModal" class="usage-modal-overlay">
            <div class="usage-modal-box">
              <button class="usage-modal-close" id="closeIssuedModal">&times;</button>

              <h2>Issued Items Frequency</h2>

              <div class="usage-modal-chart">
                <canvas id="issuedModalChart"></canvas>
              </div>
            </div>
          </div>

          <div>
            <div>
              <button class="modalBtn">MODAL BUTTON</button>
            </div>
          </div>
        </div>



        <!-- ======================
            INVENTORY SECTION
        ======================= -->
        <div id="inventory" class="content-section">
          <div class="inventory-summary">
            <div class="summary-box">
              <p>Total Items & Equipment</p>
              <h2>{{ $totalItems }}</h2>
            </div>
            <div class="summary-box">
              <p>Available Items</p>
              <h2>{{ $availableItems }}</h2>
            </div>
            <div class="summary-box">
              <p>Issued Items</p>
              <h2>{{ $issuedItems }}</h2>
            </div>
            <div class="summary-box">
              <p>Unserviceable/For Repair</p>
              <h2>{{ $forRepair }}</h2>
            </div>
          </div>

          <div class="inventory-controls">
            <div class="left-buttons">
              <button>Sort by fields</button>
              <button>+ Export</button>
              <button>Clear filters</button>
            </div>
            <div class="right-buttons">
              <input type="text" id="inventorySearchInput" placeholder="Search Item Name...">
              <button id="addItemBtn">+ Add new item</button>
            </div>
          </div>

          <table id="inventoryTable">
            <thead>
              <tr>
                <th>Serial #</th>
                <th>Item</th>
                <th>Sources of Fund</th>
                <th>Classification</th>
                <th>Date Acquired</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($inventory as $item)
                <tr onclick="showItemDetails({{ json_encode($item) }})" style="cursor: pointer;">
                  <td>{{ $item->serial_no }}</td>
                  <td>{{ $item->item_name }}</td>
                  <td>{{ $item->source_of_fund }}</td>
                  <td>{{ $item->classification }}</td>
                  <td>{{ \Carbon\Carbon::parse($item->date_acquired)->format('F d, Y') }}</td>
                  <td>
                    <span class="
                                                                            @if($item->status === 'Available') text-green
                                                                            @elseif($item->status === 'For Repair') text-brown
                                                                            @elseif($item->status === 'Issued') text-blue
                                                                            @elseif($item->status === 'Unserviceable' || $item->status === 'Damaged' || $item->status === 'Lost') text-red
                                                                            @endif">
                      {{ $item->status }}
                    </span>
                  </td>
                  <td class="action-buttons">
                    <button class="edit-btn" onclick="event.stopPropagation();">✏️</button>
                    <button class="delete-btn" onclick="event.stopPropagation();">🗑️</button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div><button class="view-btn">View Usage History</button></div>

          <div class="modal fade" id="inventoryModal" tabindex="-1">
            <div class="modal-dialog modal-md modal-side-right">
              <div class="modal-content item-detail-modal">
                <div class="modal-header-custom">
                  <button type="button" class="btn-action" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i>
                  </button>
                  <h5 class="modal-title-custom">Item detail</h5>
                  <button type="button" class="btn-action" onclick="saveItemChanges()">
                    <i class="bi bi-check-lg"></i>
                  </button>
                </div>

                <div class="modal-body p-0">
                  <div class="detail-row">
                    <div class="detail-info">
                      <label>Item name</label>
                      <div id="modal-item" class="detail-value"></div>
                    </div>
                    <a href="#" class="detail-action">Rename <i class="bi bi-pencil"></i></a>
                  </div>

                  <div class="detail-row">
                    <div class="detail-info">
                      <label>Property No.</label>
                      <div id="modal-serial" class="detail-value"></div>
                    </div>
                    <a href="#" class="detail-action">Change</a>
                  </div>

                  <div class="detail-row">
                    <div class="detail-info">
                      <label>Condition</label>
                      <div id="modal-status" class="detail-value"></div>
                    </div>
                    <a href="#" class="detail-action">Change</a>
                  </div>

                  <div class="detail-row">
                    <div class="detail-info">
                      <label>Date Acquired</label>
                      <div id="modal-date" class="detail-value"></div>
                    </div>
                    <a href="#" class="detail-action">Change</a>
                  </div>

                  <div class="detail-row empty">
                    <label>Expected Lifespan</label>
                  </div>

                  <div class="detail-row empty">
                    <label>Predicted Maintenance</label>
                  </div>

                  <div class="status-marking">
                    <p class="section-title">Regular calibration required</p>
                    <div class="marking-options">
                      <span class="mark-label">Mark as</span>
                      <a href="javascript:void(0)" class="mark-link text-repair" onclick="updateStatus('For Repair')">[
                        For repair ]</a>
                      <a href="javascript:void(0)" class="mark-link text-unserviceable"
                        onclick="updateStatus('Unserviceable')">[ Unserviceable ]</a>
                      <a href="javascript:void(0)" class="mark-link text-missing" onclick="updateStatus('Missing')">[
                        Missing ]</a>
                      <a href="javascript:void(0)" class="mark-link text-notfound" onclick="updateStatus('Not found')">[
                        Not found ]</a>
                      <a href="javascript:void(0)" class="mark-link text-maintenance"
                        onclick="updateStatus('Maintenance')">[ Schedule item for maintenance ]</a>
                    </div>
                  </div>

                  <div class="footer-links" style="padding: 20px;">
                    <a href="javascript:void(0)" class="usage-history" onclick="showUsageHistory()">View item usage
                      history</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="usageHistoryModal" class="modal-overlay" style="display: none;">
            <div class="modal-content usage-history-content">
              <div class="usage-header">
                <button class="back-btn" onclick="closeUsageHistory()">
                  <i class="bi bi-arrow-left"></i>
                </button>
                <h1 class="usage-title">Usage History</h1>
              </div>

              <div class="usage-item-info">
                <p>Item: <span id="history-item-name">Printer</span></p>
                <p>Property No.: <span id="history-property-no">00001</span></p>
              </div>

              <div class="usage-filters">
                <select class="filter-select" id="filterStatus">
                  <option>All Statuses</option>
                </select>
                <select class="filter-select" id="filterDate">
                  <option>Latest - Oldest</option>
                </select>
              </div>

              <div class="table-responsive usage-table-container">
                <table class="usage-table">
                  <thead>
                    <tr>
                      <th>Issued Period</th>
                      <th>Issued To</th>
                      <th>Purpose</th>
                      <th>Issued By</th>
                      <th>Return Status</th>
                      <th>Condition After Use</th>
                      <th>Remarks</th>
                    </tr>
                  </thead>
                  <tbody id="usage-history-body">
                  </tbody>
                </table>
              </div>

              <div class="usage-footer">
                <span class="entries-count">Showing 1-3 of 42 entries</span>

                <div class="pagination-controls">
                  <button class="pag-btn" title="Previous">
                    <i class="bi bi-chevron-left"></i>
                  </button>

                  <button class="pag-num active">1</button>
                  <button class="pag-num">2</button>
                  <button class="pag-num">3...</button>

                  <button class="pag-btn" title="Next">
                    <i class="bi bi-chevron-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- SCANNER MODAL -->
          <div id="scannerModal" class="scanner-modal hidden">
            <div class="scanner-modal__box">
              <div class="scanner-modal__header">
                <h2>Scan Item</h2>
                <button onclick="closeScannerModal()" class="scanner-modal__close">&times;</button>
              </div>
              <div class="scanner-modal__body">
                <input id="scannerInput" type="text" placeholder="Scan QR/Barcode here" autofocus>
                <p class="scanner-instruction">Scanned items will appear below</p>
                <div id="scanned-items-list" class="scanned-items-container"></div>
              </div>
              <div class="scanner-modal__footer">
                <button onclick="closeScannerModal()" class="scanner-btn scanner-btn--cancel">Cancel</button>
                <button id="markReceivedBtn" class="scanner-btn scanner-btn--confirm">Mark as Received</button>
              </div>
            </div>
          </div>
        </div>

        <div id="issued" class="content-section">
          <div class="issued-header">
            <h2>Analytics Overview</h2>
          </div>

          <div class="issued-layout">
            <!-- ===== LEFT SECTION ===== -->
            <div class="issued-left">
              <!-- Analytics Overview -->
              <div class="analytics-overview">
                <div class="analytic-card clickable-card" onclick="openDynamicModal('total')">
                  <h4>Total issued items</h4>
                  <p>309</p>
                </div>
                <div class="analytic-card clickable-card" onclick="openDynamicModal('active')">
                  <h4>Active issuances</h4>
                  <p>0</p>
                </div>
                <div class="analytic-card clickable-card" onclick="openDynamicModal('returned')">
                  <h4>Returned items</h4>
                  <p>0</p>
                </div>
                <div class="analytic-card clickable-card" onclick="openDynamicModal('overdue')">
                  <h4>Overdue items</h4>
                  <p>0</p>
                </div>
                <div class="analytic-card clickable-card" onclick="openDynamicModal('permanent')">
                  <h4>Permanent issuances</h4>
                  <p>0</p>
                </div>
                <div class="analytic-card clickable-card" onclick="openDynamicModal('pending')">
                  <h4>Pending issuances</h4>
                  <p>0</p>
                </div>
              </div>

              <!-- Issued Table -->
              <div class="issued-table-section">
                <div class="table-header">
                  <h4>Issued item list</h4>
                  <a href="#">View all</a>
                </div>
                <table class="issued-table">
                  <thead>
                    <tr>
                      <th>Serial #</th>
                      <th>Issued to</th>
                      <th>Issued by</th>
                      <th>Date Issued</th>
                      <th>Expected Return Date</th>
                      <th>Item</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($issuedItemsList as $item)
                      <tr>
                        <td>{{ $item->serial_no }}</td>
                        <td>{{ $item->issued_to }}</td>
                        <td>{{ $item->issued_by }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->issued_date)->format('F d, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->return_date)->format('F d, Y') }}</td>
                        <td>{{ $item->item }}</td>
                        <td class="action-buttons-issued">
                          <button class="action-btn-issued return-btn-issued" title="Return"
                            data-id="{{ $item->issue_id }}">
                            <i class="fas fa-undo"></i>
                          </button>
                          <button class="action-btn-issued damaged-btn-issued" data-id="{{ $item->serial_no }}"
                            title="Damaged">
                            <i class="fas fa-exclamation-triangle"></i>
                          </button>
                          <button class="action-btn-issued unserviceable-btn-issued" title="Unserviceable">
                            <i class="fas fa-times-circle"></i>
                          </button>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>


        <!-- ======================
              MAINTENANCE & MONITORING
          ======================= -->
        <div id="reports" class="content-section">
          <div class="form-header">
            <h2>Maintenance Summary</h2>

            <div class="reports-controls">
              <div class="right-buttons">
                <input type="text" id="MaintenanceSearchInput" placeholder="Search Item Name...">
              </div>
            </div>
          </div>

          <div class="form-summary">
            <div class="summary-card">
              <p>Items Under Repair</p>
              <h2>{{ $maintenanceCounts['total'] }}</h2>
            </div>
            <div class="summary-card">
              <p>Complete Repairs</p>
              <h2>{{ $maintenanceCounts['pending'] }}</h2>
            </div>
            <div class="summary-card">
              <p>Unserviceable</p>
              <h2>{{ $maintenanceCounts['completed'] }}</h2>
            </div>
            <div class="summary-card">
              <p>Total Repair Cost</p>
              <h2>{{ $maintenanceCounts['upcoming'] }}</h2>
            </div>
          </div>

          <!-- Maintenance Filter Input -->
          <div class="reports-controls">

            <!-- Input With Icon -->
            <div class="left-side">
              <div class="input-with-icon">
                <input type="text" id="MaintenanceFilterInput" placeholder="Filter Items">
                <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M21 4V6H20L15 13.5V22H9V13.5L4 6H3V4H21ZM6.4037 6L11 12.8944V20H13V12.8944L17.5963 6H6.4037Z">
                  </path>
                </svg>
              </div>

              <!-- Export Button -->
              <div class="btn-with-icon">
                <button class="export-btn" id="ExportMaintenanceBtn">
                  <svg class="export-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path
                      d="M21 3H3C2.44772 3 2 3.44772 2 4V20C2 20.5523 2.44772 21 3 21H21C21.5523 21 22 20.5523 22 20V4C22 3.44772 21.5523 3 21 3ZM12 16C10.3431 16 9 14.6569 9 13H4V5H20V13H15C15 14.6569 13.6569 16 12 16ZM16 11H13V14H11V11H8L12 6.5L16 11Z">
                    </path>
                  </svg>
                  Export to PDF
                </button>
              </div>

              <!-- Print Button -->
              <div class="btn-wth-icon">
                <button class="print-btn" id="PrintMaintenanceBtn">
                  <svg class="print-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path
                      d="M7 17H17V22H7V17ZM19 20V15H5V20H3C2.44772 20 2 19.5523 2 19V9C2 8.44772 2.44772 8 3 8H21C21.5523 8 22 8.44772 22 9V19C22 19.5523 21.5523 20 21 20H19ZM5 10V12H8V10H5ZM7 2H17C17.5523 2 18 2.44772 18 3V6H6V3C6 2.44772 6.44772 2 7 2Z">
                    </path>
                  </svg>
                  Print
              </div>
            </div>

            <!-- Right Buttons -->
            <!-- <div class="right-side">
              <div class="btn-with-icon">
                <button class="add-btn" id="AddMaintenanceBtn">
                  <svg class="add-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"
                    fill="white">
                    <path d="M12 5v14m-7-7h14" stroke="white" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" />
                  </svg>
                  Add New Form
                </button>
              </div>
            </div> -->
          </div>


          <!-- Maintenance Records Table -->
          <div class="form-table-container mt-4">
            <h3>Maintenance Records</h3>
            <table class="form-table">
              <thead>
                <tr>
                  <th>Serial #</th>
                  <th>Item</th>
                  <th>Issue / Problem</th>
                  <th>Date Reported</th>
                  <th>Repair Cost</th>
                  <th>Expected Completion</th>
                  <th>Remarks</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($maintenanceRecords as $record)
                  <tr>
                    <td class="serial-cell" data-serial="{{ $record->serial_no }}">
                      {{ $record->serial_no }}
                    </td>
                    <td>{{ $record->item_name ?? '-' }}</td>
                    <td>{{ $record->issue_type ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->date_reported)->format('F d, Y') }}</td>
                    <td>{{ $record->repair_cost ? '₱' . number_format($record->repair_cost, 2) : '-' }}</td>
                    <td>
                      {{ $record->expected_completion ? \Carbon\Carbon::parse($record->expected_completion)->format('M d, Y') : '-' }}
                    </td>
                    <td>{{ $record->remarks ?? '-' }}</td>
                    <td>
                      <div class="btn-with-icon">
                        <button class="edit-btn" data-id="{{ $record->maintenance_id }}"
                          data-serial="{{ $record->serial_no }}">
                          <svg class="edit-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="rgba(100,205,138,1)">
                            <path
                              d="M12.8995 6.85453L17.1421 11.0972L7.24264 20.9967H3V16.754L12.8995 6.85453ZM14.3137 5.44032L16.435 3.319C16.8256 2.92848 17.4587 2.92848 17.8492 3.319L20.6777 6.14743C21.0682 6.53795 21.0682 7.17112 20.6777 7.56164L18.5563 9.68296L14.3137 5.44032Z">
                            </path>
                          </svg>
                          Edit
                        </button>
                      </div>
                      <div class="right-side">
                        <div class="btn-with-icon">
                          <button class="make-available-btn" data-serial="{{ $record->serial_no }}"
                            title="Make Available">
                            <svg class="available-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                              fill="rgba(100,205,138,1)">
                              <path d="M9 16.2l-3.5-3.5 1.41-1.42L9 13.38l7.09-7.09L17.5 7.8z" />
                            </svg>
                            Make Available
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" style="text-align:center;">No maintenance records found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>

        <!-- ======================
           DAMAGE REPORTS SECTION
        ======================= -->

        <div id="damaged" class="content-section">
          <div class="form-header">
            <h2>Damage Records History</h2>
          </div>

          <div class="damaged-layout">

            <div class="issued-left">

              <div class="form-summary">
                <div class="summary-card">
                  <h4>Total Reports</h4>
                  <p>{{ $damageCounts['total'] ?? 0 }}</p>
                </div>
                <div class="summary-card">
                  <h4>Reported Damages</h4>
                  <p>{{ $damageCounts['reported'] ?? 0 }}</p>
                </div>
              </div>

              <div class="damaged-table-section">
                <div class="table-header">
                  <h4>Damage Report List</h4>
                </div>

                <table class="issued-table">
                  <thead>
                    <tr>
                      <th>Serial #</th>
                      <th>Item</th>
                      <th>Date Reported</th>
                      <th>Actions</th>
                    </tr>
                  </thead>

                  <tbody>
                    @forelse($damageReports as $report)
                      <tr>
                        <td>{{ $report->serial_no }}</td>
                        <td>{{ $report->item->item_name ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($report->reported_at)->format('F d, Y') }}</td>
                        <td>
                          <div class="button-container">
                            <button class="action-btn-issued maintenance-btn-issued" data-id="{{ $report->id }}"
                              data-serial="{{ $report->serial_no }}" title="Maintenance">
                              <i class="fas fa-exclamation-triangle"></i>
                            </button>

                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="7" style="text-align:center; padding:20px;">
                          No damage reports found.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>


        <!-- ======================
            FORM RECORDS SECTION
        ======================= -->
        <div id="form" class="content-section">
          <div class="form-header">
            <h2>Form Summary</h2>
          </div>

          <div class="form-summary">
            <div class="summary-card">
              <p>Total Forms</p>
              <h2>{{ $formSummaryCounts->total_forms }}</h2>
            </div>
            <div class="summary-card">
              <p>ICS Form</p>
              <h2>{{ $formSummaryCounts->ics_forms }}</h2>
            </div>
            <div class="summary-card">
              <p>PAR Form</p>
              <h2>{{ $formSummaryCounts->par_forms }}</h2>
            </div>
            <div class="summary-card">
              <p>Active</p>
              <h2>{{ $formSummaryCounts->active_forms }}</h2>
            </div>
            <div class="summary-card">
              <p>Archive</p>
              <h2>{{ $formSummaryCounts->archived_forms }}</h2>
            </div>
          </div>

          <div class="form-controls">

            <button class="sort-btn"><i class="fas fa-filter"></i> Sort by field</button>
            <button class="add-btn"><i class="fas fa-plus"></i> Add New Form</button>
          </div>

          <div class="form-table-container">
            <table class="form-table">
              <thead>
                <tr>
                  <th>Form Type</th>
                  <th>Reference No.</th>
                  <th>Date Created</th>
                  <th>Issued To</th>
                  <th>Item Count</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($issuedForms as $form)
                  <tr>
                    <td>{{ $form->form_type }}</td>
                    <td>{{ $form->reference_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($form->created_at)->format('F d, Y') }}</td>
                    <td>{{ $form->student_name }}</td>
                    <td>{{ $form->item_count }}</td>
                    <td><span class="status {{ strtolower($form->status) }}">{{ $form->status }}</span></td>
                    <td><a href="#">View</a></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div id="formTypeModal" class="modal-overlay" style="display: none;">
            <div class="modal-content" style="width: 420px;">
              <span class="close-btn" onclick="closeFormTypeModal()">&times;</span>
              <h2 style="color:#004aad;text-align:center;">Choose Form Type</h2>
              <div style="display:flex;gap:20px;justify-content:center;margin-top:20px;">
                <button class="save-btn" id="chooseIcs">ICS</button>
                <button class="save-btn" id="choosePar">PAR</button>
              </div>
            </div>
          </div>

          <div id="viewFormModal" class="modal-overlay" style="display:none;">
            <div class="modal-content" style="width: 800px;">
              <span class="close-btn" onclick="closeViewFormModal()">&times;</span>
              <h2 style="text-align:center;color:#004aad;">Form Details</h2>
              <div class="modal-body" style="margin-top:20px;"></div>
              <div style="text-align:center; margin:20px 0;">
                <button class="save-btn" onclick="printFormModal()">🖨️ Print</button>
              </div>
            </div>
          </div>

          <div id="addFormModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
              <span class="close-btn" onclick="closeAddFormModal()">&times;</span>
              <h2 id="addFormTitle" style="text-align:center;color:#004aad;">Add New Form</h2>

              <form id="addForm" onsubmit="submitForm(event)">
                <input type="hidden" id="form_type_input" name="form_type" value="ICS">

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                  <div class="full-width" style="position:relative;">
                    <label>Student Name</label>
                    <input type="text" id="studentSearch" name="student_name" autocomplete="off"
                      placeholder="Type student name..." required>

                    <div id="studentSuggestion" class="suggestion-box"></div>
                  </div>

                  <div class="full-width">
                    <label>Property Number</label>
                    <input type="text" id="propertyFilter" placeholder="Enter property number..." autocomplete="on">
                  </div>

                  <!-- <div class="full-width">
                    <label>Available Serial Numbers</label>
                    <input type="hidden" id="serial_no" name="serial_no">
                    <div id="serialList" class="serial-container">
                      <div class="placeholder">Type a Serial No. to see available items.</div>
                    </div>
                  </div> -->


                  <div class="full-width">
                    <label>Reference No.</label>
                    <input type="text" id="referenceNo" name="reference_no" required>
                    <div id="refCheck" style="color:red;margin-top:6px;display:none;">Reference already exists.</div>
                  </div>

                  <div class="full-width">
                    <label>Issued Date</label>
                    <input type="date" id="issuedDate" name="issued_date" required>
                  </div>

                  <div class="full-width">
                    <label>Return Date</label>
                    <input type="date" id="returnDate" name="return_date">
                  </div>

                  <div class="form-buttons" style="margin-top:18px; grid-column: 1 / -1;">
                    <button type="submit" class="save-btn">Save Form</button>
                    <button type="button" class="reset-btn" onclick="closeAddFormModal()">Cancel</button>
                  </div>
              </form>
            </div>
          </div>
        </div>
  </div>


  <!-- GENERATE QR CODE MODULE REQUEST -->
  <div class="content-section qr-code" id="Generate">

    <div class="qr-container">

      <!-- LEFT PANEL -->
      <div class="qr-filters">
        <h3>Add to Queue</h3>

        <label>Item Name</label>
        <input type="text" id="item-name" placeholder="Enter item name">

        <label>Type</label>
        <select id="item-type">
          <option value="qr" selected>QR Code</option>
          <option value="barcode">Barcode</option>
        </select>

        <label>Quantity</label>
        <input type="number" id="item-quantity" min="1" placeholder="Enter quantity">

        <button type="button" id="add-to-queue-btn">
          Add to Queue
        </button>

        <p class="note">
          Items will be queued first before generating codes.
        </p>
      </div>

      <!-- RIGHT PANEL -->
      <div class="qr-preview">

        <h3>Generation Queue</h3>

        <div class="qr-queue-scroll">
          <table class="qr-queue-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Type</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="qr-queue-body"></tbody>
          </table>
        </div>

        <h3 class="preview-title">Preview</h3>

        <div class="qr-scroll">
          <div id="qr-result"></div>
        </div>

        <!-- FOOTER -->
        <div class="qr-footer">
          <span class="approval-note">
            Items in queue require admin approval
          </span>

          <button id="send-request-btn" disabled>
            Send for Approval
          </button>
        </div>

      </div>
    </div>
  </div>






  </div>
  <!-- ===============================
     ISSUED ITEMS MODAL
================================ -->
  <div id="dynamicModal" class="modal-overlay">
    <div class="modal-contents">
      <div class="modal-header">
        <h3 id="m-title">List of All Issued Items</h3>
      </div>
      <div class="modal-body">
        <div class="stat-main" id="m-summary"></div>

        <div class="issuance-type-section">
          <p class="section-label" id="m-label"></p>
          <ul class="type-list" id="m-list"></ul>
        </div>

        <div id="m-footer-info" class="trend-section"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-close-modal" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- ===============================
    DASHBOARD MODAL
================================ -->
  <div id="dashboardTableModal" class="modal-overlay">
    <div class="modal-contents list-modal-wide">
      <div class="modal-header">
        <h3 id="dt-title">List of All Items</h3>
      </div>

      <div class="modal-body">
        <div class="table-controls" id="dt-controls">
          <button class="btn-sort">Sort by field <i class="fas fa-filter"></i></button>
        </div>

        <div class="table-responsive">
          <table class="dashboard-list-table">
            <thead id="dt-thead">
            </thead>
            <tbody id="dt-tbody">
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer" id="dt-footer">
        <button type="button" class="btn-view-section" onclick="openViewInventorySection()">View Inventory
          Section</button>
      </div>
    </div>
  </div>


  <!-- ===============================
     MAINTENANCE EDIT MODAL
================================ -->
  <div id="maintenanceEditModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <button id="closeMaintenanceModal" class="modal-close">&times;</button>
      <h2 class="modal-title">Edit Maintenance Record</h2>

      <form id="maintenanceForm">
        <div class="form-group">
          <label>Serial #</label>
          <input type="text" id="m_serial_no" name="serial_no" placeholder="Enter Serial #" readonly required>
        </div>

        <div class="form-group">
          <label>Item Name*</label>
          <input type="text" id="m_item_name" name="item_name" readonly required>
        </div>

        <div class="form-group">
          <label>Issue / Problem*</label>
          <input type="text" id="m_issue" name="issue" required>
        </div>

        <div class="form-group">
          <label>Date Reported*</label>
          <input type="date" id="m_date" name="date_reported" readonly required>
        </div>

        <div class="form-group">
          <label>Repair Cost*</label>
          <input type="number" id="m_cost" name="repair_cost" required>
        </div>

        <div class="form-group">
          <label>Expected Completion*</label>
          <input type="date" id="m_completion" name="expected_completion" required>
        </div>

        <div class="form-group">
          <label>Remarks</label>
          <textarea id="m_remarks" name="remarks"></textarea>
        </div>

        <div class="modal-buttons">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" class="save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>


  <!-- CHATBOT BUTTON -->
  <div id="chat-toggle" class="chat-toggle">
    <i class="fa-solid fa-comments"></i>
  </div>

  <!-- CHAT WINDOW -->
  <div id="chat-popup" class="chat-popup" aria-hidden="true">

    <!-- HEADER -->
    <div class="chat-header">
      <div class="chat-title">TESDA ChatBot</div>
      <button id="chat-close" class="chat-close">&times;</button>
    </div>

    <!-- BODY -->
    <div class="chat-body">

      <!-- MESSAGES -->
      <div id="chat-messages" class="chat-messages"></div>

      <!-- INPUT -->
      <div class="chat-input-bar">
        <input id="chat-input" type="text" placeholder="Type your message..." autocomplete="off" />
        <button id="chat-send">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </div>

    </div>
  </div>


  <script>

    // I DIDN'T SEPERATE THIS FUNCTION INTO A DIFFERENT JS FILE BECAUSE IT'S ONLY USED HERE
    // Print the content of the View Form Modal
    function printFormModal() {
      const modal = document.getElementById('viewFormModal');
      const modalContent = modal.querySelector('.modal-body').cloneNode(true);

      let formType = modal.dataset.formType || '';
      if (formType === 'ICS') formType = 'Inventory Custodian Slip (ICS)';
      if (formType === 'PAR') formType = 'Property Acknowledgement Receipt (PAR)';

      const printHTML = `
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color:#000; }
            h2 { text-align: center; color: #004aad; margin-bottom: 20px; }
            p { margin: 5px 0; }
            u { text-decoration: underline; }
            table { border-collapse: collapse; width: 100%; margin-top: 10px; }
            table, th, td { border: 1px solid #000; }
            th, td { padding: 8px; text-align: left; }
            .signature-block { display: flex; justify-content: space-between; margin-top: 50px; }
            .signature-block div { width: 45%; }
            .signature-block .line { border-bottom: 1px solid #000; height: 1px; margin: 40px 0 5px 0; }
            .form-header { text-align: center; font-weight: bold; line-height: 1.4; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="form-header">
            TESDA<br>
            Property and Supply Management Section<br>
            ${formType ? `<span style="font-size:16px;">${formType}</span>` : ''}
        </div>

        <div>${modalContent.innerHTML}</div>

        <div class="signature-block">
            <div>
                Issued By:<br>
                <div class="line"></div>
                Signature over printed name<br>
                Date: __________
            </div>
            <div>
                Issued To:<br>
                <div class="line"></div>
                Signature over printed name<br>
                Date: __________
            </div>
        </div>
    </body>
    </html>
    `;

      const printWindow = window.open('TESDA', 'TESDA', 'width=900,height=700');
      printWindow.document.write(printHTML);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    }

  </script>

  <script>
    // Usage Data
    window.usageLabels = @json($usageData->pluck('item_name'));
    window.usageValues = @json($usageData->pluck('total_usage')).map(Number);

    // Issued Data
    window.issuedLabels = @json($issuedFrequency->pluck('item_name'));
    window.issuedValues = @json($issuedFrequency->pluck('total')).map(Number);
  </script>

  <script src="{{ asset('js/dashboard-charts.js') }}"></script>
  <script src=" {{ asset('js/dashboard-page-switch.js') }}"></script>
  <script src="{{ asset('js/dashboard-inv-search.js') }}"></script>
  <script src="{{ asset('js/dashboard-modal.js') }}"></script>
  <script src="{{ asset('js/dashboard-form-search.js') }}"></script>
  <script src="{{ asset('js/dashboard-calc-total.js') }}"></script>
  <script src="{{ asset('js/dashboard-fill-prop-num.js') }}"></script>
  <script src="{{ asset('js/dashboard-form-search.js') }}"></script>
  <script src="{{ asset('js/dashboard-load-avail-serials.js') }}"></script>
  <script src="{{ asset('js/dashboard-prop-filter.js') }}"></script>
  <script src="{{ asset('js/dashboard-reference-quick.js') }}"></script>
  <script src="{{ asset('js/dashboard-student-search.js') }}"></script>
  <script src="{{ asset('js/dashboard-submit-form.js') }}"></script>
  <script src="{{ asset('js/dashboard-propertyno-search.js') }}"></script>
  <script src="{{ asset('js/chatbot.js') }}"></script>
  <script src="{{ asset('js/notification.js') }}"></script>
  <script src="{{ asset('js/profile.js') }}"></script>
  <script src="{{ asset('js/return-item.js') }}"></script>
  <script src="{{ asset('js/issued-unserviceable.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
  <script src="{{ asset('js/damage.js') }}"></script>
  <script src="{{ asset('js/maintenance.js') }}"></script>
  <script src="{{ asset('js/maintenance-report.js') }}"></script>
  <script src="{{ asset('js/make-available.js') }}"></script>
  <script src="{{ asset('js/history.js') }}"></script>
  <script src="{{ asset('js/usage-trends-modal.js') }}"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <script src="{{ asset('js/qr-module.js') }}"></script>
  <script src="{{ asset('js/scanner.js') }}"></script>

</body>

</html>