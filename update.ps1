# GitHub ZIP URL
$repoZipUrl = "https://github.com/alexbalak21/Invoice-Document-Editor/archive/refs/heads/main.zip"

# Local project folder
$projectPath = "C:\xampp\htdocs\Invoice"

# Temporary files
$tempZip = Join-Path $env:TEMP "Invoice_update.zip"
$tempExtract = Join-Path $env:TEMP "Invoice_update"
$tempEnv = Join-Path $env:TEMP "Invoice_env_backup"

Write-Host "=== Invoice Update Started ===" -ForegroundColor Green

# Cleanup old temp files
if (Test-Path $tempZip) {
    Remove-Item $tempZip -Force
}

if (Test-Path $tempExtract) {
    Remove-Item $tempExtract -Recurse -Force
}

if (Test-Path $tempEnv) {
    Remove-Item $tempEnv -Force
}

# Backup .env
$envFile = Join-Path $projectPath ".env"

if (Test-Path $envFile) {
    Copy-Item $envFile $tempEnv -Force
    Write-Host ".env backed up"
}

# Download latest ZIP
Write-Host "Downloading latest version..."
Invoke-WebRequest -Uri $repoZipUrl -OutFile $tempZip

# Extract ZIP
Write-Host "Extracting ZIP..."
Expand-Archive -Path $tempZip -DestinationPath $tempExtract -Force

# GitHub creates a root folder
$sourceFolder = Get-ChildItem $tempExtract -Directory | Select-Object -First 1

# Copy all files and overwrite
Write-Host "Updating files..."
Copy-Item `
    -Path "$($sourceFolder.FullName)\*"