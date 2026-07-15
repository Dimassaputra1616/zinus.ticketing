<x-app-layout>
    <style>
        @keyframes scannerSweep {
            0% { top: 1.1rem; opacity: .2; }
            8% { opacity: .95; }
            92% { opacity: .95; }
            100% { top: calc(100% - 1.35rem); opacity: .25; }
        }

        @keyframes statusBreath {
            0%, 100% { transform: scale(.9); opacity: .65; }
            50% { transform: scale(1.18); opacity: 1; }
        }

        .asset-scan-line {
            animation: scannerSweep 2.35s ease-in-out infinite alternate;
        }

        .asset-scan-dot {
            animation: statusBreath 1.8s ease-in-out infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .asset-scan-line,
            .asset-scan-dot {
                animation: none;
            }
        }
    </style>

    <script>
        (() => {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                window.location.replace(@js(route('assets.index')));
            }
        })();
    </script>

    <div class="min-h-screen bg-slate-50/60 pb-8 pt-0 lg:hidden">
        <div class="mx-auto w-full max-w-6xl space-y-4 lg:space-y-5">
            <h1 class="sr-only">Scan Asset</h1>

            <header class="hidden border-b border-slate-200 pb-5 lg:block">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500">
                            <span>Asset Management</span>
                            <svg class="h-3.5 w-3.5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="text-slate-700">Scan Asset</span>
                        </div>
                        <h1 class="text-2xl font-semibold tracking-normal text-slate-950 sm:text-3xl">Scan Asset</h1>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500">Scan QR label asset atau cari manual dari asset code, hostname, serial number, IP, dan nama device.</p>
                    </div>
                    <a
                        href="{{ route('assets.index') }}"
                        class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700 sm:self-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 4h18v8H3z" />
                            <path d="M7 4v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4" />
                            <path d="M5 20h14" />
                        </svg>
                        Asset Center
                    </a>
                </div>
            </header>

            <div
                x-data="assetScanner()"
                class="grid gap-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)] lg:gap-5"
            >
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm">
                    <div class="relative min-h-[calc(100dvh-10.75rem)] bg-slate-950 sm:min-h-[520px] lg:min-h-[560px]">
                        <video
                            x-ref="video"
                            class="absolute inset-0 h-full w-full object-cover"
                            autoplay
                            muted
                            playsinline
                        ></video>

                        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0,transparent_42%,rgba(2,6,23,0.72)_72%)] transition-opacity duration-300" :class="scanning ? 'opacity-95' : 'opacity-80'"></div>
                        <div class="pointer-events-none absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-3xl border border-emerald-300/80 shadow-[0_0_0_999px_rgba(2,6,23,0.18)] transition-all duration-300 sm:h-80 sm:w-80" :class="scanning ? 'scale-100 border-emerald-200/95' : 'scale-[.98] border-emerald-300/70'">
                            <span class="asset-scan-line absolute left-5 right-5 h-[3px] rounded-full bg-emerald-200 shadow-[0_0_18px_rgba(110,231,183,0.95)]"></span>
                            <span class="asset-scan-line absolute left-8 right-8 h-10 rounded-full bg-gradient-to-b from-emerald-300/22 to-transparent blur-sm"></span>
                            <span class="absolute inset-x-6 top-0 h-16 bg-gradient-to-b from-emerald-300/10 to-transparent"></span>
                            <span class="absolute inset-x-6 bottom-0 h-16 bg-gradient-to-t from-emerald-300/10 to-transparent"></span>
                            <span class="absolute -left-1 -top-1 h-10 w-10 rounded-tl-3xl border-l-4 border-t-4 border-emerald-300"></span>
                            <span class="absolute -right-1 -top-1 h-10 w-10 rounded-tr-3xl border-r-4 border-t-4 border-emerald-300"></span>
                            <span class="absolute -bottom-1 -left-1 h-10 w-10 rounded-bl-3xl border-b-4 border-l-4 border-emerald-300"></span>
                            <span class="absolute -bottom-1 -right-1 h-10 w-10 rounded-br-3xl border-b-4 border-r-4 border-emerald-300"></span>
                        </div>

                        <div class="absolute inset-x-0 top-0 flex items-center justify-between gap-3 p-4">
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/15 backdrop-blur">
                                <span class="h-2 w-2 rounded-full" :class="scanning ? 'asset-scan-dot bg-emerald-300' : 'bg-slate-400'"></span>
                                <span x-text="scanning ? 'Camera active' : 'Camera standby'"></span>
                            </div>
                            <button
                                type="button"
                                x-show="torchAvailable"
                                @click="toggleTorch()"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/15"
                                aria-label="Toggle flashlight"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M9 2h6" />
                                    <path d="M10 2v5l-2 3v10a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V10l-2-3V2" />
                                    <path d="M12 14v4" />
                                </svg>
                            </button>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 space-y-3 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent p-4 pt-20">
                            <p class="text-sm font-medium text-white" x-text="status"></p>
                            <p class="text-xs font-medium text-amber-100" x-show="error" x-text="error"></p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="start()"
                                    :disabled="starting || scanning"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 text-sm font-semibold text-white shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:bg-slate-500"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 7V5a1 1 0 0 1 1-1h2" />
                                        <path d="M17 4h2a1 1 0 0 1 1 1v2" />
                                        <path d="M20 17v2a1 1 0 0 1-1 1h-2" />
                                        <path d="M7 20H5a1 1 0 0 1-1-1v-2" />
                                        <path d="M7 12h10" />
                                    </svg>
                                    <span x-text="starting ? 'Opening camera...' : 'Mulai Scan'"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="stop()"
                                    x-show="scanning"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-white/10 px-4 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/15"
                                >
                                    Stop
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M11 4H4v7" />
                                    <path d="M20 4h-7" />
                                    <path d="M20 13v7h-7" />
                                    <path d="M4 13v7h7" />
                                    <path d="M8 8h.01M16 8h.01M8 16h.01M14 14h2v2" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Lookup Manual</h2>
                                <p class="mt-1 text-sm text-slate-500">Pakai ini kalau kamera tidak tersedia atau label QR sulit kebaca.</p>
                            </div>
                        </div>

                        <form x-ref="lookupForm" method="GET" action="{{ route('admin.assets.scan') }}" class="space-y-3">
                            <label for="asset-scan-query" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keyword asset</label>
                            <div class="flex gap-2">
                                <input
                                    x-ref="lookupInput"
                                    id="asset-scan-query"
                                    name="q"
                                    value="{{ $scanValue }}"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Asset code, hostname, serial, IP"
                                    class="h-11 min-w-0 flex-1 rounded-lg border-slate-200 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                <button
                                    type="submit"
                                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-white transition hover:bg-emerald-700"
                                    aria-label="Search asset"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="m20 20-3.5-3.5" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </section>

                    @if ($hasSearch && $assetResults->isEmpty())
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                            Asset tidak ditemukan untuk "{{ $scanValue }}". Coba pakai asset code, hostname, serial number, atau IP.
                        </div>
                    @endif

                    @if ($assetResults->isNotEmpty())
                        <section class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-slate-800">Hasil pencarian</h2>
                                <span class="text-xs font-medium text-slate-500">{{ $assetResults->count() }} hasil</span>
                            </div>
                            @foreach ($assetResults as $asset)
                                <a
                                    href="{{ route('assets.show', $asset) }}"
                                    class="block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-950">{{ $asset->name ?: $asset->hostname ?: $asset->asset_code }}</p>
                                            <p class="mt-1 truncate font-mono text-xs font-semibold text-emerald-700">{{ $asset->asset_code ?: 'No asset code' }}</p>
                                        </div>
                                        <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </div>
                                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <dt class="font-medium text-slate-400">Hostname</dt>
                                            <dd class="truncate font-semibold text-slate-700">{{ $asset->hostname ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-slate-400">Department</dt>
                                            <dd class="truncate font-semibold text-slate-700">{{ $asset->department?->name ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-slate-400">IP</dt>
                                            <dd class="truncate font-semibold text-slate-700">{{ $asset->ip_address ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-medium text-slate-400">Location</dt>
                                            <dd class="truncate font-semibold text-slate-700">{{ $asset->location ?: '-' }}</dd>
                                        </div>
                                    </dl>
                                </a>
                            @endforeach
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('assetScanner', () => ({
                    stream: null,
                    detector: null,
                    canvas: null,
                    canvasContext: null,
                    detectorMode: null,
                    scanning: false,
                    starting: false,
                    detecting: false,
                    detected: false,
                    torchAvailable: false,
                    torchEnabled: false,
                    status: 'Tekan Mulai Scan lalu arahkan kamera ke QR label asset.',
                    error: '',

                    async start() {
                        this.error = '';
                        this.detected = false;

                        if (!navigator.mediaDevices?.getUserMedia) {
                            this.error = 'Akses kamera tidak tersedia di browser ini.';
                            return;
                        }

                        if (!('BarcodeDetector' in window) && typeof window.ZinusQrDecoder !== 'function') {
                            this.error = 'Decoder QR belum siap. Refresh halaman atau pakai lookup manual.';
                            return;
                        }

                        this.starting = true;
                        this.status = 'Membuka kamera...';

                        try {
                            if ('BarcodeDetector' in window && !this.detector) {
                                this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                            }

                            this.stream = await navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: {
                                    facingMode: { ideal: 'environment' },
                                    width: { ideal: 1280 },
                                    height: { ideal: 720 },
                                },
                            });

                            this.$refs.video.srcObject = this.stream;
                            await this.$refs.video.play();
                            this.scanning = true;
                            this.detectorMode = this.detector ? 'native' : 'canvas';
                            this.status = 'Arahkan kotak ke QR label asset.';
                            this.detectTorch();
                            this.scanFrame();
                        } catch (error) {
                            this.error = 'Kamera gagal dibuka. Cek izin kamera atau pakai lookup manual.';
                            this.status = 'Camera standby';
                            this.stop();
                        } finally {
                            this.starting = false;
                        }
                    },

                    async scanFrame() {
                        if (!this.scanning || this.detected) {
                            return;
                        }

                        if (!this.detecting && this.$refs.video.readyState >= 2) {
                            this.detecting = true;

                            try {
                                const rawValue = this.detector
                                    ? await this.decodeWithNativeDetector()
                                    : this.decodeWithCanvas();

                                if (rawValue) {
                                    this.handleScan(rawValue);
                                    return;
                                }
                            } catch (error) {
                                if (this.detector && typeof window.ZinusQrDecoder === 'function') {
                                    this.detector = null;
                                    this.detectorMode = 'canvas';
                                    this.error = '';
                                } else {
                                    this.error = 'Scanner sempat gagal membaca frame. Coba dekatkan lagi labelnya.';
                                }
                            } finally {
                                this.detecting = false;
                            }
                        }

                        window.requestAnimationFrame(() => this.scanFrame());
                    },

                    async decodeWithNativeDetector() {
                        const codes = await this.detector.detect(this.$refs.video);

                        return codes?.[0]?.rawValue || '';
                    },

                    decodeWithCanvas() {
                        const decoder = window.ZinusQrDecoder;
                        const video = this.$refs.video;

                        if (typeof decoder !== 'function' || !video.videoWidth || !video.videoHeight) {
                            return '';
                        }

                        if (!this.canvas) {
                            this.canvas = document.createElement('canvas');
                            this.canvasContext = this.canvas.getContext('2d', { willReadFrequently: true });
                        }

                        const scanWidth = Math.min(video.videoWidth, 720);
                        const scanHeight = Math.max(1, Math.round(video.videoHeight * (scanWidth / video.videoWidth)));

                        this.canvas.width = scanWidth;
                        this.canvas.height = scanHeight;
                        this.canvasContext.drawImage(video, 0, 0, scanWidth, scanHeight);

                        const imageData = this.canvasContext.getImageData(0, 0, scanWidth, scanHeight);
                        const result = decoder(imageData.data, scanWidth, scanHeight, {
                            inversionAttempts: 'attemptBoth',
                        });

                        return result?.data || '';
                    },

                    handleScan(value) {
                        if (!value || this.detected) {
                            return;
                        }

                        this.detected = true;
                        this.status = 'QR terbaca. Membuka detail asset...';
                        this.stop();

                        const detailUrl = this.assetDetailUrl(value);

                        if (detailUrl) {
                            window.location.assign(detailUrl);
                            return;
                        }

                        this.$refs.lookupInput.value = value;
                        this.$refs.lookupForm.submit();
                    },

                    assetDetailUrl(value) {
                        try {
                            const url = new URL(value, window.location.origin);
                            const match = url.pathname.match(/^\/admin\/assets\/(\d+)\/?$/);

                            if (match) {
                                return url.pathname + url.search;
                            }
                        } catch (error) {
                            return null;
                        }

                        return null;
                    },

                    detectTorch() {
                        const track = this.stream?.getVideoTracks?.()[0];
                        const capabilities = track?.getCapabilities?.();
                        this.torchAvailable = Boolean(capabilities?.torch);
                    },

                    async toggleTorch() {
                        const track = this.stream?.getVideoTracks?.()[0];

                        if (!track) {
                            return;
                        }

                        this.torchEnabled = !this.torchEnabled;

                        try {
                            await track.applyConstraints({
                                advanced: [{ torch: this.torchEnabled }],
                            });
                        } catch (error) {
                            this.torchAvailable = false;
                            this.torchEnabled = false;
                        }
                    },

                    stop() {
                        this.stream?.getTracks?.().forEach((track) => track.stop());
                        this.stream = null;
                        this.scanning = false;
                        this.detectorMode = null;
                        this.torchAvailable = false;
                        this.torchEnabled = false;

                        if (this.$refs.video) {
                            this.$refs.video.srcObject = null;
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
