const root = document.documentElement;
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const menuToggle = document.getElementById('menuToggle');
const themeToggle = document.getElementById('themeToggle');
const themeSelect = document.querySelector('select[name="theme"]');

function setTheme(theme, save = false) {
    if (!['dark', 'light'].includes(theme)) {
        return;
    }

    root.dataset.theme = theme;
    localStorage.setItem('aidlink-theme', theme);
    document.cookie = `aidlink_theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;

    if (themeSelect) {
        themeSelect.value = theme;
    }

    if (save) {
        fetch('theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `theme=${encodeURIComponent(theme)}`
        }).catch(() => null);
    }
}

setTheme(localStorage.getItem('aidlink-theme') || root.dataset.theme || 'dark');

function closeMenu() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('show');
    menuToggle?.classList.remove('active');
}

menuToggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
    overlay?.classList.toggle('show');
    menuToggle.classList.toggle('active');
});

overlay?.addEventListener('click', closeMenu);

themeToggle?.addEventListener('click', () => {
    const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    setTheme(nextTheme, true);
});

themeSelect?.addEventListener('change', () => {
    setTheme(themeSelect.value, true);
});

// Sound settings are placed here so the ringtone can be changed anytime.
// Replace the file paths below with your own .mp3, .wav, or .ogg files.
const soundSettings = {
    enabled: true,
    volume: 0.55,
    messenger: 'assets/sounds/messenger.wav',
    notification: 'assets/sounds/notification.wav'
};

const soundPlayers = {};
let soundUnlocked = false;

function prepareSound(type) {
    if (!soundPlayers[type]) {
        const audio = new Audio(soundSettings[type]);
        audio.preload = 'auto';
        audio.volume = soundSettings.volume;
        soundPlayers[type] = audio;
    }

    return soundPlayers[type];
}

function unlockSounds() {
    if (soundUnlocked) {
        return;
    }

    soundUnlocked = true;

    // Prepare audio objects only. Do not play anything here, so normal page actions
    // such as exporting reports never trigger the ringtone files.
    ['messenger', 'notification'].forEach((type) => {
        try {
            prepareSound(type);
        } catch (error) {
            return;
        }
    });
}

function playFallbackTone(type) {
    try {
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const gain = context.createGain();
        gain.gain.value = 0.035;
        gain.connect(context.destination);

        const frequencies = type === 'messenger' ? [740, 980] : [880, 660];
        frequencies.forEach((frequency, index) => {
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            oscillator.connect(gain);
            oscillator.start(context.currentTime + index * 0.11);
            oscillator.stop(context.currentTime + index * 0.11 + 0.09);
        });
    } catch (error) {
        return;
    }
}

function playRingtone(type) {
    if (!soundSettings.enabled || !['messenger', 'notification'].includes(type)) {
        return;
    }

    try {
        const audio = prepareSound(type);
        audio.pause();
        audio.currentTime = 0;
        audio.volume = soundSettings.volume;
        audio.play().catch(() => playFallbackTone(type));
    } catch (error) {
        playFallbackTone(type);
    }
}

document.addEventListener('click', unlockSounds, { once: true });
document.addEventListener('keydown', unlockSounds, { once: true });

const fallbackLocations = [
    'Mati City, Davao Oriental, Philippines',
    'San Isidro, Davao Oriental, Philippines',
    'Manay, Davao Oriental, Philippines',
    'Lupon, Davao Oriental, Philippines',
    'Baganga, Davao Oriental, Philippines',
    'Davao City, Philippines',
    'Tagum City, Davao del Norte, Philippines',
    'Digos City, Davao del Sur, Philippines',
    'Panabo City, Davao del Norte, Philippines',
    'General Santos City, Philippines',
    'Cebu City, Philippines',
    'Quezon City, Metro Manila, Philippines',
    'Manila, Metro Manila, Philippines',
    'Baguio City, Philippines',
    'Tokyo, Japan',
    'Osaka, Japan',
    'New York, United States',
    'Los Angeles, United States',
    'London, United Kingdom',
    'Singapore',
    'Seoul, South Korea',
    'Bangkok, Thailand'
];

function cleanLocationName(value) {
    return String(value || '')
        .replace(/,\s*\d{4,6}(?=,|$)/g, '')
        .replace(/,\s*(Region\s+[IVXLCDM]+-?[A-Z]?|Davao Region)(?=,|$)/gi, '')
        .replace(/\s+/g, ' ')
        .replace(/,\s*,/g, ',')
        .replace(/^,\s*|,\s*$/g, '')
        .trim();
}

function attachLocationSuggest(input) {
    const wrapper = document.createElement('div');
    wrapper.className = 'location-suggest-wrap';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const box = document.createElement('div');
    box.className = 'suggest-box';
    wrapper.appendChild(box);

    let controller = null;
    let timer = null;

    function setHiddenCoordinates(item) {
        const latName = input.dataset.latField;
        const lonName = input.dataset.lonField;
        if (!latName || !lonName) return;
        const form = input.closest('form');
        const latField = form?.querySelector(`[name="${latName}"]`);
        const lonField = form?.querySelector(`[name="${lonName}"]`);
        if (latField && lonField) {
            latField.value = item.lat || '';
            lonField.value = item.lon || '';
        }
    }

    function render(items) {
        const seen = new Set();
        const unique = [];
        items.forEach((item) => {
            const normalized = cleanLocationName(item.label || item);
            if (!normalized || seen.has(normalized.toLowerCase())) return;
            seen.add(normalized.toLowerCase());
            unique.push({ label: normalized, lat: item.lat || '', lon: item.lon || '' });
        });

        box.innerHTML = '';
        unique.slice(0, 8).forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = item.label;
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                input.value = item.label;
                setHiddenCoordinates(item);
                box.classList.remove('show');
            });
            box.appendChild(button);
        });
        box.classList.toggle('show', unique.length > 0);
    }

    function localSearch(query) {
        const lower = query.toLowerCase();
        return fallbackLocations
            .filter((location) => location.toLowerCase().includes(lower))
            .map((location) => ({ label: location }));
    }

    input.addEventListener('input', () => {
        const query = input.value.trim();
        clearTimeout(timer);
        setHiddenCoordinates({});

        if (query.length < 2) {
            box.classList.remove('show');
            return;
        }

        render(localSearch(query));

        timer = setTimeout(async () => {
            try {
                if (controller) controller.abort();
                controller = new AbortController();
                const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=8&q=${encodeURIComponent(query)}`;
                const response = await fetch(url, { signal: controller.signal, headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                const remote = data
                    .filter((item) => item && item.display_name && item.lat && item.lon)
                    .map((item) => ({ label: cleanLocationName(item.display_name), lat: item.lat, lon: item.lon }));
                render([...remote, ...localSearch(query)]);
            } catch (error) {
                render(localSearch(query));
            }
        }, 320);
    });

    input.addEventListener('focus', () => {
        if (input.value.trim().length >= 2) {
            render(localSearch(input.value.trim()));
        }
    });

    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) {
            box.classList.remove('show');
        }
    });
}


