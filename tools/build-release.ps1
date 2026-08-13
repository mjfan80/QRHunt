<#
.SYNOPSIS
Builds and optionally verifies the distributable QRHunt plugin archive.

.DESCRIPTION
Creates build\staging\qrhunt from runtime files only, installs Composer
production dependencies, and creates build\qrhunt.zip. When -WordPressPath
is provided, it runs Plugin Check against a temporary sibling plugin copy.

.EXAMPLE
powershell -ExecutionPolicy Bypass -File .\tools\build-release.ps1

.EXAMPLE
powershell -ExecutionPolicy Bypass -File .\tools\build-release.ps1 -WordPressPath 'D:\Local-Sites\app\public'
#>

[CmdletBinding()]
param(
	[Parameter()]
	[string]$WordPressPath,
	[Parameter()]
	[switch]$CheckWorktree
)

$ErrorActionPreference = 'Stop'

$projectRoot       = Split-Path -Parent $PSScriptRoot
$buildDirectory    = Join-Path $projectRoot 'build'
$stagingDirectory  = Join-Path $buildDirectory 'staging'
$packageDirectory  = Join-Path $stagingDirectory 'qrhunt'
$archivePath       = Join-Path $buildDirectory 'qrhunt.zip'
$checkPluginSlug   = 'qrhunt-release-check'
$checkPluginPath   = $null
$checkPluginCreated = $false
$failureMessage    = $null
$currentCheck      = 'Preflight'
$checks            = [ordered]@{
	'Composer validation'       = 'PENDING'
	'PHP syntax'                = 'PENDING'
	'Release structure'         = 'PENDING'
	'Plugin Check'              = if ( [string]::IsNullOrWhiteSpace( $WordPressPath ) ) { 'SKIPPED' } else { 'PENDING' }
	'Worktree whitespace check' = if ( $CheckWorktree ) { 'PENDING' } else { 'SKIPPED' }
	'Archive'                   = 'PENDING'
}

