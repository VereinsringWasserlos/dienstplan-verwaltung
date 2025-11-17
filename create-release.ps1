# WordPress Plugin Release Creator
# Erstellt WordPress-konforme ZIP mit Forward Slashes

param([string]$Version = "0.9.1")

$ErrorActionPreference = "Stop"
$pluginDir = $PSScriptRoot
$parentDir = Split-Path $pluginDir -Parent
$tempDir = Join-Path $parentDir "temp-release"
$pluginName = "dienstplan-verwaltung"
$zipFile = Join-Path $parentDir "$pluginName.zip"

Write-Host "`nWordPress Plugin Release Creator" -ForegroundColor Cyan
Write-Host "Version: $Version`n" -ForegroundColor Yellow

if (Test-Path $tempDir) { Remove-Item -Recurse -Force $tempDir }
if (Test-Path $zipFile) { Remove-Item -Force $zipFile }

New-Item -ItemType Directory -Path "$tempDir\$pluginName" -Force | Out-Null

$exclude = '\.git|\.venv|node_modules|temp-release|\.backup\.|check-.*\.php|debug-.*\.php|fix-.*\.php|migrate-.*\.php|create-release\.ps1|documentation\\archive'

Get-ChildItem -Path $pluginDir -Recurse | Where-Object {
    $_.FullName -notmatch $exclude
} | ForEach-Object {
    $rel = $_.FullName.Substring($pluginDir.Length + 1)
    $target = Join-Path "$tempDir\$pluginName" $rel
    
    if ($_.PSIsContainer) {
        if (!(Test-Path $target)) { New-Item -ItemType Directory -Path $target -Force | Out-Null }
    } else {
        $dir = Split-Path $target -Parent
        if (!(Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
        Copy-Item $_.FullName $target -Force
    }
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$compressionLevel = [System.IO.Compression.CompressionLevel]::Optimal
$zipArchive = [System.IO.Compression.ZipFile]::Open($zipFile, [System.IO.Compression.ZipArchiveMode]::Create)

Get-ChildItem -Path "$tempDir\$pluginName" -Recurse -File | ForEach-Object {
    $relativePath = $_.FullName.Substring($tempDir.Length + 1)
    $zipPath = $relativePath.Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $_.FullName, $zipPath, $compressionLevel) | Out-Null
}

$zipArchive.Dispose()
Remove-Item -Recurse -Force $tempDir

Write-Host "WordPress Plugin ZIP erstellt!" -ForegroundColor Green
Write-Host "Datei: $zipFile" -ForegroundColor Cyan
Write-Host "Groesse: $([math]::Round((Get-Item $zipFile).Length/1MB, 2)) MB" -ForegroundColor Cyan

$zip = [System.IO.Compression.ZipFile]::OpenRead($zipFile)
Write-Host "`nZIP-Struktur (Forward Slashes):" -ForegroundColor Yellow
$zip.Entries | Select-Object -First 5 | ForEach-Object { Write-Host "   $($_.FullName)" -ForegroundColor Gray }
Write-Host "   ... $($zip.Entries.Count) Dateien gesamt" -ForegroundColor Gray
$zip.Dispose()

Write-Host "`nWordPress installiert als: wp-content/plugins/$pluginName/" -ForegroundColor Cyan
Write-Host "Pfad: $zipFile`n" -ForegroundColor Green
