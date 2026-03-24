param(
    [Parameter(Mandatory = $false)]
    [string]$Version,

    [Parameter(Mandatory = $false)]
    [string]$OutputDir,

    [Parameter(Mandatory = $false)]
    [switch]$ArchiveExisting
)

$ErrorActionPreference = 'Stop'

$PluginSlug = 'dienstplan-verwaltung'
$DefaultOutputDir = 'C:\privat'
$ScriptDir = Split-Path -Path $MyInvocation.MyCommand.Definition -Parent
$RepoRoot = Resolve-Path (Join-Path $ScriptDir '..') | Select-Object -ExpandProperty Path

if ([string]::IsNullOrWhiteSpace($Version)) {
    $mainFile = Join-Path $RepoRoot 'dienstplan-verwaltung.php'
    if (-not (Test-Path $mainFile)) {
        throw "Main plugin file not found: $mainFile"
    }

    $versionLine = Select-String -Path $mainFile -Pattern "^\s*\*\s*Version\s*:\s*(.+)$" | Select-Object -First 1
    if (-not $versionLine) {
        throw 'Could not auto-detect version from plugin header. Pass -Version explicitly.'
    }

    $Version = $versionLine.Matches[0].Groups[1].Value.Trim()
}

if ([string]::IsNullOrWhiteSpace($OutputDir)) {
    $OutputDir = $DefaultOutputDir
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$OutputZip = Join-Path $OutputDir "$PluginSlug-$Version.zip"
$ArchiveDir = Join-Path $RepoRoot 'release'

$ExcludePatterns = @(
    '.git', '.github', '.gitignore', '.gitattributes', '.editorconfig',
    '.vscode', '.idea', 'node_modules', 'scripts', 'release', 'tools',
    '_release_stage*', '_zip_install_test*', '_zip_test_extract*',
    '*.zip', '*.log', '*.bak', '*.backup.php', '.DS_Store',
    'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
    'phpunit.xml', 'phpcs.xml', '.phpcs.xml.dist'
)

Write-Host "=== WordPress ZIP Builder ===" -ForegroundColor Cyan
Write-Host "Plugin:  $PluginSlug"
Write-Host "Version: $Version"
Write-Host "Source:  $RepoRoot"
Write-Host "Output:  $OutputZip"
Write-Host ''

if ($ArchiveExisting -and (Test-Path $OutputZip)) {
    if (-not (Test-Path $ArchiveDir)) {
        New-Item -ItemType Directory -Path $ArchiveDir -Force | Out-Null
    }

    $timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $archiveTarget = Join-Path $ArchiveDir ("$PluginSlug-$Version-$timestamp.zip")
    Move-Item -Path $OutputZip -Destination $archiveTarget -Force
    Write-Host "Archived existing ZIP: $archiveTarget" -ForegroundColor Yellow
}

$TempDir = Join-Path $env:TEMP ("$PluginSlug-wp-$Version")
$PluginTempRoot = Join-Path $TempDir $PluginSlug

if (Test-Path $TempDir) {
    Remove-Item -Path $TempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $PluginTempRoot -Force | Out-Null

Write-Host 'Copying files...'
Get-ChildItem -Path $RepoRoot -Force | Where-Object {
    $item = $_
    $exclude = $false

    foreach ($pattern in $ExcludePatterns) {
        if ($item.Name -like $pattern) {
            $exclude = $true
            break
        }
    }

    return -not $exclude
} | ForEach-Object {
    $dest = Join-Path $PluginTempRoot $_.Name
    if ($_.PSIsContainer) {
        Copy-Item -Path $_.FullName -Destination $dest -Recurse -Force
    }
    else {
        Copy-Item -Path $_.FullName -Destination $dest -Force
    }
    Write-Host "  Copied: $($_.Name)" -ForegroundColor DarkGray
}

if (Test-Path $OutputZip) {
    Remove-Item -Path $OutputZip -Force
}

Write-Host ''
Write-Host 'Creating ZIP...'
Compress-Archive -Path $PluginTempRoot -DestinationPath $OutputZip -CompressionLevel Optimal -Force

# Normalize entry paths to forward slashes for maximum compatibility.
Write-Host 'Normalizing ZIP entry paths...'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$TempFixedZip = Join-Path $env:TEMP ("$PluginSlug-fixed-$Version.zip")
if (Test-Path $TempFixedZip) {
    Remove-Item -Path $TempFixedZip -Force
}

$sourceZip = [System.IO.Compression.ZipFile]::OpenRead($OutputZip)
$targetZip = [System.IO.Compression.ZipFile]::Open($TempFixedZip, [System.IO.Compression.ZipArchiveMode]::Create)

foreach ($entry in $sourceZip.Entries) {
    if ([string]::IsNullOrWhiteSpace($entry.FullName)) {
        continue
    }

    $normalizedPath = $entry.FullName -replace '\\', '/'

    if ($normalizedPath.EndsWith('/')) {
        $targetZip.CreateEntry($normalizedPath) | Out-Null
        continue
    }

    $targetEntry = $targetZip.CreateEntry($normalizedPath, [System.IO.Compression.CompressionLevel]::Optimal)
    $sourceStream = $entry.Open()
    $targetStream = $targetEntry.Open()
    $sourceStream.CopyTo($targetStream)
    $targetStream.Close()
    $sourceStream.Close()
}

$sourceZip.Dispose()
$targetZip.Dispose()
Move-Item -Path $TempFixedZip -Destination $OutputZip -Force

Write-Host 'Validating ZIP...'
$zip = [System.IO.Compression.ZipFile]::OpenRead($OutputZip)
$entries = @($zip.Entries.FullName)
$expectedMainFile = "$PluginSlug/$PluginSlug.php"
$hasMain = $entries -contains $expectedMainFile
$top = @($entries | ForEach-Object { ($_ -split '[\\/]')[0] } | Where-Object { $_ } | Sort-Object -Unique)
$zip.Dispose()

if (-not $hasMain) {
    throw "Validation failed: missing $expectedMainFile"
}

if ($top.Count -ne 1 -or $top[0] -ne $PluginSlug) {
    throw "Validation failed: top-level folder must be '$PluginSlug' (detected: $($top -join ', '))"
}

if (Test-Path $TempDir) {
    Remove-Item -Path $TempDir -Recurse -Force
}

$zipSizeMB = [math]::Round((Get-Item $OutputZip).Length / 1MB, 2)

Write-Host ''
Write-Host 'DONE' -ForegroundColor Green
Write-Host "ZIP:   $OutputZip"
Write-Host "Size:  $zipSizeMB MB"
Write-Host "Main:  $expectedMainFile"
Write-Host "Top:   $($top -join ', ')"
