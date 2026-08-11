<#
.SYNOPSIS
Builds the distributable QRHunt plugin archive.

.DESCRIPTION
Creates build\staging\qrhunt from the runtime files only, installs Composer
production dependencies in that staging directory, and creates build\qrhunt.zip.

.EXAMPLE
powershell -ExecutionPolicy Bypass -File .\tools\build-release.ps1
#>

$ErrorActionPreference = 'Stop'

if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
	throw 'Composer is required to build the release, but it was not found in PATH.'
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$buildDirectory = Join-Path $projectRoot 'build'
$stagingDirectory = Join-Path $buildDirectory 'staging'
$packageDirectory = Join-Path $stagingDirectory 'qrhunt'
$archivePath = Join-Path $buildDirectory 'qrhunt.zip'

$runtimeFiles = @(
	'qrhunt.php',
	'readme.txt',
	'LICENSE'
)

$runtimeDirectories = @(
	'src',
	'templates',
	'assets',
	'languages'
)

foreach ($runtimeFile in $runtimeFiles) {
	if (-not (Test-Path -LiteralPath (Join-Path $projectRoot $runtimeFile) -PathType Leaf)) {
		throw "Required runtime file is missing: $runtimeFile."
	}
}

foreach ($runtimeDirectory in $runtimeDirectories) {
	if (-not (Test-Path -LiteralPath (Join-Path $projectRoot $runtimeDirectory) -PathType Container)) {
		throw "Required runtime directory is missing: $runtimeDirectory."
	}
}

foreach ($composerFile in @('composer.json', 'composer.lock')) {
	if (-not (Test-Path -LiteralPath (Join-Path $projectRoot $composerFile) -PathType Leaf)) {
		throw "Required Composer file is missing: $composerFile."
	}
}

if (Test-Path -LiteralPath $stagingDirectory) {
	Remove-Item -LiteralPath $stagingDirectory -Recurse -Force
}

if (Test-Path -LiteralPath $archivePath) {
	Remove-Item -LiteralPath $archivePath -Force
}

New-Item -ItemType Directory -Path $packageDirectory -Force | Out-Null

foreach ($runtimeFile in $runtimeFiles) {
	Copy-Item -LiteralPath (Join-Path $projectRoot $runtimeFile) -Destination $packageDirectory
}

foreach ($runtimeDirectory in $runtimeDirectories) {
	Copy-Item -LiteralPath (Join-Path $projectRoot $runtimeDirectory) -Destination $packageDirectory -Recurse
}

# Composer needs the lock file to resolve the production dependencies deterministically.
Copy-Item -LiteralPath (Join-Path $projectRoot 'composer.json') -Destination $packageDirectory
Copy-Item -LiteralPath (Join-Path $projectRoot 'composer.lock') -Destination $packageDirectory

Push-Location $packageDirectory
try {
	& composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
	if ($LASTEXITCODE -ne 0) {
		throw 'Composer could not install the production dependencies.'
	}
}
finally {
	Pop-Location
}

# composer.json accompanies the generated vendor directory in the distribution.
# composer.lock is only needed while building the staged package.
Remove-Item -LiteralPath (Join-Path $packageDirectory 'composer.lock') -Force

Get-ChildItem -LiteralPath $packageDirectory -Recurse -Force -Filter '.gitkeep' | Remove-Item -Force

if (-not (Test-Path -LiteralPath (Join-Path $packageDirectory 'vendor\autoload.php') -PathType Leaf)) {
	throw 'The package is missing vendor\autoload.php.'
}

$runtimePackages = @(
	'vendor\endroid\qr-code',
	'vendor\bacon\bacon-qr-code',
	'vendor\dasprid\enum'
)

foreach ($runtimePackage in $runtimePackages) {
	if (-not (Test-Path -LiteralPath (Join-Path $packageDirectory $runtimePackage) -PathType Container)) {
		throw "The package is missing runtime dependency: $runtimePackage."
	}
}

Compress-Archive -LiteralPath $packageDirectory -DestinationPath $archivePath -Force

Write-Host "Release archive created: $archivePath"
