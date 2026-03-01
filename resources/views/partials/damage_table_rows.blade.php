@forelse($damageReports as $report)
  <tr>
    <td>{{ $report->serial_no }}</td>
    <td>{{ $report->item_name ?? '-' }}</td>
    <td>{{ $report->observation ?? '-' }}</td>
    <td>{{ \Carbon\Carbon::parse($report->reported_at)->format('F d, Y') }}</td>
    <td>
      <div class="button-container">
        <button class="action-btn-issued maintenance-btn-issued"
          data-damage-id="{{ $report->damage_id }}"
          data-serial="{{ $report->serial_no }}"
          title="Maintenance">
          <i class="fas fa-exclamation-triangle"></i>
        </button>
      </div>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="5" style="text-align:center; padding:20px;">
      No damage reports found.
    </td>
  </tr>
@endforelse