function attachPasswordToggle(input) {
    if (input.dataset.toggleReady === '1') {
        return;
    }

    input.dataset.toggleReady = '1';
    const wrapper = document.createElement('div');
    wrapper.className = 'password-field';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-eye';
    button.setAttribute('aria-label', 'Show password');
    button.innerHTML = `
        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
        <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 3 18 18"></path>
            <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6"></path>
            <path d="M9.9 4.2A10.3 10.3 0 0 1 12 4c6.5 0 10 8 10 8a17.8 17.8 0 0 1-3.2 4.4"></path>
            <path d="M6.6 6.6C3.6 8.7 2 12 2 12s3.5 8 10 8a9.7 9.7 0 0 0 4.6-1.2"></path>
        </svg>`;
    wrapper.appendChild(button);

    button.addEventListener('click', () => {
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        button.classList.toggle('showing', !showing);
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        input.focus();
    });
}

document.querySelectorAll('input[type="password"]').forEach(attachPasswordToggle);


document.querySelectorAll('[data-location-suggest]').forEach(attachLocationSuggest);

// Preserve page scroll position when navigating between system sections.
const scrollKey = `aidlink-scroll:${location.pathname}${location.search}`;
window.addEventListener('beforeunload', () => {
    localStorage.setItem(scrollKey, String(window.scrollY || 0));
});
window.addEventListener('load', () => {
    const saved = localStorage.getItem(scrollKey);
    if (saved !== null && !location.hash) {
        setTimeout(() => window.scrollTo(0, Number(saved) || 0), 35);
    }
});

