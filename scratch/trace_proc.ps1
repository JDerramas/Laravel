$p = Get-CimInstance Win32_Process -Filter 'ProcessId = 29648'
Write-Host "PID 29648 CommandLine: " $p.CommandLine
$parent = Get-CimInstance Win32_Process -Filter "ProcessId = $($p.ParentProcessId)"
Write-Host "Parent ProcessId: " $parent.ProcessId
Write-Host "Parent CommandLine: " $parent.CommandLine

$gp = Get-CimInstance Win32_Process -Filter "ProcessId = $($parent.ParentProcessId)"
Write-Host "GP ProcessId: " $gp.ProcessId
Write-Host "GP CommandLine: " $gp.CommandLine

$ggp = Get-CimInstance Win32_Process -Filter "ProcessId = $($gp.ParentProcessId)"
if ($ggp) {
    Write-Host "GGP ProcessId: " $ggp.ProcessId
    Write-Host "GGP CommandLine: " $ggp.CommandLine
}
