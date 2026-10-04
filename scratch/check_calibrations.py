import json
from PIL import Image

with open('assets/js/npc-floorplans-data.js', 'r', encoding='utf-8') as f:
    content = f.read()

specs_str = content.split('window.NPC_BUILDING_SPECS = ')[1].split(';\n\nwindow.NPC_FLOORS_DATA = ')[0]
floors_str = content.split('window.NPC_FLOORS_DATA = ')[1].split(';\n\nconsole.log')[0]

specs = json.loads(specs_str)
floors = json.loads(floors_str)

# In NpcCadEngine:
# 1 meter = 20 SVG units
# building width = 56m -> svg width = 1120
# building depth = 60.5m -> svg height = 1210
#
# Raster image calibration:
# imgW = 2977 * (svgScale / cal.scale_x)
# imgH = 2105 * (svgScale / cal.scale_y)
# imgX = -cal.ax * (svgScale / cal.scale_x)
# y9 = cal.y1 - (buildingSpecs.depth * cal.scale_y)
# imgY = -y9 * (svgScale / cal.scale_y)

for f_key in ['2F', '3F', '4F', 'RD']:
    cal = floors[f_key]['calibration']
    scale_x = cal['scale_x']
    scale_y = cal['scale_y']
    ax = cal['ax']
    y1 = cal['y1']
    y9 = y1 - (specs['depth'] * scale_y)
    
    # Raster image pixel of Grid A, Grid J, Grid 9, Grid 1:
    jx = ax + specs['width'] * scale_x
    print(f"\n=== Floor {f_key} Calibration ===")
    print(f"Calibration ax={ax}, y1={y1}, scale_x={scale_x}, scale_y={scale_y}")
    print(f"Raster pixel of Grid A (x=0m): {ax:.1f}")
    print(f"Raster pixel of Grid J (x=56m): {jx:.1f}")
    print(f"Raster pixel of Grid 9 (y=60.5m, North): {y9:.1f}")
    print(f"Raster pixel of Grid 1 (y=0m, South): {y1:.1f}")
    
    # In SVG coordinates:
    # Grid A is at SVG x = 0
    # Grid J is at SVG x = 56 * 20 = 1120
    # Grid 9 is at SVG y = 0
    # Grid 1 is at SVG y = 60.5 * 20 = 1210
    # When overlay image is placed at imgX, imgY with imgW, imgH:
    # Point (ax, y9) on raster lands on SVG:
    # svg_ax = imgX + ax * (20 / scale_x) = -ax*(20/scale_x) + ax*(20/scale_x) = 0!
    # svg_y9 = imgY + y9 * (20 / scale_y) = -y9*(20/scale_y) + y9*(20/scale_y) = 0!
    # So Grid A-9 is placed at SVG (0, 0)!
