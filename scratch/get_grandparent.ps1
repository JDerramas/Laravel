$parent = Get-CimInstance Win32_Process -Filter "ProcessId = 25388"
Write-Host "Parent ProcessId: " $parent.ProcessId
Write-Host "Parent ParentProcessId: " $parent.ParentProcessId
Write-Host "Parent CommandLine: " $parent.CommandLine

$grandParent = Get-CimInstance Win32_Process -Filter "ProcessId = $($parent.ParentProcessId)"
Write-Host "GrandParent CommandLine: " $grandParent.CommandLine
