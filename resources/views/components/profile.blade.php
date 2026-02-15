<div id="profilePanel" class="profile-panel hidden">
    <!-- User Info -->
    <div class="profile-header">
        <div class="profile-avatar">
            <img src="{{ asset('images/default-avatar.png') }}" alt="User Avatar">
        </div>
        <div class="profile-info">
            <p class="name">{{ auth()->user()->full_name }}</p>
            <p class="email">{{ auth()->user()->email }}</p>
        </div>
    </div>

    <!-- Menu Links -->
    <div class="profile-menu">
        <a href="#">
            <span class="icon">⚙️</span> Account Settings
        </a>
        <a href="#">
            <span class="icon">🕒</span> Login History
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <span class="icon">🚪</span> Log Out
            </button>
        </form>
    </div>
</div>