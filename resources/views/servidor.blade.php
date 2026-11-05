<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor del Servidor</title>
    <!-- Tailwind CSS y Chart.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkBg: '#090d16',
                        darkCard: '#111726',
                        darkBorder: '#1e293b'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .pulse-dot {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 dark:bg-darkBg dark:text-slate-100 min-h-screen transition-colors duration-200 antialiased font-sans p-4 sm:p-6 lg:p-8">

    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header / Barra Superior -->
        <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-darkBorder">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Monitor del Servidor</h1>
                    <span id="liveBadge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-dot"></span>
                        <span id="liveText">En vivo</span>
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Última sincronización: <span id="at" class="font-medium text-slate-700 dark:text-slate-300">{{ $data['at'] }}</span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Botón Pausar / Reanudar -->
                <button id="togglePollingBtn" onclick="togglePolling()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <span id="pauseIcon">⏸</span>
                    <span id="pauseText">Pausar</span>
                </button>

                <!-- Botón Tema Claro / Oscuro -->
                <button onclick="toggleTheme()" class="p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg border border-slate-300 dark:border-slate-700 transition" title="Alternar tema">
                    <span class="dark:hidden">🌙</span>
                    <span class="hidden dark:inline">☀️</span>
                </button>
            </div>
        </header>

        <!-- Tarjetas de Información General del Sistema -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm">
                <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Equipo</span>
                <div class="text-lg font-bold mt-1 truncate" title="{{ $data['hostname'] }}">{{ $data['hostname'] }}</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm">
                <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Sistema Operativo</span>
                <div class="text-lg font-bold mt-1 truncate" title="{{ $data['os'] }}">{{ $data['os'] }}</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm">
                <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Versión PHP</span>
                <div class="text-lg font-bold mt-1 text-indigo-500">{{ $data['php'] }}</div>
            </div>
            <div class="p-4 rounded-xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm">
                <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Uptime</span>
                <div id="uptimeDisplay" class="text-lg font-bold mt-1 text-slate-700 dark:text-slate-200">Calculando...</div>
            </div>
        </section>

        <!-- Gráficos Principales con Líneas Fluidas -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Tarjeta CPU -->
            <div class="p-5 rounded-2xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Uso de CPU</span>
                        <span id="cpuBadge" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-500 border border-cyan-500/20">Cian</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span id="cpuVal" class="text-4xl font-extrabold tracking-tight text-cyan-500">{{ $data['cpu_pct'] ?? '0' }}%</span>
                        <span class="text-xs text-slate-400">en tiempo real</span>
                    </div>
                </div>
                <div class="h-44 mt-4">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>

            <!-- Tarjeta Memoria RAM -->
            <div class="p-5 rounded-2xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Memoria RAM</span>
                        <span id="memBadge" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                            {{ $data['memory']['available_mb'] ?? 0 }} MB libres
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span id="memVal" class="text-4xl font-extrabold tracking-tight text-emerald-500">{{ $data['memory']['used_pct'] ?? '0' }}%</span>
                        <span id="memDetail" class="text-xs text-slate-400">Total: {{ round(($data['memory']['total_mb'] ?? 0)/1024, 1) }} GB</span>
                    </div>
                </div>
                <div class="h-44 mt-4">
                    <canvas id="memChart"></canvas>
                </div>
            </div>

            <!-- Tarjeta Temperatura CPU -->
            <div class="p-5 rounded-2xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Temperatura CPU</span>
                        <span id="tempBadge" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20">Térmico</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span id="tempVal" class="text-4xl font-extrabold tracking-tight text-amber-500">{{ $data['cpu_temp_c'] ?? 'N/D' }}°C</span>
                        <span class="text-xs text-slate-400">sensor térmico</span>
                    </div>
                </div>
                <div class="h-44 mt-4">
                    <canvas id="tempChart"></canvas>
                </div>
            </div>

        </section>

        <!-- Sección de Almacenamiento en Disco -->
        <section class="p-6 rounded-2xl bg-white dark:bg-darkCard border border-slate-200 dark:border-darkBorder shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-base font-semibold">Almacenamiento Local (Disco)</h3>
                    <p class="text-xs text-slate-400">Volumen principal del servidor</p>
                </div>
                <div class="text-right">
                    <span id="diskPct" class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $data['disk']['used_pct'] }}%</span>
                    <span class="text-xs text-slate-400 block">ocupado</span>
                </div>
            </div>

            <!-- Barra de Progreso -->
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                <div id="diskBar" class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ $data['disk']['used_pct'] }}%"></div>
            </div>

            <div class="flex justify-between items-center text-xs text-slate-400 mt-3 font-medium">
                <span>Libre: <strong id="diskFree" class="text-slate-700 dark:text-slate-200">{{ $data['disk']['free_gb'] }} GB</strong></span>
                <span>Total: <strong id="diskTotal" class="text-slate-700 dark:text-slate-200">{{ $data['disk']['total_gb'] }} GB</strong></span>
            </div>
        </section>

    </div>

    <!-- Script de Gráficos y Actualización en Tiempo Real -->
    <script>
        const MAX_POINTS = 20;
        let isPolling = true;
        let timer = null;

        // Historial inicial
        const initialTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const labels = [initialTime];
        const cpuData = [{{ is_numeric($data['cpu_pct']) ? $data['cpu_pct'] : 0 }}];
        const memData = [{{ isset($data['memory']['used_pct']) ? $data['memory']['used_pct'] : 0 }}];
        const tempData = [{{ is_numeric($data['cpu_temp_c']) ? $data['cpu_temp_c'] : 0 }}];

        function createChartConfig(canvasId, label, colorHex, initialData, yMax = 100) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 160);
            gradient.addColorStop(0, colorHex + '55'); // 33% opacidad
            gradient.addColorStop(1, colorHex + '00'); // transparente

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [...labels],
                    datasets: [{
                        label: label,
                        data: [...initialData],
                        borderColor: colorHex,
                        borderWidth: 2.5,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        pointBackgroundColor: colorHex
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#94a3b8',
                            bodyColor: '#f8fafc',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { size: 10 },
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            min: 0,
                            max: yMax,
                            grid: { color: 'rgba(100, 116, 139, 0.1)' },
                            ticks: {
                                color: '#64748b',
                                font: { size: 10 },
                                stepSize: yMax === 100 ? 25 : undefined
                            }
                        }
                    }
                }
            });
        }

        const cpuChart = createChartConfig('cpuChart', 'CPU %', '#06b6d4', cpuData, 100);
        const memChart = createChartConfig('memChart', 'RAM %', '#10b981', memData, 100);
        const tempChart = createChartConfig('tempChart', 'Temp °C', '#f59e0b', tempData, 100);

        function formatUptime(seconds) {
            if (!seconds || isNaN(seconds)) return 'No disponible';
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const parts = [];
            if (days > 0) parts.push(`${days}d`);
            if (hours > 0 || days > 0) parts.push(`${hours}h`);
            parts.push(`${minutes}m`);
            return parts.join(' ');
        }

        // Formato inicial de uptime
        document.getElementById('uptimeDisplay').textContent = formatUptime({{ $data['uptime_s'] ?? 0 }});

        async function actualizar() {
            if (!isPolling) return;
            try {
                const res = await fetch('/api/metrics');
                if (!res.ok) return;
                const d = await res.json();
                
                const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                // Textos principales
                document.getElementById('at').textContent = d.at;
                document.getElementById('uptimeDisplay').textContent = formatUptime(d.uptime_s);
                
                // Disco
                if (d.disk) {
                    document.getElementById('diskPct').textContent = `${d.disk.used_pct}%`;
                    document.getElementById('diskBar').style.width = `${d.disk.used_pct}%`;
                    document.getElementById('diskFree').textContent = `${d.disk.free_gb} GB`;
                    document.getElementById('diskTotal').textContent = `${d.disk.total_gb} GB`;
                }

                // CPU
                const cpuVal = d.cpu_pct !== null ? d.cpu_pct : 0;
                document.getElementById('cpuVal').textContent = d.cpu_pct !== null ? `${d.cpu_pct}%` : 'N/D';

                // Memoria
                const memVal = d.memory?.used_pct !== null ? d.memory.used_pct : 0;
                document.getElementById('memVal').textContent = d.memory ? `${d.memory.used_pct}%` : 'N/D';
                if (d.memory) {
                    document.getElementById('memBadge').textContent = `${d.memory.available_mb} MB libres`;
                }

                // Temp
                const tempVal = d.cpu_temp_c !== null ? d.cpu_temp_c : 0;
                document.getElementById('tempVal').textContent = d.cpu_temp_c !== null ? `${d.cpu_temp_c}°C` : 'N/D';

                // Actualizar buffer de gráficos
                pushData(cpuChart, timeStr, cpuVal);
                pushData(memChart, timeStr, memVal);
                pushData(tempChart, timeStr, tempVal);

            } catch (e) {
                console.error("Error al actualizar métricas:", e);
            }
        }

        function pushData(chart, label, value) {
            chart.data.labels.push(label);
            chart.data.datasets[0].data.push(value);
            if (chart.data.labels.length > MAX_POINTS) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }
            chart.update('none'); // Update suave sin resetear animaciones
        }

        function togglePolling() {
            isPolling = !isPolling;
            const pauseText = document.getElementById('pauseText');
            const pauseIcon = document.getElementById('pauseIcon');
            const liveBadge = document.getElementById('liveBadge');
            const liveText = document.getElementById('liveText');

            if (isPolling) {
                pauseText.textContent = 'Pausar';
                pauseIcon.textContent = '⏸';
                liveText.textContent = 'En vivo';
                liveBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
            } else {
                pauseText.textContent = 'Reanudar';
                pauseIcon.textContent = '▶';
                liveText.textContent = 'Pausado';
                liveBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
            }
        }

        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
        }

        timer = setInterval(actualizar, 5000);
    </script>
</body>
</html>