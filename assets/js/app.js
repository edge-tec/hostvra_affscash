/* AffiliateTracker - Main JS */

// User dropdown toggle
document.addEventListener('DOMContentLoaded', function () {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.querySelector('.dropdown-menu');
    if (userMenu && dropdown) {
        userMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', function () {
            dropdown.classList.remove('show');
        });
    }

    // Auto-hide flash messages
    document.querySelectorAll('.alert[data-auto-hide]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, 4000);
    });

    // Copy to clipboard
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.dataset.copy);
            if (!target) return;
            navigator.clipboard.writeText(target.value || target.textContent).then(function () {
                const orig = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => btn.textContent = orig, 2000);
            });
        });
    });

    // Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // Poll notification count every 60s
    fetchNotifCount();
    setInterval(fetchNotifCount, 60000);
});

function fetchNotifCount() {
    fetch('/api/notifications?action=unread_count')
        .then(r => r.json())
        .then(data => {
            const badge = document.querySelector('.notif-badge');
            if (!badge) return;
            const n = data.unread || data.count || 0;
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : n;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }).catch(() => {});
}

// Postback URL builder macro inserter
function insertMacro(inputId, macro) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const pos = input.selectionStart;
    const val = input.value;
    input.value = val.slice(0, pos) + macro + val.slice(pos);
    input.focus();
    input.setSelectionRange(pos + macro.length, pos + macro.length);
}

// DataTables is initialized inline in each view that needs it
