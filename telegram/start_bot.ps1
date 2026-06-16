$botDir = "C:\laragon\www\proyecto\telegram"
$offsetFile = "$botDir\last_offset.txt"
$logFile = "$botDir\bot.log"

Write-Host "=== PIC Bot de Telegram ===" -ForegroundColor Cyan
Write-Host "Iniciando bot..." -ForegroundColor Yellow

if (Test-Path $offsetFile) { Remove-Item $offsetFile -Force }

while ($true) {
    $date = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$date] Ejecutando bot_daemon.php..." -ForegroundColor Green
    php "$botDir\bot_daemon.php"
    $exitCode = $LASTEXITCODE
    $date = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$date] Bot detenido (código: $exitCode). Reiniciando en 5 segundos..." -ForegroundColor Yellow
    Start-Sleep -Seconds 5
}
