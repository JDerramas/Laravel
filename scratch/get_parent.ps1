$p = Get-CimInstance Win32_Process -Filter 'ProcessId = 29648'
Write-Host "ProcessId: " $p.ProcessId
Write-Host "ParentProcessId: " $p.ParentProcessId
Write-Host "CommandLine: " $p.CommandLine
$parent = Get-CimInstance Win32_Process -Filter "ProcessId = $($p.ParentProcessId)"
Write-Host "Parent CommandLine: " $parent.CommandLine
