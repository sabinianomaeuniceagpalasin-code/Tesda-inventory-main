<div id="notifDropdown" class="notif-dropdown">
    <div class="notif-header">
        <div>
            <h3>Notifications</h3>
            <p>{{ $unreadCount }} unread</p>
        </div>
        <button type="button" id="markAllReadBtn" class="notif-mark-all">
            Mark all as read
        </button>
    </div>

    <div class="notif-list">
        @forelse($notifications as $notif)
            <a
                href="{{ $notif->action_url ?: 'javascript:void(0)' }}"
                class="notif-card {{ $notif->read_at ? '' : 'unread' }}"
                data-recipient-id="{{ $notif->recipient_id }}"
            >
                <div class="notif-icon {{ $notif->severity ?? 'info' }}">
                    @if(($notif->severity ?? 'info') === 'success')
                        <i class="fa-solid fa-circle-check"></i>
                    @elseif(($notif->severity ?? 'info') === 'warning')
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    @elseif(($notif->severity ?? 'info') === 'danger')
                        <i class="fa-solid fa-circle-xmark"></i>
                    @else
                        <i class="fa-solid fa-bell"></i>
                    @endif
                </div>

                <div class="notif-content">
                    <div class="notif-topline">
                        <h4>{{ $notif->title }}</h4>
                        @if(!$notif->read_at)
                            <span class="notif-dot"></span>
                        @endif
                    </div>

                    <p>{{ $notif->message }}</p>
                    <small>{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</small>
                </div>
            </a>
        @empty
            <div class="notif-empty">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        @endforelse
    </div>
</div>