try {
	if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
		throw 'Composer is required to build the release, but it was not found in PATH.'
	}

	$currentCheck = 'Composer validation'
	& composer validate --no-check-publish
	if ($LASTEXITCODE -ne 0) {
		throw 'Composer validation failed.'
	}
	$checks[$currentCheck] = 'PASS'

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

	$currentCheck = 'PHP syntax'
	$phpFiles = @(
		Join-Path $projectRoot 'qrhunt.php'
	) + @(
		Get-ChildItem -Path (Join-Path $projectRoot 'src'), (Join-Path $projectRoot 'templates') -Recurse -File -Filter '*.php' |
			Select-Object -ExpandProperty FullName
	)

	foreach ($phpFile in $phpFiles) {
		& php -l $phpFile
		if ($LASTEXITCODE -ne 0) {
			throw "PHP syntax validation failed: $phpFile."
		}
	}
	$checks[$currentCheck] = 'PASS'

	$currentCheck = 'Release structure'
	$catalogFiles = @(
		'languages\qrhunt.pot',
		'languages\qrhunt-it_IT.po',
		'languages\qrhunt-it_IT.mo'
	)

	foreach ($catalogFile in $catalogFiles) {
		$catalogPath = Join-Path $projectRoot $catalogFile

		if (-not (Test-Path -LiteralPath $catalogPath -PathType Leaf) -or 0 -eq (Get-Item -LiteralPath $catalogPath).Length) {
			throw "Required translation catalog is missing or empty: $catalogFile."
		}
	}

	$composerManifest = Get-Content -LiteralPath (Join-Path $projectRoot 'composer.json') -Raw | ConvertFrom-Json
	$composerLock     = Get-Content -LiteralPath (Join-Path $projectRoot 'composer.lock') -Raw | ConvertFrom-Json
	$developmentPackages = @()

	if ($null -ne $composerManifest.'require-dev') {
		$developmentPackages += $composerManifest.'require-dev'.PSObject.Properties.Name
	}

	if ($null -ne $composerLock.'packages-dev') {
		$developmentPackages += $composerLock.'packages-dev' | ForEach-Object { $_.name }
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

	foreach ($developmentPackage in $developmentPackages | Sort-Object -Unique) {
		$developmentPackagePath = Join-Path $packageDirectory ( 'vendor\' + ( $developmentPackage -replace '/', '\\' ) )

		if (Test-Path -LiteralPath $developmentPackagePath) {
			throw "The release package contains the development dependency: $developmentPackage."
		}
	}

	foreach ($developmentBinary in @('vendor\bin\phpunit', 'vendor\bin\phpunit.bat')) {
		if (Test-Path -LiteralPath (Join-Path $packageDirectory $developmentBinary) -PathType Leaf) {
			throw "The release package contains the development binary: $developmentBinary."
		}
	}

	$checks[$currentCheck] = 'PASS'

	if ($CheckWorktree) {
		$currentCheck = 'Worktree whitespace check'
		& git diff --check
		if ($LASTEXITCODE -ne 0) {
			throw 'Git whitespace validation failed.'
		}
		$checks[$currentCheck] = 'PASS'
	}

	if (-not [string]::IsNullOrWhiteSpace($WordPressPath)) {
		$currentCheck = 'Plugin Check'
		$wpRoot = (Resolve-Path -LiteralPath $WordPressPath).Path

		if (-not (Test-Path -LiteralPath (Join-Path $wpRoot 'wp-load.php') -PathType Leaf)) {
			throw 'The supplied WordPress path does not contain wp-load.php.'
		}

		$pluginsDirectory = Join-Path $wpRoot 'wp-content\plugins'
		if (-not (Test-Path -LiteralPath $pluginsDirectory -PathType Container)) {
			throw 'The supplied WordPress path does not contain wp-content\plugins.'
		}

		if (-not (Get-Command wp -ErrorAction SilentlyContinue)) {
			throw 'WP-CLI is required for Plugin Check, but it was not found in PATH.'
		}

		$checkPluginPath = Join-Path $pluginsDirectory $checkPluginSlug
		if (Test-Path -LiteralPath $checkPluginPath) {
			throw "Refusing to overwrite the existing temporary Plugin Check directory: $checkPluginPath."
		}

		New-Item -ItemType Directory -Path $checkPluginPath -Force | Out-Null
		$checkPluginCreated = $true
		Get-ChildItem -LiteralPath $packageDirectory -Force | Copy-Item -Destination $checkPluginPath -Recurse -Force

		$pluginCheckErrorFile = [System.IO.Path]::GetTempFileName()
		try {
			$pluginCheckOutput = & wp --path=$wpRoot plugin check $checkPluginSlug --slug=qrhunt --format=strict-json 2>$pluginCheckErrorFile
			$pluginCheckExitCode = $LASTEXITCODE
			$pluginCheckErrorOutput = Get-Content -LiteralPath $pluginCheckErrorFile -Raw

			if (-not [string]::IsNullOrWhiteSpace($pluginCheckErrorOutput)) {
				Write-Host $pluginCheckErrorOutput
			}

			if ($pluginCheckExitCode -ne 0) {
				throw "Plugin Check could not complete (exit code $pluginCheckExitCode)."
			}

			try {
				# Local can emit PHP extension startup diagnostics on stdout before WP-CLI's JSON.
				# Only that known non-Plugin-Check prefix is removed; all other non-JSON output fails parsing.
				$pluginCheckJson = $pluginCheckOutput -join "`n"
				$phpStartupWarningPattern = '(?s)^\s*(Warning: PHP Startup:.*? in Unknown on line 0)\s*'

				if ($pluginCheckJson -match $phpStartupWarningPattern) {
					Write-Warning $matches[1]
					$pluginCheckJson = $pluginCheckJson -replace $phpStartupWarningPattern, ''
				}

				if ( '[]' -eq $pluginCheckJson.Trim() -or $pluginCheckJson.Trim() -match '^Success: .+$' ) {
					# Current Plugin Check emits this WP-CLI success line instead of [] for zero findings.
					$pluginCheckFindings = @()
				}
				else {
					$pluginCheckFindings = @( $pluginCheckJson | ConvertFrom-Json )
				}
			}
			catch {
				throw 'Plugin Check did not return valid strict JSON output.'
			}

			if ($pluginCheckFindings.Count -gt 0) {
				$pluginCheckFindings | ConvertTo-Json -Depth 10
				throw "Plugin Check reported $($pluginCheckFindings.Count) finding(s)."
			}
		}
		finally {
			Remove-Item -LiteralPath $pluginCheckErrorFile -Force -ErrorAction SilentlyContinue
		}

		$checks[$currentCheck] = 'PASS'
	}

	$currentCheck = 'Archive'
	Add-Type -AssemblyName System.IO.Compression

	$archiveStream = [System.IO.File]::Open(
		$archivePath,
		[System.IO.FileMode]::Create,
		[System.IO.FileAccess]::Write,
		[System.IO.FileShare]::None
	)

	try {
		$archive = New-Object System.IO.Compression.ZipArchive(
			$archiveStream,
			[System.IO.Compression.ZipArchiveMode]::Create,
			$false
		)

		try {
			# ZIP entry names always use forward slashes, regardless of the build platform.
			$archive.CreateEntry('qrhunt/') | Out-Null

			foreach ($packageFile in Get-ChildItem -LiteralPath $packageDirectory -Recurse -File -Force) {
				$relativePath = $packageFile.FullName.Substring($stagingDirectory.Length).TrimStart('\', '/')
				$entryName = $relativePath.Replace('\', '/')
				$entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
				$entryStream = $entry.Open()

				try {
					$sourceStream = [System.IO.File]::OpenRead($packageFile.FullName)
					try {
						$sourceStream.CopyTo($entryStream)
					}
					finally {
						$sourceStream.Dispose()
					}
				}
				finally {
					$entryStream.Dispose()
				}
			}
		}
		finally {
			$archive.Dispose()
		}
	}
	finally {
		$archiveStream.Dispose()
	}

	if (-not (Test-Path -LiteralPath $archivePath -PathType Leaf) -or 0 -eq (Get-Item -LiteralPath $archivePath).Length) {
		throw 'The release archive was not created.'
	}

	$archiveStream = [System.IO.File]::OpenRead($archivePath)
	try {
		$archive = New-Object System.IO.Compression.ZipArchive(
			$archiveStream,
			[System.IO.Compression.ZipArchiveMode]::Read,
			$false
		)

		try {
			$entryNames = @( $archive.Entries | ForEach-Object { $_.FullName } )

			if ($entryNames | Where-Object { $_.Contains('\') }) {
				throw 'The release archive contains ZIP entries with backslash separators.'
			}

			foreach ($requiredEntry in @('qrhunt/qrhunt.php', 'qrhunt/readme.txt')) {
				if ($requiredEntry -notin $entryNames) {
					throw "The release archive is missing required entry: $requiredEntry."
				}
			}

			$hasUnexpectedRootEntry = @( $entryNames | Where-Object { -not $_.StartsWith('qrhunt/') } ).Count -gt 0
			if ('qrhunt/' -notin $entryNames -or $hasUnexpectedRootEntry) {
				throw 'The release archive does not have qrhunt/ as its only root directory.'
			}
		}
		finally {
			$archive.Dispose()
		}
	}
	finally {
		$archiveStream.Dispose()
	}

	$checks[$currentCheck] = 'PASS'
}
catch {
	$failureMessage = $_.Exception.Message
	if ($checks.Contains($currentCheck) -and 'PASS' -ne $checks[$currentCheck] -and 'SKIPPED' -ne $checks[$currentCheck]) {
		$checks[$currentCheck] = 'FAIL'
	}
}
finally {
	if ($checkPluginCreated -and $null -ne $checkPluginPath -and (Test-Path -LiteralPath $checkPluginPath)) {
		Remove-Item -LiteralPath $checkPluginPath -Recurse -Force
	}

	Write-Host ''
	Write-Host 'Release verification summary:'
	foreach ($check in $checks.GetEnumerator()) {
		Write-Host ( '{0}: {1}' -f $check.Key, $check.Value )
	}

	if ($null -ne $failureMessage) {
		Write-Host "Release build: FAIL - $failureMessage"
		exit 1
	}

	Write-Host "Release build: PASS - $archivePath"
}
