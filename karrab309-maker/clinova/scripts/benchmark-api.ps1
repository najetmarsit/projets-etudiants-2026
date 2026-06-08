param(
    [string]$BaseUrl = "http://localhost:8000/api",
    [string]$Token = "",
    [int]$Iterations = 3
)

if (-not $Token) {
    Write-Host "Usage: .\scripts\benchmark-api.ps1 -Token 'eyJ...'" -ForegroundColor Yellow
    exit 1
}

$headers = @{
    Authorization = "Bearer $Token"
    Accept        = "application/json"
}

$endpoints = @(
    "/auth/me",
    "/dashboard/stats",
    "/dashboard/analytics",
    "/patients?per_page=20",
    "/notifications?limit=20",
    "/messages?per_page=20"
)

Write-Host "Benchmark Clinova API — $BaseUrl" -ForegroundColor Cyan
Write-Host ("Iterations: {0}`n" -f $Iterations)

$results = @()

foreach ($ep in $endpoints) {
    $times = @()
    $cacheHit = $false
    for ($i = 1; $i -le $Iterations; $i++) {
        $sw = [System.Diagnostics.Stopwatch]::StartNew()
        try {
            $r = Invoke-WebRequest -Uri ($BaseUrl + $ep) -Headers $headers -UseBasicParsing
            $sw.Stop()
            $times += $sw.ElapsedMilliseconds
            if ($r.Headers["X-Cache"]) { $cacheHit = $true }
        } catch {
            $sw.Stop()
            $times += -1
        }
        Start-Sleep -Milliseconds 100
    }
    $valid = $times | Where-Object { $_ -ge 0 }
    $avg = if ($valid.Count) { [math]::Round(($valid | Measure-Object -Average).Average, 1) } else { "ERR" }
    $min = if ($valid.Count) { ($valid | Measure-Object -Minimum).Minimum } else { "-" }
    $results += [PSCustomObject]@{
        Endpoint = $ep
        AvgMs    = $avg
        MinMs    = $min
        Cache    = $(if ($cacheHit) { "api" } else { "-" })
    }
}

$results | Format-Table -AutoSize
Write-Host "Astuce: relancer — la 2e série doit montrer des temps plus bas (cache Laravel + client)." -ForegroundColor Green
