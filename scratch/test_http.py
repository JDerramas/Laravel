import urllib.request

try:
    req = urllib.request.Request('http://localhost/llama-b10483-bin-win-cpu-x64/LocalAI/app/campus_map.php')
    with urllib.request.urlopen(req, timeout=5) as r:
        print('HTTP Status:', r.status)
        content = r.read().decode('utf-8', errors='ignore')
        print('Has search input:', 'map-search-input' in content)
        print('Has 1F button:', 'data-floor="1F"' in content)
        print('Has 2F button:', 'data-floor="2F"' in content)
        print('Has 3D mode button:', 'id="btn-mode-3d"' in content)
        print('Has CAD measure tool:', 'id="btn-cad-measure"' in content)
        print('Has Explore bar:', 'id="quick-room-bar"' in content)
        print('Has X/Y coordinates in footer:', 'id="footer-cad-coords"' in content)
        print('Has 2D full-height viewport:', 'bottom-0' in content)
except Exception as e:
    print('HTTP Request info:', e)
