$proc = Get-Process -Id 29648
$handle = $proc.Handle
$path = (Get-Item "/proc/$($proc.Id)/cwd").Target
Write-Host "Target: $path"