// Live header badges and lightweight chat refresh.
let lastMessageCount = null;
let lastNotificationCount = null;

function setBadge(selector, count) {
    const link = document.querySelector(selector);
    if (!link) return;

    let badge = link.querySelector('.badge');
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge';
            link.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

async function refreshHeaderCounts() {
    try {
        const response = await fetch('api_counts.php', { cache: 'no-store' });
        const data = await response.json();
        if (!data.ok) return;

        const messageCount = Number(data.message_count || 0);
        const notificationCount = Number(data.notification_count || 0);

        if (lastMessageCount !== null && messageCount > lastMessageCount) {
            playRingtone('messenger');
        }

        if (lastNotificationCount !== null && notificationCount > lastNotificationCount) {
            playRingtone('notification');
        }

        lastMessageCount = messageCount;
        lastNotificationCount = notificationCount;
        setBadge('a[href="messenger.php"]', messageCount);
        setBadge('a[href="notifications.php"]', notificationCount);
    } catch (error) {
        return;
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function refreshMessages() {
    const chatWindow = document.querySelector('.chat-window[data-conversation-id]');
    const thread = document.getElementById('messageThread');
    if (!chatWindow || !thread) return;

    const conversationId = chatWindow.dataset.conversationId;
    if (!conversationId || conversationId === '0') return;

    try {
        const response = await fetch(`api_messages.php?conversation_id=${encodeURIComponent(conversationId)}`, { cache: 'no-store' });
        const data = await response.json();
        if (!data.ok) return;

        const atBottom = Math.abs(thread.scrollHeight - thread.scrollTop - thread.clientHeight) < 40;
        thread.innerHTML = data.messages.map((message) => {
            const mine = Number(message.sender_id) === Number(data.current_user_id);
            const mineClass = mine ? ' mine' : '';
            const deleted = message.body === 'Message deleted';
            const bodyClass = deleted ? ' class="muted deleted-message"' : '';
            const deleteForm = mine && !deleted
                ? `<form class="message-delete-form" method="POST" data-confirm="Delete this message?">
                        <input type="hidden" name="delete_message" value="1">
                        <input type="hidden" name="conversation_id" value="${escapeHtml(conversationId)}">
                        <input type="hidden" name="message_id" value="${escapeHtml(message.id)}">
                        <button class="message-delete-button" type="submit" title="Delete message">Delete</button>
                   </form>`
                : '';
            return `<div class="message-bubble${mineClass}"><span class="message-meta">${escapeHtml(message.fullname)} · ${escapeHtml(message.created_at)}</span><div${bodyClass}>${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>${deleteForm}</div>`;
        }).join('') || '<p class="muted">Send the first message.</p>';

        if (atBottom) {
            thread.scrollTop = thread.scrollHeight;
        }
    } catch (error) {
        return;
    }
}

if (document.querySelector('.top-actions')) {
    refreshHeaderCounts();
    setInterval(refreshHeaderCounts, 4000);
}

if (document.querySelector('.chat-window[data-conversation-id]')) {
    setInterval(refreshMessages, 3500);
}

// Stop page-entry animations after they finish so cursor movement cannot replay them.
window.addEventListener('load', () => {
    setTimeout(() => {
        document.querySelectorAll('.reveal, .fade-up, .slide-right, .scale-in').forEach((element) => {
            element.classList.add('animation-done');
        });
    }, 750);
});


// Profile photo removal confirmation modal.
const avatarRemoveModal = document.getElementById('avatarRemoveModal');
const avatarRemoveForm = document.querySelector('.avatar-remove-overlay-form');
const avatarRemoveOpen = document.querySelector('[data-avatar-remove-open]');
const avatarRemoveClose = document.querySelector('[data-avatar-remove-close]');
const avatarRemoveConfirm = document.querySelector('[data-avatar-remove-confirm]');

function closeAvatarRemoveModal() {
    avatarRemoveModal?.classList.remove('show');
    avatarRemoveModal?.setAttribute('aria-hidden', 'true');
}

avatarRemoveOpen?.addEventListener('click', () => {
    avatarRemoveModal?.classList.add('show');
    avatarRemoveModal?.setAttribute('aria-hidden', 'false');
});

avatarRemoveClose?.addEventListener('click', closeAvatarRemoveModal);

avatarRemoveModal?.addEventListener('click', (event) => {
    if (event.target === avatarRemoveModal) {
        closeAvatarRemoveModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAvatarRemoveModal();
    }
});

avatarRemoveConfirm?.addEventListener('click', () => {
    avatarRemoveForm?.submit();
});

// Generic confirmation for delete/review actions.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});





// Registers the logged-in browser so the Node consumer knows who is online.
(function () {
    if (!window.AIDLINK_CURRENT_USER_ID) {
        return;
    }

    let socket;

    try {
        socket = new WebSocket('ws://localhost:8081');
    } catch (error) {
        console.error('AidLink WebSocket failed:', error);
        return;
    }

    socket.addEventListener('open', function () {
        socket.send(JSON.stringify({
            type: 'register',
            user_id: Number(window.AIDLINK_CURRENT_USER_ID)
        }));
    });

    socket.addEventListener('message', function (event) {
        let packet;

        try {
            packet = JSON.parse(event.data);
        } catch (error) {
            return;
        }

        const payload = packet.payload || packet;

        if (!payload || payload.event_type !== 'messenger_message') {
            return;
        }

        playRingtone('messenger');

        // Reload only after RabbitMQ has been consumed and WebSocket pushes.
        if (
            window.AIDLINK_CURRENT_PAGE === 'messenger.php' &&
            Number(window.AIDLINK_ACTIVE_CONVERSATION_ID || 0) === Number(payload.conversation_id || 0)
        ) {
            window.location.reload();
            return;
        }

        setTimeout(function () {
            window.location.reload();
        }, 600);
    });
})();


// AidLink official RabbitMQ WebSocket client.
// Receiver confirms delivery through the browser, then reloads the messenger.
(function () {
    if (!window.AIDLINK_CURRENT_USER_ID) {
        return;
    }

    let socket;

    try {
        socket = new WebSocket('ws://localhost:8081');
    } catch (error) {
        console.error('AidLink WebSocket failed:', error);
        return;
    }

    socket.addEventListener('open', function () {
        socket.send(JSON.stringify({
            type: 'register',
            user_id: Number(window.AIDLINK_CURRENT_USER_ID)
        }));
    });

    socket.addEventListener('message', async function (event) {
        let packet;

        try {
            packet = JSON.parse(event.data);
        } catch (error) {
            return;
        }

        const payload = packet.payload || packet;

        if (!payload || payload.event_type !== 'messenger_message') {
            return;
        }

        // Mark the message as delivered from the receiver browser.
        // This avoids hard-coded folder problems in the Node server.
        try {
            const form = new FormData();
            form.append('message_id', String(payload.message_id || 0));
            form.append('secret', 'aidlink_realtime_secret');

            await fetch('realtime_deliver.php', {
                method: 'POST',
                body: form
            });
        } catch (error) {
            console.error('Delivery confirmation failed:', error);
        }

        playRingtone('messenger');

        if (
            window.AIDLINK_CURRENT_PAGE === 'messenger.php' &&
            Number(window.AIDLINK_ACTIVE_CONVERSATION_ID || 0) === Number(payload.conversation_id || 0)
        ) {
            window.location.reload();
            return;
        }

        setTimeout(function () {
            window.location.reload();
        }, 500);
    });
})();
