@forelse($damageReports as $report)
  <tr>
    <td>{{ $report->serial_no }}</td>
    <td>{{ $report->item->item_name ?? '-' }}</td>
    <td>
      {{ $report->reported_at ? \Carbon\Carbon::parse($report->reported_at)->format('F d, Y') : '-' }}
    </td>
    <td>
      <div class="button-container">
        <button class="action-btn-issued maintenance-btn-issued"
          data-serial="{{ $report->serial_no }}"
          title="Maintenance">
          <i class="fas fa-exclamation-triangle"></i>
        </button>
      </div>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="4" style="text-align:center; padding:20px;">
      No damage reports found.
    </td>
  </tr>
@endforelse