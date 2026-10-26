# =====================================================
# Script maestro de inicialización del sistema
# Ejecutar DESPUÉS de que Laragon tenga MySQL corriendo
# =====================================================
param([switch]$SkipPuestos)

$ErrorActionPreference = "Stop"
$mysql  = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$php    = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"
$back   = "C:\Users\hamil\Documents\proyectos en desarrollo\Mapa Electoral\backend"
$scripts= "C:\Users\hamil\Documents\proyectos en desarrollo\Mapa Electoral\scripts"

function Log($msg, $color="Cyan") { Write-Host $msg -ForegroundColor $color }

# 1. Verificar MySQL
Log "`n=== 1/5  Verificando MySQL ===" "Yellow"
try {
    & $mysql -u root -e "SELECT 1" 2>&1 | Out-Null
    Log "✅ MySQL disponible" "Green"
} catch {
    Log "❌ MySQL no disponible. Abre Laragon y ejecuta este script de nuevo." "Red"
    exit 1
}

# 2. Insertar puestos reales (si no fue hecho antes)
if (-not $SkipPuestos) {
    Log "`n=== 2/5  Cargando puestos de votación reales ===" "Yellow"
    $total = & $mysql -u root mapa_electoral -e "SELECT COUNT(*) FROM puestos_votacion" 2>&1
    if ($total -match "27[0-9]") {
        Log "✅ Puestos ya cargados ($($total.Trim()))" "Green"
    } else {
        Log "Insertando puestos OSM..." "Cyan"
        Get-Content "$scripts\insertar_puestos_reales.sql" | & $mysql -u root mapa_electoral
        Log "✅ Puestos insertados" "Green"
    }
} else {
    Log "`n=== 2/5  Puestos omitidos (SkipPuestos)" "Gray"
}

# 3. Ejecutar seeder de simpatizantes
Log "`n=== 3/5  Generando simpatizantes ===" "Yellow"
& $php "$back\artisan" db:seed --class=SimpatizanteSeeder --force 2>&1 | ForEach-Object { Log "  $_" }
Log "✅ Simpatizantes creados" "Green"

# 4. Verificar resultados
Log "`n=== 4/5  Verificando datos ===" "Yellow"
$res = & $mysql -u root mapa_electoral -e "
    SELECT
        (SELECT COUNT(*) FROM puestos_votacion)    AS puestos,
        (SELECT SUM(total_mesas) FROM puestos_votacion) AS mesas,
        (SELECT COUNT(*) FROM simpatizantes WHERE campana_id = 1) AS simpatizantes,
        (SELECT COUNT(*) FROM territorios)         AS territorios;
" 2>&1
Log $res "Green"

# 5. Limpiar cache Laravel
Log "`n=== 5/5  Limpiando cache ===" "Yellow"
& $php "$back\artisan" cache:clear 2>&1 | Out-Null
& $php "$back\artisan" config:clear 2>&1 | Out-Null
Log "✅ Cache limpiado" "Green"

Log "`n🎉 Sistema listo!" "Green"
Log "Backend: http://127.0.0.1:8000" "Cyan"
Log "Frontend: http://localhost:5173" "Cyan"
