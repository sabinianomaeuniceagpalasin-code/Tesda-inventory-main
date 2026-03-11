<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Account Settings</title>
  <link rel="stylesheet" href="{{ asset('css/profile-settings.css') }}">
</head>
<body>

  <div class="page">
    <!-- Top header -->
    <div class="topbar">
        <a href="{{ route('dashboard') }}" class="back-btn">←</a>
        <h1>Account Settings</h1>
    </div>

    <!-- Main card -->
    <div class="card">
      <!-- Left sidebar -->
      <aside class="side">
        <div class="profile-box">
          <div class="avatar">
            {{-- If you have avatar image, replace this --}}
            <span class="avatar-icon">👤</span>
          </div>

          <div class="profile-meta">
            <div class="profile-name">
              {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
            </div>
            <div class="profile-email">{{ auth()->user()->email ?? 'Admin@gmail.com' }}</div>
          </div>
        </div>

        <nav class="side-menu">
          <a class="side-link active" href="{{ route('profile-settings') }}">
            <span class="side-ico">⚙️</span>
            Account Settings
          </a>
          <a class="side-link" href="{{ route('login-history') }}">
            <span class="side-ico">🕒</span>
            Login History
          </a>
        </nav>

        <div class="side-bottom">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
              <span class="side-ico">↩</span>
              Log Out
            </button>
          </form>
        </div>
      </aside>

      <!-- Right content -->
      <main class="content">

        @if(session('success'))
        <div style="padding:10px 12px; border:1px solid #bbf7d0; background:#ecfdf5; border-radius:10px; margin-bottom:14px;">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div style="padding:10px 12px; border:1px solid #fecaca; background:#fef2f2; border-radius:10px; margin-bottom:14px;">
            <ul style="margin:0; padding-left:18px;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
            </ul>
        </div>
        @endif
        
        {{-- Change action to your route --}}
        <form method="POST" action="{{ route('profile.update') }}" class="form">
          @csrf
          {{-- @method('PUT') --}}

          <section class="section">
            <h2 class="section-title">Basic Information</h2>

            <div class="grid">
              <div class="field">
                    <label for="first_name">First Name</label>
                    <input
                    id="first_name"
                    name="first_name"
                    type="text"
                    value="{{ old('first_name', auth()->user()->first_name) }}"
                    required
                    />
                </div>

                <div class="field">
                    <label for="last_name">Last Name</label>
                    <input
                    id="last_name"
                    name="last_name"
                    type="text"
                    value="{{ old('last_name', auth()->user()->last_name) }}"
                    required
                    />
                </div>
              <div class="field">
                <label for="contact_no">Contact Number</label>
                <input id="contact_no" name="contact_no" type="text" value="{{ old('contact_no', auth()->user()->contact_no ?? '') }}" placeholder="09xxxxxxxxx"/>
              </div>

              <div class="field">
                <label for="email">Email</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  value="{{ old('email', auth()->user()->email) }}"
                  placeholder="you@email.com"
                />
              </div>
            </div>
          </section>

          <section class="section">
            <h2 class="section-title">Change Password</h2>

            <div class="grid">
              <div class="field">
                <label for="current_password">Current password</label>
                <div class="input-wrap">
                  <input id="current_password" name="current_password" type="password" placeholder="••••••••••" />
                  <span class="lock">🔑</span>
                </div>
              </div>

              <div class="field">
                  <label for="new_password">New password</label>
                  <div class="input-wrap">
                      <input id="new_password" name="new_password" type="password" placeholder="••••••••••" />
                      <span class="lock">🔑</span>
                  </div>

                  <div id="passwordRules" style="margin-top:8px; font-size:13px;">
                      <div id="rule-length" style="color:#dc2626;">✖ At least 8 characters</div>
                      <div id="rule-upper" style="color:#dc2626;">✖ At least 1 uppercase letter</div>
                      <div id="rule-lower" style="color:#dc2626;">✖ At least 1 lowercase letter</div>
                      <div id="rule-number" style="color:#dc2626;">✖ At least 1 number</div>
                      <div id="rule-special" style="color:#dc2626;">✖ At least 1 special character</div>
                  </div>
              </div>

              <div class="field">
                <label for="new_password_confirmation">Confirm password</label>
                <div class="input-wrap">
                  <input id="new_password_confirmation" name="new_password_confirmation" type="password" placeholder="••••••••••" />
                  <span class="lock">🔑</span>
                </div>
              </div>
            </div>
          </section>

          <div class="actions">
            <button type="submit" class="save-btn">Save Changes</button>
          </div>
        </form>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
      window.profileSuccessMessage = @json(session('success'));
      window.profileErrorMessages = @json($errors->all());
  </script>

  <script src="{{ asset('js/profile-settings.js') }}"></script>

</body>
</html>
