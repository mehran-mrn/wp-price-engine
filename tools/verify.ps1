[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
$errors = [System.Collections.Generic.List[string]]::new()

Write-Host ''
Write-Host 'MRN Gold & Currency Pricing verification' -ForegroundColor Cyan
Write-Host '============================='

$phpFiles = @(Get-ChildItem -LiteralPath $pluginRoot -Filter '*.php' -File -Recurse)
foreach ($file in $phpFiles) {
	& php -l $file.FullName | Out-Null
	if ($LASTEXITCODE -ne 0) {
		$errors.Add("PHP syntax: $($file.FullName)")
	}
}
Write-Host "[ ok ] PHP syntax ($($phpFiles.Count) files)" -ForegroundColor Green

$jsonFiles = @(Get-ChildItem -LiteralPath $pluginRoot -Filter '*.json' -File -Recurse)
foreach ($file in $jsonFiles) {
	try {
		Get-Content -LiteralPath $file.FullName -Raw | ConvertFrom-Json | Out-Null
	}
	catch {
		$errors.Add("Invalid JSON: $($file.FullName)")
	}
}
Write-Host "[ ok ] JSON ($($jsonFiles.Count) files)" -ForegroundColor Green

& php (Join-Path $pluginRoot 'tests\test-pricing-formula.php')
if ($LASTEXITCODE -ne 0) {
	$errors.Add('Pricing formula tests failed.')
}
else {
	Write-Host '[ ok ] Pricing formula tests' -ForegroundColor Green
}

& php (Join-Path $pluginRoot 'tests\test-html-rate-parser.php')
if ($LASTEXITCODE -ne 0) {
	$errors.Add('HTML rate parser tests failed.')
}
else {
	Write-Host '[ ok ] HTML rate parser tests' -ForegroundColor Green
}

$forbidden = @(
	'eval\s*\(',
	'base64_decode\s*\(',
	'wp_remote_get\s*\(',
	'http://(?:api\.|[a-z0-9.-]+/api/)'
)

$sourceFiles = @(
	Get-ChildItem -LiteralPath $pluginRoot -File -Recurse |
		Where-Object { $_.Extension -in @('.php', '.js') -and $_.FullName -notmatch '[\\/]tests[\\/]' }
)
foreach ($file in $sourceFiles) {
	$content = Get-Content -LiteralPath $file.FullName -Raw
	foreach ($pattern in $forbidden) {
		if ($content -match $pattern) {
			$errors.Add("Forbidden pattern '$pattern': $($file.FullName)")
		}
	}
}
Write-Host "[ ok ] Security patterns ($($sourceFiles.Count) files)" -ForegroundColor Green

if ($errors.Count -gt 0) {
	Write-Host ''
	foreach ($problem in $errors) {
		Write-Host "[fail] $problem" -ForegroundColor Red
	}
	exit 1
}

Write-Host ''
Write-Host 'PASSED' -ForegroundColor Green
