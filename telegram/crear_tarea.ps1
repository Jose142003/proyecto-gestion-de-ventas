# Script para crear tarea programada en Windows que ejecuta el bot cada minuto
# Ejecutar como Administrador: PowerShell -ExecutionPolicy Bypass .\crear_tarea.ps1

$taskName = "PIC_TelegramBot"
$scriptPath = "C:\laragon\www\proyecto\telegram\poll.php"
$phpPath = "C:\laragon\bin\php\php.exe"

# Verificar que PHP existe
if (-not (Test-Path $phpPath)) {
    # Buscar PHP automáticamente
    $phpPath = Get-Command php.exe -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source
    if (-not $phpPath) {
        Write-Host "❌ PHP no encontrado. Instala PHP o edita la variable `$phpPath en el script." -ForegroundColor Red
        exit 1
    }
}

Write-Host "Usando PHP: $phpPath" -ForegroundColor Cyan
Write-Host "Script: $scriptPath" -ForegroundColor Cyan

# Crear la tarea programada
$action = New-ScheduledTaskAction -Execute $phpPath -Argument $scriptPath
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 365)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -MultipleInstances IgnoreNew

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force

if ($?) {
    Write-Host "✅ Tarea '$taskName' creada exitosamente." -ForegroundColor Green
    Write-Host "El bot procesará mensajes de Telegram cada 1 minuto automáticamente." -ForegroundColor Green
    Write-Host ""
    Write-Host "Para probar: envía un mensaje a @piccavzlabot en Telegram" -ForegroundColor Yellow
    Write-Host "Para ver la tarea: abrir 'Task Scheduler' o ejecutar: Get-ScheduledTask -TaskName '$taskName'" -ForegroundColor Yellow
    Write-Host "Para eliminar: Unregister-ScheduledTask -TaskName '$taskName' -Confirm:`$false" -ForegroundColor Yellow
} else {
    Write-Host "❌ Error al crear la tarea." -ForegroundColor Red
}
