[CmdletBinding()]
param(
	[string]$OutputDirectory = (Join-Path (Split-Path -Parent (Split-Path -Parent $PSScriptRoot)) 'dist')
)

$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
$pluginName = Split-Path -Leaf $pluginRoot
$version = '1.2.0'

& (Join-Path $PSScriptRoot 'verify.ps1')
if ($LASTEXITCODE -ne 0) {
	throw 'Verification failed.'
}

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
$stageRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("mrnpe-package-" + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Force -Path $stageRoot | Out-Null
$stagePlugin = Join-Path $stageRoot $pluginName

try {
	New-Item -ItemType Directory -Force -Path $stagePlugin | Out-Null
	Get-ChildItem -LiteralPath $pluginRoot -Force |
		Where-Object { $_.Name -notin @('tests', 'tools', '.git', '.codegraph') } |
		Copy-Item -Destination $stagePlugin -Recurse

	$zip = Join-Path $OutputDirectory ("$pluginName-$version.zip")
	if (Test-Path -LiteralPath $zip) {
		Remove-Item -LiteralPath $zip -Force
	}

	# Compress-Archive writes Windows backslashes into ZIP entries. Some Linux
	# unzip implementations treat those as literal filename characters, so use
	# bsdtar to guarantee portable forward-slash entries for WordPress hosting.
	& tar.exe -a -cf $zip -C $stageRoot $pluginName
	if ($LASTEXITCODE -ne 0) {
		throw 'Portable ZIP creation failed.'
	}

	$entries = @(tar.exe -tf $zip)
	if ($entries.Count -eq 0 -or $entries | Where-Object { $_ -match '\\' }) {
		throw 'Package contains invalid or non-portable paths.'
	}

	Write-Host "Package: $zip" -ForegroundColor Green
}
finally {
	if (Test-Path -LiteralPath $stageRoot) {
		$resolvedStage = (Resolve-Path -LiteralPath $stageRoot).Path
		$resolvedTemp = (Resolve-Path -LiteralPath ([System.IO.Path]::GetTempPath())).Path
		if ($resolvedStage.StartsWith($resolvedTemp, [System.StringComparison]::OrdinalIgnoreCase)) {
			Remove-Item -LiteralPath $resolvedStage -Recurse -Force
		}
	}
}
