$ErrorActionPreference='Stop'
$reader=Join-Path (Split-Path -Parent $PSScriptRoot) 'servidor_local.ps1'
$ast=[Management.Automation.Language.Parser]::ParseFile($reader,[ref]$null,[ref]$null)
$functions=@($ast.FindAll({param($node) $node -is [Management.Automation.Language.FunctionDefinitionAst] -and $node.Name -in @('Decode-Html','Company-From-File','Read-LatestLog','Read-ProfileLog')},$false)|ForEach-Object{$_.Extent.Text})
. ([scriptblock]::Create($functions -join "`n"))
$invalid=Read-ProfileLog (Join-Path $PSScriptRoot 'config-invalid.log.html') 'Compac'
if (-not $invalid.available -or -not $invalid.failed -or $invalid.processed -ne '0/0' -or $invalid.issues.Count -ne 1) { throw 'Invalid configuration was not detected' }
$fixture=Get-Content -LiteralPath (Join-Path $PSScriptRoot 'config-invalid.log.html') -Raw -Encoding UTF8
$validPath=Join-Path ([IO.Path]::GetTempPath()) ('backup-reader-test-'+[guid]::NewGuid().ToString('N')+'.html')
try {
    [IO.File]::WriteAllText($validPath,($fixture -replace 'Compac - .*?</p>','</p>'),[Text.Encoding]::UTF8)
    $valid=Read-ProfileLog $validPath 'Compac'
    if (-not $valid.available -or $valid.failed -or $valid.processed -ne '0/0') { throw 'Zero actions incorrectly classified as failure' }
    if ($valid.ReadBytes -ne $valid.TotalBytes) { throw 'Small log read more than once' }
    foreach($paddingSize in @(132000,263000,850000)) {
        $largeFixture=$fixture.Replace('</body>',('<!--'+('x' * $paddingSize)+'--></body>'))
        [IO.File]::WriteAllText($validPath,$largeFixture,[Text.Encoding]::UTF8)
        $large=Read-LatestLog $validPath
        if ($large.ReadBytes -ne $large.TotalBytes -or $large.Html -notmatch 'cs_1') { throw 'Incremental tail read transferred duplicate data' }
    }
} finally { [IO.File]::Delete($validPath) }
foreach($file in @('Comprobantes33.log.html','Data.log.html')) {
    $path=Join-Path 'C:\Users\MOISES INFORMATICA\Desktop' $file
    if (-not [IO.File]::Exists($path)) { continue }
    $latest=Read-LatestLog $path
    $full=[IO.File]::ReadAllText($path,[Text.Encoding]::UTF8)
    $target=[regex]::Match($full,'href="#(?<id>cs_\d+)">Ir al m[^<]+reciente','IgnoreCase').Groups['id'].Value
    $marker=[regex]::Match($full,"<h2\s+id=`"$target`"",'IgnoreCase')
    if ($latest.Html -ne $full.Substring($marker.Index)) { throw "Latest execution differs from full read: $file" }
    $profile=Read-ProfileLog $path $file
    if($file -eq 'Data.log.html'){
        if($profile.failedFiles.Count -ne 4){throw 'Both failed database and log files were not retained'}
        if(@($profile.failedFiles|Where-Object{$_.path -match '(?i)_log\.ldf$'}).Count -ne 2){throw 'Log paths were changed or omitted'}
    }
    Write-Output "$file : $($profile.date) | failed=$($profile.failed) | companies=$($profile.errors.Count) | read=$($latest.ReadBytes)/$($latest.TotalBytes) bytes"
}
Write-Output 'PASS: configuration error, zero actions, small-file transfer and latest-block equivalence'
