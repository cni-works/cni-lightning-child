[CmdletBinding()]
param(
	[switch] $Force
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSCommandPath
$themeSlug = 'cni-lightning-child'
$expectedUpdateUri = 'https://github.com/cni-works/cni-lightning-child'
$stylePath = Join-Path $themeRoot 'style.css'
$functionsPath = Join-Path $themeRoot 'functions.php'
$releaseDirectory = Join-Path $themeRoot 'release'
$temporaryRoot = Join-Path ([System.IO.Path]::GetTempPath()) ( $themeSlug + '-release-' + [System.Guid]::NewGuid().ToString('N') )
$temporaryTheme = Join-Path $temporaryRoot $themeSlug
$zipPath = $null

function Get-ThemeRelativePath {
	param(
		[Parameter(Mandatory = $true)]
		[string] $FullName
	)

	return $FullName.Substring($themeRoot.Length).TrimStart('\', '/')
}

function Test-ExcludedReleasePath {
	param(
		[Parameter(Mandatory = $true)]
		[string] $RelativePath
	)

	$normalized = $RelativePath.Replace('\', '/')
	$segments = @($normalized -split '/')
	$excludedDirectories = @('.git', '.github', 'release', 'node_modules', 'dist', 'tests')
	$excludedFiles = @('.gitattributes', '.gitignore', 'AGENTS.md', 'PROJECT-BRIEF.md', 'README.md', 'build-release.ps1', 'desktop.ini', '.DS_Store')

	foreach ($segment in $segments) {
		if ($excludedDirectories -contains $segment) {
			return $true
		}
	}

	$fileName = $segments[-1]
	if ($excludedFiles -contains $fileName) {
		return $true
	}

	if ($fileName -eq '.env' -or $fileName -like '.env.*' -or $fileName -like '*.zip' -or $fileName -like '*.log') {
		return $true
	}

	return $false
}

function Copy-ReleaseFile {
	param(
		[Parameter(Mandatory = $true)]
		[System.IO.FileInfo] $File
	)

	$relativePath = Get-ThemeRelativePath -FullName $File.FullName
	if (Test-ExcludedReleasePath -RelativePath $relativePath) {
		return
	}

	$destination = Join-Path $temporaryTheme $relativePath
	$destinationDirectory = Split-Path -Parent $destination
	if (-not (Test-Path -LiteralPath $destinationDirectory)) {
		$null = New-Item -ItemType Directory -Path $destinationDirectory -Force
	}

	Copy-Item -LiteralPath $File.FullName -Destination $destination -Force
}

if (-not (Test-Path -LiteralPath $stylePath -PathType Leaf)) {
	throw 'Required file style.css was not found.'
}

if (-not (Test-Path -LiteralPath $functionsPath -PathType Leaf)) {
	throw 'Required file functions.php was not found.'
}

$styleContent = [System.IO.File]::ReadAllText($stylePath, [System.Text.Encoding]::UTF8)
$versionMatch = [System.Text.RegularExpressions.Regex]::Match($styleContent, '(?mi)^\s*Version:\s*([^\r\n]+?)\s*$')
if (-not $versionMatch.Success) {
	throw 'The Version header was not found in style.css.'
}

$version = $versionMatch.Groups[1].Value.Trim()
if ($version -notmatch '^[0-9A-Za-z][0-9A-Za-z._-]*$') {
	throw "The Version value contains characters that cannot be used safely in a ZIP file name: $version"
}

$updateUriMatch = [System.Text.RegularExpressions.Regex]::Match($styleContent, '(?mi)^\s*Update URI:\s*([^\r\n]+?)\s*$')
if (-not $updateUriMatch.Success) {
	throw 'The Update URI header was not found in style.css.'
}

$updateUri = $updateUriMatch.Groups[1].Value.Trim()
if ([string]::IsNullOrWhiteSpace($updateUri)) {
	throw 'The Update URI value must not be empty.'
}
if ($updateUri -ne $expectedUpdateUri) {
	throw "The Update URI must be $expectedUpdateUri."
}

$zipPath = Join-Path $releaseDirectory ( "{0}-{1}.zip" -f $themeSlug, $version )
if ((Test-Path -LiteralPath $zipPath) -and -not $Force) {
	throw "A release ZIP for Version $version already exists. Use -Force to overwrite it: $zipPath"
}

try {
	if (-not (Test-Path -LiteralPath $releaseDirectory)) {
		$null = New-Item -ItemType Directory -Path $releaseDirectory -Force
	}

	if (Test-Path -LiteralPath $zipPath) {
		Remove-Item -LiteralPath $zipPath -Force
	}

	$null = New-Item -ItemType Directory -Path $temporaryTheme -Force

	foreach ($requiredFile in @($stylePath, $functionsPath)) {
		Copy-ReleaseFile -File (Get-Item -LiteralPath $requiredFile)
	}

	$optionalThemeJson = Join-Path $themeRoot 'theme.json'
	if (Test-Path -LiteralPath $optionalThemeJson -PathType Leaf) {
		Copy-ReleaseFile -File (Get-Item -LiteralPath $optionalThemeJson)
	}

	foreach ($directoryName in @('assets', 'inc', 'parts')) {
		$sourceDirectory = Join-Path $themeRoot $directoryName
		if (Test-Path -LiteralPath $sourceDirectory -PathType Container) {
			Get-ChildItem -LiteralPath $sourceDirectory -Recurse -File -Force | ForEach-Object {
				Copy-ReleaseFile -File $_
			}
		}
	}

	Add-Type -AssemblyName System.IO.Compression
	Add-Type -AssemblyName System.IO.Compression.FileSystem
	$zipArchive = [System.IO.Compression.ZipFile]::Open(
		$zipPath,
		[System.IO.Compression.ZipArchiveMode]::Create
	)
	try {
		Get-ChildItem -LiteralPath $temporaryRoot -Recurse -File -Force | ForEach-Object {
			$entryPath = $_.FullName.Substring($temporaryRoot.Length).TrimStart('\', '/').Replace('\', '/')
			$zipEntry = $zipArchive.CreateEntry($entryPath, [System.IO.Compression.CompressionLevel]::Optimal)
			$sourceStream = [System.IO.File]::OpenRead($_.FullName)
			$destinationStream = $zipEntry.Open()
			try {
				$sourceStream.CopyTo($destinationStream)
			} finally {
				$destinationStream.Dispose()
				$sourceStream.Dispose()
			}
		}
	} finally {
		$zipArchive.Dispose()
	}

	$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
	try {
		$entryPaths = @($archive.Entries | ForEach-Object { $_.FullName })
		$requiredEntries = @(
			"$themeSlug/style.css",
			"$themeSlug/functions.php",
			"$themeSlug/inc/updater/class-github-release-updater.php"
		)
		$validationErrors = New-Object System.Collections.Generic.List[string]

		foreach ($requiredEntry in $requiredEntries) {
			if ($entryPaths -notcontains $requiredEntry) {
				$validationErrors.Add("Missing required ZIP entry: $requiredEntry")
			}
		}

		foreach ($entryPath in $entryPaths) {
			if ($entryPath.Contains('\')) {
				$validationErrors.Add("ZIP entry uses a backslash instead of '/': $entryPath")
				continue
			}

			if (-not $entryPath.StartsWith("$themeSlug/", [System.StringComparison]::Ordinal)) {
				$validationErrors.Add("Entry is outside the $themeSlug top-level folder: $entryPath")
				continue
			}

			$relativeEntry = $entryPath.Substring(("$themeSlug/").Length)
			if ($relativeEntry.StartsWith("$themeSlug/", [System.StringComparison]::OrdinalIgnoreCase)) {
				$validationErrors.Add("Double $themeSlug folder detected: $entryPath")
			}

			if ($relativeEntry -and (Test-ExcludedReleasePath -RelativePath $relativeEntry)) {
				$validationErrors.Add("Excluded entry was found in ZIP: $entryPath")
			}
		}

		$styleEntry = $archive.GetEntry("$themeSlug/style.css")
		if ($null -ne $styleEntry) {
			$entryStream = $styleEntry.Open()
			$reader = New-Object System.IO.StreamReader($entryStream, [System.Text.Encoding]::UTF8)
			try {
				$archivedStyle = $reader.ReadToEnd()
			} finally {
				$reader.Dispose()
				$entryStream.Dispose()
			}

			$archivedVersionMatch = [System.Text.RegularExpressions.Regex]::Match($archivedStyle, '(?mi)^\s*Version:\s*([^\r\n]+?)\s*$')
			if (-not $archivedVersionMatch.Success -or $archivedVersionMatch.Groups[1].Value.Trim() -ne $version) {
				$validationErrors.Add('The Version in the archived style.css does not match the release file name.')
			}

			$archivedUpdateUriMatch = [System.Text.RegularExpressions.Regex]::Match($archivedStyle, '(?mi)^\s*Update URI:\s*([^\r\n]+?)\s*$')
			if (-not $archivedUpdateUriMatch.Success -or $archivedUpdateUriMatch.Groups[1].Value.Trim() -ne $updateUri) {
				$validationErrors.Add('The Update URI in the archived style.css does not match the source theme.')
			}
		}

		if ($validationErrors.Count -gt 0) {
			throw ( $validationErrors -join [System.Environment]::NewLine )
		}
	} finally {
		$archive.Dispose()
	}

	Write-Output "Release ZIP created and verified: $zipPath"
} catch {
	if ($zipPath -and (Test-Path -LiteralPath $zipPath)) {
		Remove-Item -LiteralPath $zipPath -Force
	}

	throw
} finally {
	if (Test-Path -LiteralPath $temporaryRoot) {
		Remove-Item -LiteralPath $temporaryRoot -Recurse -Force
	}
}
