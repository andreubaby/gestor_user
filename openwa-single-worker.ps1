param(
    [string]$Container = "php_gestor",
    [int]$SleepSeconds = 3,
    [int]$Tries = 3,
    [int]$Timeout = 120
)

$ErrorActionPreference = 'Stop'

Write-Host "== OpenWA single worker ==" -ForegroundColor Cyan
Write-Host "Container: $Container"
Write-Host "Config: queue:work --sleep=$SleepSeconds --tries=$Tries --timeout=$Timeout"

$top = docker top $Container
if ($LASTEXITCODE -ne 0) {
    throw "No se pudo inspeccionar el contenedor $Container"
}

$pids = @()
foreach ($line in $top) {
    if ($line -match 'php artisan queue:work') {
        $parts = ($line.Trim() -split '\s+')
        if ($parts.Length -ge 2 -and $parts[1] -match '^\d+$') {
            $pids += [int]$parts[1]
        }
    }
}

$pids = $pids | Select-Object -Unique

if ($pids.Count -gt 0) {
    Write-Host "Deteniendo workers existentes: $($pids -join ', ')" -ForegroundColor Yellow
    foreach ($workerPid in $pids) {
        try {
            Stop-Process -Id $workerPid -Force -ErrorAction Stop
        } catch {
            Write-Warning "No se pudo detener el PID ${workerPid}: $($_.Exception.Message)"
        }
    }
    Start-Sleep -Seconds 1
} else {
    Write-Host "No había workers previos activos." -ForegroundColor DarkGray
}

docker exec -d $Container php artisan queue:work --sleep=$SleepSeconds --tries=$Tries --timeout=$Timeout | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw "No se pudo iniciar el nuevo worker"
}

Start-Sleep -Seconds 1

Write-Host "Worker único iniciado correctamente." -ForegroundColor Green
Write-Host ""
docker top $Container



