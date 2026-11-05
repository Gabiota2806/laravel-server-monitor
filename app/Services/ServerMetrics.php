<?php

namespace App\Services;

class ServerMetrics
{
    public function snapshot(): array
    {
        $metrics = match (PHP_OS_FAMILY) {
            'Windows' => $this->readWindows(),
            'Linux' => $this->readLinux(),
            default => null,
        };

        return [
            'hostname' => gethostname(),
            'os' => php_uname('s').' '.php_uname('r'),
            'php' => PHP_VERSION,
            'disk' => $this->disk(),
            'memory' => $this->memory($metrics),
            'cpu_pct' => $metrics['cpu_pct'] ?? null,
            'uptime_s' => $metrics['uptime_s'] ?? null,
            'cpu_temp_c' => $metrics['temp_c'] ?? null,
            'at' => now()->toIso8601String(),
        ];
    }

    private function disk(): array
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        return [
            'total_gb' => round($total / 1e9, 1),
            'free_gb' => round($free / 1e9, 1),
            'used_pct' => round(100 * ($total - $free) / $total, 1),
        ];
    }

    private function memory(?array $metrics): ?array
    {
        if (empty($metrics['mem_total_kb'])) {
            return null;
        }

        $total = $metrics['mem_total_kb'];
        $free = $metrics['mem_free_kb'];

        return [
            'total_mb' => intdiv($total, 1024),
            'available_mb' => intdiv($free, 1024),
            'used_pct' => round(100 * ($total - $free) / $total, 1),
        ];
    }

    // Ejecuta un script de PowerShell y devuelve sus datos como array (Windows)
    private function readWindows(): ?array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $script = <<<'PS'
$os = Get-CimInstance Win32_OperatingSystem
$cpu = (Get-CimInstance Win32_Processor |
Measure-Object -Property LoadPercentage -Average).Average
$temp = $null
try {
$ns = 'root/wmi'
$cls = 'MSAcpi_ThermalZoneTemperature'
$t = Get-CimInstance -Namespace $ns -ClassName $cls -ErrorAction Stop |
Select-Object -First 1
if ($t) { $temp = [math]::Round($t.CurrentTemperature / 10 - 273.15, 1) }
} catch {}
[pscustomobject]@{
mem_total_kb = [int64]$os.TotalVisibleMemorySize
mem_free_kb = [int64]$os.FreePhysicalMemory
cpu_pct = $cpu
uptime_s = [int64]((Get-Date) - $os.LastBootUpTime).TotalSeconds
temp_c = $temp
} | ConvertTo-Json -Compress
PS;

        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $output = shell_exec("powershell -NoProfile -NonInteractive -EncodedCommand $encoded");

        return $output ? json_decode($output, true) : null;
    }

    // Lee métricas nativas del sistema en Linux (/proc y /sys)
    private function readLinux(): ?array
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }

        // Memoria desde /proc/meminfo
        $memTotalKb = null;
        $memFreeKb = null;
        if (file_exists('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo') ?: '';
            preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $totalMatches);
            preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $availMatches);

            $memTotalKb = isset($totalMatches[1]) ? (int) $totalMatches[1] : null;
            $memFreeKb = isset($availMatches[1]) ? (int) $availMatches[1] : null;

            if ($memFreeKb === null && preg_match('/MemFree:\s+(\d+)\s+kB/', $meminfo, $freeMatches)) {
                $memFreeKb = (int) $freeMatches[1];
            }
        }

        // Tiempo encendido (uptime) desde /proc/uptime
        $uptimeS = null;
        if (file_exists('/proc/uptime')) {
            $uptimeContent = @file_get_contents('/proc/uptime') ?: '';
            $uptimeParts = explode(' ', trim($uptimeContent));
            if (isset($uptimeParts[0]) && is_numeric($uptimeParts[0])) {
                $uptimeS = (int) $uptimeParts[0];
            }
        }

        // Temperatura CPU desde /sys/class/thermal/
        $tempC = null;
        $thermalPaths = [
            '/sys/class/thermal/thermal_zone0/temp',
            '/sys/class/thermal/thermal_zone1/temp',
        ];
        foreach ($thermalPaths as $thermalPath) {
            if (file_exists($thermalPath)) {
                $raw = trim(@file_get_contents($thermalPath) ?: '');
                if (is_numeric($raw)) {
                    $tempC = round((float) $raw / 1000, 1);
                    break;
                }
            }
        }

        // Uso de CPU (%) leyendo /proc/stat
        $cpuPct = null;
        if (file_exists('/proc/stat')) {
            $stat1 = @file('/proc/stat');
            if ($stat1 && isset($stat1[0])) {
                usleep(50000); // 50ms para delta de actividad
                $stat2 = @file('/proc/stat');
                if ($stat2 && isset($stat2[0])) {
                    preg_match_all('/\d+/', $stat1[0], $m1);
                    preg_match_all('/\d+/', $stat2[0], $m2);
                    if (! empty($m1[0]) && ! empty($m2[0])) {
                        $prevTotal = array_sum($m1[0]);
                        $currTotal = array_sum($m2[0]);
                        $prevIdle = $m1[0][3] + ($m1[0][4] ?? 0);
                        $currIdle = $m2[0][3] + ($m2[0][4] ?? 0);
                        $diffTotal = $currTotal - $prevTotal;
                        $diffIdle = $currIdle - $prevIdle;
                        if ($diffTotal > 0) {
                            $cpuPct = round((1 - ($diffIdle / $diffTotal)) * 100, 1);
                        }
                    }
                }
            }
        }

        return [
            'mem_total_kb' => $memTotalKb,
            'mem_free_kb' => $memFreeKb,
            'cpu_pct' => $cpuPct,
            'uptime_s' => $uptimeS,
            'temp_c' => $tempC,
        ];
    }
}
