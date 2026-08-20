<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Borne de Pointage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body { overflow: hidden; -webkit-user-select: none; user-select: none; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .pulse { animation: pulse 2s infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900 h-screen flex flex-col">

    {{-- Setup screen --}}
    <div id="setup-screen" class="flex-1 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Configuration Borne</h1>
                <p class="text-gray-500 mt-1">Entrez le token de la borne</p>
            </div>
            <input type="text" id="device-token" placeholder="Token de la borne..."
                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-lg focus:outline-none focus:border-blue-500 mb-4">
            <button onclick="connectKiosk()" id="connect-btn"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-lg font-semibold transition">
                Connecter
            </button>
            <p id="setup-error" class="mt-3 text-red-500 text-center hidden"></p>
        </div>
    </div>

    {{-- Scanner screen --}}
    <div id="scanner-screen" class="flex-1 flex flex-col hidden">
        {{-- Header --}}
        <div class="bg-black/30 backdrop-blur px-6 py-3 flex justify-between items-center">
            <div>
                <h1 class="text-white text-xl font-bold" id="campus-name">-</h1>
                <p class="text-white/60 text-sm" id="kiosk-name">-</p>
            </div>
            <div class="text-right">
                <div class="text-white text-3xl font-mono font-bold" id="clock">--:--</div>
                <div class="text-white/60 text-sm" id="date">-</div>
            </div>
        </div>

        {{-- Scanner area --}}
        <div class="flex-1 flex items-center justify-center p-6">
            <div id="scan-area" class="text-center">
                <div id="qr-reader" class="mx-auto rounded-2xl overflow-hidden" style="width: 350px; height: 350px;"></div>
                <p class="text-white/70 mt-4 text-lg pulse">Presentez votre badge QR devant la camera</p>
            </div>

            {{-- Result overlay --}}
            <div id="result-overlay" class="hidden fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm z-50">
                <div id="result-card" class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-lg text-center fade-in">
                    <div id="result-icon" class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center text-5xl"></div>
                    <h2 id="result-name" class="text-2xl font-bold text-gray-800 mb-1"></h2>
                    <p id="result-message" class="text-lg text-gray-600 mb-2"></p>
                    <p id="result-time" class="text-3xl font-mono font-bold text-gray-800 mb-2"></p>
                    <div id="result-details" class="text-sm text-gray-500"></div>

                    {{-- Photo preview --}}
                    <div id="photo-preview" class="hidden mt-4">
                        <img id="photo-img" class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200">
                    </div>
                </div>
            </div>
        </div>

        {{-- Camera for photo --}}
        <video id="photo-camera" class="hidden" autoplay playsinline></video>
        <canvas id="photo-canvas" class="hidden"></canvas>
    </div>

<script>
    const API_BASE = '{{ url("/api") }}';
    let deviceToken = localStorage.getItem('kiosk_device_token') || '';
    let kioskInfo = null;
    let html5QrCode = null;
    let photoStream = null;
    let isProcessing = false;

    // Auto-connect si token sauvegarde
    if (deviceToken) {
        document.getElementById('device-token').value = deviceToken;
        connectKiosk();
    }

    async function connectKiosk() {
        const token = document.getElementById('device-token').value.trim();
        if (!token) return;

        const btn = document.getElementById('connect-btn');
        const error = document.getElementById('setup-error');
        btn.textContent = 'Connexion...';
        btn.disabled = true;
        error.classList.add('hidden');

        try {
            const res = await fetch(`${API_BASE}/kiosk/auth`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ device_token: token })
            });

            const data = await res.json();

            if (!res.ok) {
                error.textContent = data.message || 'Erreur de connexion';
                error.classList.remove('hidden');
                btn.textContent = 'Connecter';
                btn.disabled = false;
                return;
            }

            deviceToken = token;
            kioskInfo = data.kiosk;
            localStorage.setItem('kiosk_device_token', token);

            document.getElementById('campus-name').textContent = kioskInfo.campus.name;
            document.getElementById('kiosk-name').textContent = kioskInfo.name;

            document.getElementById('setup-screen').classList.add('hidden');
            document.getElementById('scanner-screen').classList.remove('hidden');

            startClock();
            startScanner();
            startPhotoCamera();

        } catch (e) {
            error.textContent = 'Erreur reseau. Verifiez la connexion.';
            error.classList.remove('hidden');
            btn.textContent = 'Connecter';
            btn.disabled = false;
        }
    }

    function startClock() {
        function update() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('date').textContent = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        update();
        setInterval(update, 1000);
    }

    async function startScanner() {
        html5QrCode = new Html5Qrcode("qr-reader");

        try {
            await html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1 },
                onScanSuccess,
                () => {} // ignore errors
            );
        } catch (err) {
            // Fallback : essayer la camera frontale
            try {
                await html5QrCode.start(
                    { facingMode: "user" },
                    { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1 },
                    onScanSuccess,
                    () => {}
                );
            } catch (err2) {
                console.error('Camera error:', err2);
            }
        }
    }

    async function startPhotoCamera() {
        try {
            // Utiliser la camera frontale pour la photo
            photoStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: "user", width: 480, height: 480 }
            });
            document.getElementById('photo-camera').srcObject = photoStream;
        } catch (e) {
            console.log('Photo camera not available:', e);
        }
    }

    function capturePhoto() {
        if (!photoStream) return null;

        const video = document.getElementById('photo-camera');
        const canvas = document.getElementById('photo-canvas');
        canvas.width = 480;
        canvas.height = 480;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, 480, 480);

        return canvas.toDataURL('image/jpeg', 0.7).split(',')[1]; // base64 sans prefixe
    }

    async function onScanSuccess(qrToken) {
        if (isProcessing) return;
        isProcessing = true;

        // Pause le scanner
        try { await html5QrCode.pause(); } catch(e) {}

        // Capturer la photo
        const photo = capturePhoto();

        try {
            const res = await fetch(`${API_BASE}/kiosk/scan`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    device_token: deviceToken,
                    qr_token: qrToken,
                    photo: photo
                })
            });

            const data = await res.json();
            showResult(data, res.ok);

        } catch (e) {
            showResult({ message: 'Erreur reseau. Reessayez.', employee: '' }, false);
        }

        // Reprendre le scanner apres 4 secondes
        setTimeout(async () => {
            document.getElementById('result-overlay').classList.add('hidden');
            isProcessing = false;
            try { await html5QrCode.resume(); } catch(e) {}
        }, 4000);
    }

    function showResult(data, success) {
        const overlay = document.getElementById('result-overlay');
        const icon = document.getElementById('result-icon');
        const name = document.getElementById('result-name');
        const message = document.getElementById('result-message');
        const time = document.getElementById('result-time');
        const details = document.getElementById('result-details');
        const photoPreview = document.getElementById('photo-preview');

        if (success) {
            const isCheckIn = data.type === 'check-in';
            icon.className = 'w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center text-5xl ' +
                (isCheckIn ? 'bg-green-100' : 'bg-blue-100');
            icon.innerHTML = isCheckIn ? '<span class="text-green-600">&#10003;</span>' : '<span class="text-blue-600">&#8594;</span>';
            name.textContent = data.employee || '';
            message.textContent = data.message || '';
            time.textContent = data.timestamp || '';
            time.className = 'text-3xl font-mono font-bold mb-2 ' + (isCheckIn ? 'text-green-600' : 'text-blue-600');

            let detailsHtml = `<span class="px-3 py-1 rounded-full text-sm ${isCheckIn ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">${data.type === 'check-in' ? 'ENTREE' : 'SORTIE'} - ${data.shift || ''}</span>`;
            if (data.is_late) {
                detailsHtml += `<br><span class="text-red-500 font-semibold mt-2 inline-block">Retard: ${data.late_minutes} min</span>`;
            }
            if (data.duration_hours) {
                detailsHtml += `<br><span class="text-gray-600 mt-1 inline-block">Duree: ${data.duration_hours}h</span>`;
            }
            details.innerHTML = detailsHtml;

            if (data.photo_url) {
                document.getElementById('photo-img').src = data.photo_url;
                photoPreview.classList.remove('hidden');
            } else {
                photoPreview.classList.add('hidden');
            }
        } else {
            icon.className = 'w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center text-5xl bg-red-100';
            icon.innerHTML = '<span class="text-red-600">&#10007;</span>';
            name.textContent = data.employee || 'Erreur';
            message.textContent = data.message || 'Scan echoue';
            time.textContent = '';
            details.innerHTML = '';
            photoPreview.classList.add('hidden');
        }

        overlay.classList.remove('hidden');
    }
</script>
</body>
</html>
