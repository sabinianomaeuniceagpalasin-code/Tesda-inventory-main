<div id="notificationPanel" class="notification-panel hidden">
    <div class="notif-header">
        <h3>Notifications</h3>
        <button id="markAllRead">Mark all as read</button>
    </div>

    <div class="notif-body">
        @forelse($notifications as $notif)
            @php
                switch ($notif->type) {
                    case 'maintenance':
                        $target = 'reports';
                        $icon = '⚠️';
                        $iconClass = 'warning';
                        break;
                    case 'inventory':
                        $target = 'inventory';
                        $icon = '🛠️';
                        $iconClass = 'tool';
                        break;
                    case 'user':
                        $target = 'issued';
                        $icon = '👤';
                        $iconClass = 'user';
                        break;
                    default:
                        $target = 'dashboard';
                        $icon = '✔️';
                        $iconClass = 'success';
                        break;
                }
            @endphp

            <div class="notif-item">
                <span class="notif-icon {{ $iconClass }}">{{ $icon }}</span>
                <div class="notif-text">
                    <p><strong>{{ $notif->title }}</strong> {{ $notif->message }}</p>
                    <a href="#" class="notif-view" data-target="{{ $target }}">View</a>
                </div>
            </div>
        @empty
            <div class="notif-item">
                <p>No notifications found</p>
            </div>
        @endforelse
    </div>

    <div class="notif-footer">
        <a href="#">View All Notifications</a>
    </div>
</div>