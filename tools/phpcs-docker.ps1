$ErrorActionPreference = 'Stop'

$projectDir = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

$mappedArgs = @()
foreach ($arg in $args) {
    $mapped = $arg

    if ($arg.StartsWith($projectDir, [System.StringComparison]::OrdinalIgnoreCase)) {
        $relative = $arg.Substring($projectDir.Length).TrimStart('\', '/')
        $mapped = '/app/' + ($relative -replace '\\', '/')
    }

    $mappedArgs += $mapped
}

$dockerArgs = @(
    'run', '--rm',
    '-v', "${projectDir}:/app",
    '-w', '/app',
    'composer:2',
    'php', 'vendor/bin/phpcs'
) + $mappedArgs

& docker @dockerArgs
exit $LASTEXITCODE
