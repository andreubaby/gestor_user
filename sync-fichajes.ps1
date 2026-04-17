# sync-fichajes.ps1
# Ejecuta comandos artisan de fichajes con codificación UTF-8 correcta
# Uso: .\sync-fichajes.ps1 [--dry-run] [--revert=<archivo>] [--list-backups]

# Forzar UTF-8 en la consola de PowerShell
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::InputEncoding  = [System.Text.Encoding]::UTF8
$OutputEncoding           = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

$args_str = $args -join ' '

Write-Host ""
Write-Host "════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  Sync Fichajes — UTF-8 mode" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

docker exec php_gestor php artisan fichajes:sync-users $args_str

