# WordPress Plugin Release Creator
# Erstellt eine WordPress-konforme ZIP-Datei für Releases
#
# Usage: .\create-release.ps1
# Output: dienstplan-verwaltung-{VERSION}.zip im übergeordneten Ordner

param(
    [string]$Version = "0.9.0"
)

$ErrorActionPreference = "Stop"
$ProgressPreference = 'SilentlyContinue'

# Farben für Output
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Error { Write-Host $args -ForegroundColor Red }
function Write-Info { Write-Host $args -ForegroundColor Cyan }

Write-Info "🚀 Erstelle WordPress Plugin Release..."
Write-Info "Version: $Version"

# Pfade
$pluginDir = $PSScriptRoot
$parentDir = Split-Path $pluginDir -Parent
$tempDir = Join-Path $parentDir "temp-release"
$pluginName = "dienstplan-verwaltung"
$tempPluginDir = Join-Path $tempDir $pluginName
$zipFile = Join-Path $parentDir "$pluginName-$Version.zip"

# Cleanup alter Dateien
if (Test-Path $tempDir) {
    Write-Info "🧹 Räume alten Release-Ordner auf..."
    Remove-Item -Recurse -Force $tempDir
}

if (Test-Path $zipFile) {
    Write-Info "🧹 Lösche alte ZIP-Datei..."
    Remove-Item -Force $zipFile
}

# Temporären Ordner erstellen
Write-Info "📁 Erstelle temporären Release-Ordner..."
New-Item -ItemType Directory -Path $tempPluginDir -Force | Out-Null

# Dateien kopieren (mit Ausschlüssen)
Write-Info "📋 Kopiere Plugin-Dateien..."

$excludeItems = @(
    '.git',
    '.gitignore',
    '.gitattributes',
    'node_modules',
    'temp-release',
    '*.backup.*',
    '*.old',
    '*.bak',
    'check-*.php',
    'debug-*.php',
    'fix-*.php',
    'migrate-*.php',
    'TAG_FIX_ANLEITUNG.md',
    'create-release.ps1',
    'documentation\archive'
)

# Alle Dateien kopieren
Get-ChildItem -Path $pluginDir -Recurse | ForEach-Object {
    $relativePath = $_.FullName.Substring($pluginDir.Length + 1)
    
    # Prüfen ob Datei/Ordner ausgeschlossen werden soll
    $shouldExclude = $false
    foreach ($exclude in $excludeItems) {
        if ($relativePath -like $exclude -or $relativePath -like "$exclude\*") {
            $shouldExclude = $true
            break
        }
    }
    
    if (-not $shouldExclude) {
        $targetPath = Join-Path $tempPluginDir $relativePath
        
        if ($_.PSIsContainer) {
            # Ordner erstellen
            if (-not (Test-Path $targetPath)) {
                New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
            }
        } else {
            # Datei kopieren
            $targetDir = Split-Path $targetPath -Parent
            if (-not (Test-Path $targetDir)) {
                New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
            }
            Copy-Item -Path $_.FullName -Destination $targetPath -Force
        }
    }
}

Write-Success "✅ Dateien kopiert"

# ZIP erstellen
Write-Info "📦 Erstelle ZIP-Archiv..."
Compress-Archive -Path "$tempDir\*" -DestinationPath $zipFile -Force

# Cleanup
Write-Info "🧹 Räume temporäre Dateien auf..."
Remove-Item -Recurse -Force $tempDir

# Erfolgsmeldung
if (Test-Path $zipFile) {
    $zipSize = (Get-Item $zipFile).Length / 1MB
    Write-Success "`n✅ Release erfolgreich erstellt!"
    Write-Info "📦 Datei: $zipFile"
    Write-Info "📊 Größe: $([math]::Round($zipSize, 2)) MB"
    Write-Info "`n🎯 Bereit für Upload zu WordPress oder GitHub Release"
    
    # ZIP-Struktur anzeigen
    Write-Info "`n📂 ZIP-Struktur:"
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::OpenRead($zipFile)
    $zip.Entries | Select-Object -First 10 | ForEach-Object {
        Write-Host "   $($_.FullName)"
    }
    if ($zip.Entries.Count -gt 10) {
        Write-Host "   ... und $($zip.Entries.Count - 10) weitere Dateien"
    }
    $zip.Dispose()
    
} else {
    Write-Error "`n❌ Fehler beim Erstellen der ZIP-Datei"
    exit 1
}

Write-Success "`n✨ Fertig!`n"
