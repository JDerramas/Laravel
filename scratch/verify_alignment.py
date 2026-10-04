import json
import re

def test_alignment():
    print("=== NPC CAD ARCHITECTURAL ALIGNMENT TEST ===")
    
    # Load JS data file
    with open("assets/js/npc-floorplans-data.js", "r", encoding="utf-8") as f:
        content = f.read()
        
    m = re.search(r"window\.NPC_FLOORS_DATA\s*=\s*(\{.*?\});\s*console\.log", content, re.DOTALL)
    assert m, "Could not extract NPC_FLOORS_DATA from JS"
    floors = json.loads(m.group(1))
    
    # 1. Verify 1F Ground Floor presence with Canteen
    print("[TEST 1] Ground floor present with Campus Canteen...")
    assert "1F" in floors, "1F should be in floors_data"
    r_map_1f = {r["id"]: r for r in floors["1F"]["rooms"]}
    assert "1F-13" in r_map_1f, "1F-13 (Canteen) not found on 1F"
    assert "canteen" in r_map_1f["1F-13"]["name"].lower(), "1F-13 is not named Canteen"
    print(" -> PASS: 1F Ground Floor active with 1F-13 Canteen.")
    
    # 2. Verify 2F & 3F Mid Offices are level and flush (d=6.0, y=42.5 -> north wall y=48.5)
    print("[TEST 2] 2F & 3F Mid Offices levelness and flush north wall (y=48.5)...")
    r_map_2f = {r["id"]: r for r in floors["2F"]["rooms"]}
    r_map_3f = {r["id"]: r for r in floors["3F"]["rooms"]}

    for f_label, r_map, off_ids in [
        ("2F", r_map_2f, ["2F-12", "2F-14", "2F-16"]),
        ("3F", r_map_3f, ["3F-11", "3F-14", "3F-15"])
    ]:
        for rid in off_ids:
            r = r_map[rid]
            assert r["y"] == 42.5, f"{rid} y not 42.5: {r}"
            assert r["d"] == 6.0, f"{rid} d not 6.0: {r}"
            assert abs((r["y"] + r["d"]) - 48.5) < 0.001, f"{rid} north wall not at 48.5: {r}"
        print(f" -> PASS: {f_label} offices {off_ids} share identical depth 6.0m and flush north wall at y=48.5.")

    # 3. Verify 2F-17 & 3F-16 (Computer Lab 4 / Science Lab 3) do not overshoot corridor (w=8.0)
    print("[TEST 3] 2F-17 & 3F-16 East wall alignment (w=8.0, leaves 2.5m corridor)...")
    lab4 = r_map_2f["2F-17"]
    scilab3 = r_map_3f["3F-16"]
    for lab in [lab4, scilab3]:
        assert lab["x"] == 38.001, f"{lab['id']} x not 38.001: {lab}"
        assert lab["w"] == 8.0, f"{lab['id']} w not 8.0: {lab}"
        assert abs((lab["x"] + lab["w"]) - 46.001) < 0.001, f"{lab['id']} east wall not at 46.001: {lab}"
    print(" -> PASS: Lab east walls end precisely at x=46.001, leaving 2.5m open corridor.")

    # 4. Verify 2F-19, 2F-FE03, 3F-17, 3F-FE03 full width (w=7.5) and proper depth
    print("[TEST 4] NE Wing Storage & Fire Escape Stairs alignment...")
    sto2 = r_map_2f["2F-19-STO"]
    fe2 = r_map_2f["2F-FE03"]
    sto3 = r_map_3f["3F-17"]
    fe3 = r_map_3f["3F-FE03"]
    for sto in [sto2, sto3]:
        assert sto["x"] == 48.501, f"{sto['id']} x not 48.501: {sto}"
        assert sto["w"] == 7.500, f"{sto['id']} w not 7.500: {sto}"
        assert sto["y"] == 51.500, f"{sto['id']} y not 51.500: {sto}"
        assert sto["d"] == 5.800, f"{sto['id']} d not 5.800: {sto}"
    for fe in [fe2, fe3]:
        assert fe["x"] == 48.501, f"{fe['id']} x not 48.501: {fe}"
        assert fe["w"] == 7.500, f"{fe['id']} w not 7.500: {fe}"
        assert fe["y"] == 57.300, f"{fe['id']} y not 57.300: {fe}"
        assert fe["d"] == 3.200, f"{fe['id']} d not 3.200: {fe}"
    print(" -> PASS: Storage and Fire Escape stairs span full 7.5m width flush with corridor and exterior wall.")

    # 5. Verify campus_map.php UI components (Clean Student Interface: 2F, 3F, 4F, RD + Search Bar)
    print("[TEST 5] campus_map.php UI verification (Clean student 2D interface & search bar)...")
    with open("campus_map.php", "r", encoding="utf-8") as f:
        php_html = f.read()
    assert 'data-floor="1F"' not in php_html, "1F button should be removed in campus_map.php per user request"
    assert 'data-floor="2F"' in php_html, "2F button should be present"
    assert 'id="map-search-input"' in php_html, "Search bar must be present"
    assert 'bottom-0' in php_html, "cad-viewport-container should extend to bottom-0"
    assert 'id="quick-room-bar"' not in php_html, "Quick-room explore bar should be removed"
    print(" -> PASS: 1F Ground button removed, 2D full-height viewport active, and search bar ready.")

    # 6. Verify 4F Mid-East Facilities (4F-11, 4F-12, 4F-12A) are on the East side (Grid G-H)
    print("[TEST 6] 4F-11, 4F-12, 4F-12A East of Atrium alignment (x=38.001, w=5.500)...")
    r_map_4f = {r["id"]: r for r in floors["4F"]["rooms"]}
    r11 = r_map_4f["4F-11"]
    r12 = r_map_4f["4F-12"]
    r12a = r_map_4f["4F-12A"]

    for r in [r11, r12, r12a]:
        assert r["x"] == 38.001, f"{r['id']} x is {r['x']}, expected 38.001"
        assert r["w"] == 5.500, f"{r['id']} w is {r['w']}, expected 5.500"
        assert abs((r["x"] + r["w"]) - 43.501) < 0.001, f"{r['id']} does not end at Grid H (43.501)"

    assert r11["y"] == 28.500 and r11["d"] == 5.000, f"4F-11 bounds incorrect: {r11}"
    assert r12["y"] == 18.300 and r12["d"] == 10.200, f"4F-12 bounds incorrect: {r12}"
    assert r12a["y"] == 15.100 and r12a["d"] == 3.200, f"4F-12A bounds incorrect: {r12a}"
    print(" -> PASS: 4F-11, 4F-12, 4F-12A positioned correctly on the East wing.")

    # 7. Verify 4F South Wing (4F-T01, 4F-01B, 4F-01A, 4F-01) & 4F-TERR Terrace with Glass Wall
    print("[TEST 7] 4F South Wing architectural alignment & 4F-TERR Terrace with Glass Wall...")
    assert "4F-TERR" in r_map_4f, "4F-TERR should be present in 4F rooms"
    terr = r_map_4f["4F-TERR"]
    assert terr["polygon"] == [[24.667, 0.0], [56.001, 0.0], [56.001, 14.5], [50.501, 14.5], [50.501, 5.8], [38.001, 5.8]], f"4F-TERR polygon incorrect: {terr['polygon']}"
    assert "terrace" in terr["name"].lower(), f"4F-TERR name should contain terrace: {terr['name']}"
    
    t01 = r_map_4f["4F-T01"]
    r01b = r_map_4f["4F-01B"]
    r01a = r_map_4f["4F-01A"]
    r01 = r_map_4f["4F-01"]

    assert t01["y"] == 0.0 and t01["d"] == 4.8, f"4F-T01 bounds incorrect: {t01}"
    assert r01b["x"] == 7.677 and r01b["y"] == 0.0 and r01b["w"] == 2.123 and r01b["d"] == 2.7, f"4F-01B incorrect: {r01b}"
    assert "polygon" in r01a, "4F-01A should have polygon"
    assert "polygon" in r01, "4F-01 should have polygon"
    assert r01["polygon"] == [[12.5, 0.0], [24.667, 0.0], [38.001, 5.8], [38.001, 8.3], [12.5, 8.3]], f"4F-01 polygon incorrect: {r01['polygon']}"

    # Verify glass wall style and CAD elements
    with open("campus_map.php", "r", encoding="utf-8") as f:
        php_content = f.read()
    assert ".cad-glass-wall" in php_content, ".cad-glass-wall missing in campus_map.php"

    with open("assets/js/npc-campus-cad.js", "r", encoding="utf-8") as f:
        cad_content = f.read()
    assert "cad-glass-wall" in cad_content, "cad-glass-wall missing in npc-campus-cad.js"
    assert "cad-balustrade" in cad_content, "cad-balustrade missing in npc-campus-cad.js"
    print(" -> PASS: 4F South Wing, Executive Terrace (4F-TERR), and Glass Curtain Wall verified 100%.")

    print("\nALL ARCHITECTURAL VERIFICATION TESTS PASSED SUCCESSFULLY! [OK]")

if __name__ == "__main__":
    test_alignment()
