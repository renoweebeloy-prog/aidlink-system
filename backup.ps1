$source = "..\public\exports"
$destination = "..\storage\backups"
$date = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

if (!(Test-Path $destination)) {
    New-Item -ItemType Directory -Path $destination | Out-Null
}

Copy-Item -Path $source -Destination "$destination\exports_$date" -Recurse -Force
Write-Host "Backup completed successfully: exports_$date"
