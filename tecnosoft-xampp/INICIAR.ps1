$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$php = 'C:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $php)) { throw 'No se encontró PHP en C:\xampp. Ajusta la ruta en INICIAR.ps1.' }
if (-not (Test-Path -LiteralPath 'app/config.local.php')) {
    Copy-Item -LiteralPath 'app/config.example.php' -Destination 'app/config.local.php'
    Write-Host 'Se creó app/config.local.php. Revisa sus datos y enciende MySQL en XAMPP.'
}
Write-Host 'Primera instalación: tools/import.php. Si ya tienes datos: respalda y ejecuta tools/migrate.php. No vuelvas a importar la base nueva.'
Write-Host 'TecnoSoft estará en http://127.0.0.1:8080. Ctrl+C detiene el servidor.'
& $php -S 127.0.0.1:8080 -t public
