# Monitor de Cola de OpenWA

## 🟢 Worker Status: CORRIENDO

El worker está activo y procesando mensajes encolados.

### Comandos útiles (ejecuta en PowerShell):

```powershell
# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Limpiar trabajos fallidos
php artisan queue:flush

# Ver trabajos pendientes en la BD
php artisan tinker
# Luego en la consola:
DB::table('jobs')->count();
DB::table('jobs')->get();
DB::table('failed_jobs')->get();
```

### Ver logs en tiempo real:

```powershell
# Ver últimos 50 logs
Get-Content storage\logs\laravel.log -Tail 50

# Ver logs en tiempo real (actualización cada 2 segundos)
Get-Content storage\logs\laravel.log -Wait -Tail 20
```

### Verificar procesos PHP:

```powershell
# Ver procesos PHP activos
Get-Process php | Select-Object Name, Id, @{Name="Memory(MB)";Expression={[math]::Round($_.WorkingSet/1mb,2)}}

# Matar el worker (si necesitas)
Stop-Process -Name php -Force
```

## 🧪 Prueba: Enviar un mensaje de prueba

1. Abre http://localhost:8077/openwa/colaboraciones
2. Envía un mensaje a un trabajador o teléfono
3. Deberias ver "Mensaje encolado" en verde
4. En la tabla "Actividad Reciente" verás:
   - Estado: `pending` (inicialmente)
   - Después de unos segundos: `sent` ✅

## 📊 Monitoreo automatizado

Si querías monitorear continuamente, ejecuta esto en otra terminal PowerShell:

```powershell
# Monitorear en tiempo real
$worker = {
    while($true) {
        Clear-Host
        Write-Host "=== Queue Monitor ===" -ForegroundColor Cyan
        Write-Host "Tiempo: $(Get-Date)" -ForegroundColor Yellow
        Write-Host ""
        
        # Procesos PHP
        Write-Host "📊 Procesos PHP:" -ForegroundColor Green
        Get-Process php -ErrorAction SilentlyContinue | Select-Object Name, Id, @{Name="Memory(MB)";Expression={[math]::Round($_.WorkingSet/1mb,2)}} | Format-Table
        
        # Últimos logs
        Write-Host "📝 Últimos logs:" -ForegroundColor Green
        Get-Content storage\logs\laravel.log -Tail 5 2>/dev/null
        
        Start-Sleep -Seconds 5
    }
}

& $worker
```

## ⚙️ Configuración actual

- **Queue Driver**: `database` (SQLite)
- **Reintentos**: 3
- **Espera entre reintentos**: 60 segundos
- **Worker Status**: ✅ CORRIENDO
- **Base de datos**: SQLite local

## 🚨 Si el worker se detiene:

```powershell
cd C:\Users\alejandro\Documents\Proyectos\gestor_usuarios
php artisan queue:work --verbose
```

---

**Última actualización**: 21 de Mayo de 2026  
**Estado**: ✅ Operativo

