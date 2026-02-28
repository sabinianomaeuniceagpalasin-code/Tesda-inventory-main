<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login History</title>
  <link rel="stylesheet" href="{{ asset('css/profile-settings.css') }}">
</head>
<body>

  <div class="page">
    <div class="topbar">
      <a href="{{ route('dashboard') }}" class="back-btn">←</a>
      <h1>Account Settings</h1>
    </div>

    <div class="card">
      <!-- LEFT -->
      <aside class="side">
        <div class="profile-box">
          <div class="avatar">
            <span class="avatar-icon">👤</span>
          </div>
          <div class="profile-meta">
            <div class="profile-name">
              {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
            </div>
            <div class="profile-email">{{ auth()->user()->email }}</div>
          </div>
        </div>

        <nav class="side-menu">
          <a class="side-link" href="{{ route('profile-settings') }}">
            <span class="side-ico">⚙️</span> Account Settings
          </a>
          <a class="side-link active" href="{{ route('login-history') }}">
            <span class="side-ico">🕒</span> Login History
          </a>
        </nav>

        <div class="side-bottom">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
              <span class="side-ico">↩</span> Log Out
            </button>
          </form>
        </div>
      </aside>

      <!-- RIGHT -->
      <main class="content">
        <div class="lh-header">
          <div>
            <h2 class="lh-title">Login History</h2>

            <p class="lh-sub">
              @if($lastSuccessful)
                Last successful login:
                {{ $lastSuccessful->logged_in_at?->format('M d, Y \a\t g:i A') }}
                ({{ \Illuminate\Support\Str::limit($lastSuccessful->user_agent ?? 'Unknown device', 40) }})
              @else
                No login records yet.
              @endif
            </p>
          </div>

          <form method="GET" action="{{ route('login-history') }}" class="lh-filter">
            <select name="range" onchange="this.form.submit()">
              <option value="7"  {{ ($range ?? '7') == '7' ? 'selected' : '' }}>Show last 7 days</option>
              <option value="30" {{ ($range ?? '7') == '30' ? 'selected' : '' }}>Show last 30 days</option>
              <option value="all" {{ ($range ?? '7') == 'all' ? 'selected' : '' }}>Show all</option>
            </select>
          </form>
        </div>

        <div class="lh-table-wrap">
          <table class="lh-table">
            <thead>
              <tr>
                <th>Date &amp; Time</th>
                <th>Device/Browser</th>
                <th>IP Address</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($history as $row)
                <tr>
                  <td>
                    <div class="lh-date">
                      <div class="lh-date-top">{{ $row->logged_in_at?->format('F d, Y') }}</div>
                      <div class="lh-date-bottom">- {{ $row->logged_in_at?->format('g:i A') }}</div>
                    </div>
                  </td>

                  <td class="lh-device">
                    {{ $row->user_agent ? \Illuminate\Support\Str::limit($row->user_agent, 28) : 'Unknown' }}
                  </td>

                  <td>{{ $row->ip_address ?? '—' }}</td>

                  <td>
                    <span class="lh-status success">Success</span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="lh-empty">No login history found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <p class="lh-note">
          If you see unfamiliar devices, change your password immediately
        </p>
      </main>
    </div>
  </div>

</body>
</html>