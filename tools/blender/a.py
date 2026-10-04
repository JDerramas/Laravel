import bpy
import bmesh
import math
import os

def reset_scene():
    """Linisin ang scene bago mag-generate"""
    bpy.ops.object.select_all(action='SELECT')
    bpy.ops.object.delete(use_global=False)
    for block in list(bpy.data.meshes):
        bpy.data.meshes.remove(block)
    for block in list(bpy.data.materials):
        bpy.data.materials.remove(block)

def create_material(name, diffuse_color, roughness=0.4, metallic=0.0, alpha=1.0, transmission=0.0):
    """Gumawa ng PBR material na compatible sa Blender 5.2 Principled BSDF"""
    mat = bpy.data.materials.get(name)
    if not mat:
        mat = bpy.data.materials.new(name=name)
        mat.use_nodes = True
        bsdf = mat.node_tree.nodes.get("Principled BSDF")
        if bsdf:
            if 'Base Color' in bsdf.inputs:
                bsdf.inputs['Base Color'].default_value = diffuse_color
            if 'Roughness' in bsdf.inputs:
                bsdf.inputs['Roughness'].default_value = roughness
            if 'Metallic' in bsdf.inputs:
                bsdf.inputs['Metallic'].default_value = metallic
            if 'Alpha' in bsdf.inputs:
                bsdf.inputs['Alpha'].default_value = alpha
            if 'Transmission Weight' in bsdf.inputs:
                bsdf.inputs['Transmission Weight'].default_value = transmission
            elif 'Transmission' in bsdf.inputs:
                bsdf.inputs['Transmission'].default_value = transmission
        if alpha < 1.0:
            mat.blend_method = 'BLEND'
    return mat

def build_floor_plate(name, width, depth, z_pos, thickness, mat, void_w=0.0, void_d=0.0):
    """Floor slab na may opsyonal na central open atrium void ("OPEN ABOVE")"""
    bm = bmesh.new()
    hw, hd = width / 2.0, depth / 2.0
    
    if void_w > 1.0 and void_d > 1.0:
        hvw, hvd = void_w / 2.0, void_d / 2.0
        v_out = [
            bm.verts.new((-hw, -hd, 0)),
            bm.verts.new(( hw, -hd, 0)),
            bm.verts.new(( hw,  hd, 0)),
            bm.verts.new((-hw,  hd, 0))
        ]
        v_in = [
            bm.verts.new((-hvw, -hvd, 0)),
            bm.verts.new(( hvw, -hvd, 0)),
            bm.verts.new(( hvw,  hvd, 0)),
            bm.verts.new((-hvw,  hvd, 0))
        ]
        bm.faces.new([v_out[0], v_out[1], v_in[1], v_in[0]])
        bm.faces.new([v_out[1], v_out[2], v_in[2], v_in[1]])
        bm.faces.new([v_out[2], v_out[3], v_in[3], v_in[2]])
        bm.faces.new([v_out[3], v_out[0], v_in[0], v_in[3]])
    else:
        # Solid floor slab (walang butas sa gitna - perpekto para sa 4F Gymnasium)
        v = [
            bm.verts.new((-hw, -hd, 0)),
            bm.verts.new(( hw, -hd, 0)),
            bm.verts.new(( hw,  hd, 0)),
            bm.verts.new((-hw,  hd, 0))
        ]
        bm.faces.new(v)
    
    geom = bm.faces[:] + bm.edges[:] + bm.verts[:]
    res = bmesh.ops.extrude_face_region(bm, geom=geom)
    extruded_verts = [e for e in res['geom'] if isinstance(e, bmesh.types.BMVert)]
    bmesh.ops.translate(bm, vec=(0, 0, -thickness), verts=extruded_verts)
    
    mesh = bpy.data.meshes.new(name)
    bm.to_mesh(mesh)
    bm.free()
    
    obj = bpy.data.objects.new(name, mesh)
    bpy.context.collection.objects.link(obj)
    obj.location = (0, 0, z_pos)
    if mat:
        obj.data.materials.append(mat)
    return obj

# ══════════════════ ARCHITECTURAL DETAIL BUILDERS ══════════════════

def add_brown_door(room_id, x, y, z, width=1.05, height=2.15, wall_dir='x', materials=None):
    """BROWN WOODEN DOOR ("brown yung pinto") na may casing at chrome handle"""
    door_th = 0.08
    
    # 1. Door Leaf (Warm Rich Brown Timber)
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(x, y, z + height / 2.0))
    door = bpy.context.active_object
    door.name = f"DOOR_{room_id}"
    if wall_dir == 'x':
        door.scale = (width - 0.06, door_th, height - 0.04)
    else:
        door.scale = (door_th, width - 0.06, height - 0.04)
    door.data.materials.append(materials['door_brown'])
    door["room_id"] = room_id

    # 2. Door Casing / Architrave Frame (Dark Espresso Timber)
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(x, y, z + height / 2.0))
    frame = bpy.context.active_object
    frame.name = f"DoorFrame_{room_id}"
    if wall_dir == 'x':
        frame.scale = (width + 0.10, door_th + 0.06, height + 0.06)
    else:
        frame.scale = (door_th + 0.06, width + 0.10, height + 0.06)
    frame.data.materials.append(materials['door_frame'])

    # 3. Chrome Lever Handle
    hx = x + (width * 0.35 if wall_dir == 'x' else 0)
    hy = y + (width * 0.35 if wall_dir == 'y' else 0.06)
    hz = z + 1.0
    bpy.ops.mesh.primitive_cylinder_add(radius=0.025, depth=0.14, location=(hx, hy, hz))
    handle = bpy.context.active_object
    handle.rotation_euler = (math.radians(90), 0, 0)
    handle.name = f"DoorHandle_{room_id}"
    handle.data.materials.append(materials['chrome'])

def add_glassboard(room_id, x, y, z, width, height, materials):
    """Frosted tempered glass whiteboard na may chrome standoffs at marker tray"""
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(x, y, z))
    gb = bpy.context.active_object
    gb.name = f"Glassboard_{room_id}"
    gb.scale = (width, 0.04, height)
    gb.data.materials.append(materials['glassboard'])
    gb["room_id"] = room_id

    # Chrome Standoffs
    hw = width / 2.0 - 0.15
    hh = height / 2.0 - 0.12
    for i, (sx, sz) in enumerate([(-hw, -hh), (hw, -hh), (-hw, hh), (hw, hh)]):
        bpy.ops.mesh.primitive_cylinder_add(radius=0.035, depth=0.08, location=(x + sx, y - 0.03, z + sz))
        bolt = bpy.context.active_object
        bolt.rotation_euler = (math.radians(90), 0, 0)
        bolt.name = f"Standoff_{room_id}_{i}"
        bolt.data.materials.append(materials['chrome'])

    # Marker Tray
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(x, y - 0.05, z - height/2.0 - 0.04))
    tray = bpy.context.active_object
    tray.name = f"MarkerTray_{room_id}"
    tray.scale = (width * 0.7, 0.08, 0.02)
    tray.data.materials.append(materials['chrome'])

def add_projector_and_screen(room_id, x, y, z_ceiling, screen_w, screen_h, materials):
    """HD digital ceiling projector na may retractable pull-down screen"""
    bpy.ops.mesh.primitive_cylinder_add(radius=0.08, depth=screen_w + 0.2, location=(x, y - 0.08, z_ceiling - 0.1))
    casing = bpy.context.active_object
    casing.rotation_euler = (0, math.radians(90), 0)
    casing.name = f"Screen_Casing_{room_id}"
    casing.data.materials.append(materials['wall_warm'])

    screen_y = y - 0.08
    screen_center_z = z_ceiling - 0.1 - (screen_h / 2.0)
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(x, screen_y, screen_center_z))
    screen = bpy.context.active_object
    screen.name = f"Projector_Screen_{room_id}"
    screen.scale = (screen_w, 0.015, screen_h)
    screen.data.materials.append(materials['proj_screen'])

    bpy.ops.mesh.primitive_cylinder_add(radius=0.025, depth=screen_w + 0.1, location=(x, screen_y, z_ceiling - 0.1 - screen_h))
    bar = bpy.context.active_object
    bar.rotation_euler = (0, math.radians(90), 0)
    bar.name = f"Pull_Bar_{room_id}"
    bar.data.materials.append(materials['chrome'])

    proj_y = y + 3.8
    proj_z = z_ceiling - 0.45
    bpy.ops.mesh.primitive_cylinder_add(radius=0.03, depth=0.45, location=(x, proj_y, z_ceiling - 0.225))
    pole = bpy.context.active_object
    pole.name = f"Proj_Pole_{room_id}"
    pole.data.materials.append(materials['chrome'])

    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(x, proj_y, proj_z))
    proj = bpy.context.active_object
    proj.name = f"Projector_{room_id}"
    proj.scale = (0.45, 0.35, 0.15)
    proj.data.materials.append(materials['wall_warm'])

def add_classroom_desks(room_id, cx, cy, cz, rw, rd, materials):
    """Maayos na hanay ng student desks (4 cols x 3 rows)"""
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx + rw * 0.25, cy - rd * 0.28, cz + 0.55))
    podium = bpy.context.active_object
    podium.name = f"Podium_{room_id}"
    podium.scale = (1.0, 0.65, 1.1)
    podium.data.materials.append(materials['desk_birch'])

    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx + rw * 0.25, cy - rd * 0.28, cz + 1.13))
    laptop = bpy.context.active_object
    laptop.name = f"Laptop_{room_id}"
    laptop.scale = (0.38, 0.26, 0.02)
    laptop.data.materials.append(materials['screen_black'])

    cols, rows = 4, 3
    start_x = cx - rw * 0.30
    step_x = (rw * 0.60) / (cols - 1 if cols > 1 else 1)
    start_y = cy - rd * 0.05
    step_y = (rd * 0.55) / (rows - 1 if rows > 1 else 1)

    for r in range(rows):
        for c in range(cols):
            dx = start_x + c * step_x
            dy = start_y + r * step_y
            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(dx, dy, cz + 0.40))
            desk = bpy.context.active_object
            desk.name = f"Desk_{room_id}_{r}_{c}"
            desk.scale = (1.25, 0.58, 0.75)
            desk.data.materials.append(materials['desk_birch'])

            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(dx, dy + 0.48, cz + 0.25))
            chair = bpy.context.active_object
            chair.name = f"Chair_{room_id}_{r}_{c}"
            chair.scale = (0.46, 0.46, 0.50)
            chair.data.materials.append(materials['screen_black'])

def add_complab_desks(room_id, cx, cy, cz, rw, rd, materials):
    """Computer Lab work benches na may monitors at dark countertops"""
    rows, cols = 2, 4
    start_y = cy - rd * 0.22
    step_y = rd * 0.44
    start_x = cx - rw * 0.32
    step_x = (rw * 0.64) / (cols - 1 if cols > 1 else 1)

    for r in range(rows):
        by = start_y + r * step_y
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, by, cz + 0.38))
        bench = bpy.context.active_object
        bench.name = f"LabBench_{room_id}_{r}"
        bench.scale = (rw * 0.82, 0.95, 0.72)
        bench.data.materials.append(materials['lab_bench'])

        for c in range(cols):
            mx = start_x + c * step_x
            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(mx, by, cz + 0.95))
            mon = bpy.context.active_object
            mon.name = f"Monitor_{room_id}_{r}_{c}"
            mon.scale = (0.52, 0.04, 0.34)
            mon.data.materials.append(materials['screen_black'])

def add_restroom_cubicles(room_id, cx, cy, cz, rw, rd, materials):
    """Restroom cubicles na may glazed teal tiles at slate blue-gray floor"""
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, cy, cz + 0.05))
    cr_flr = bpy.context.active_object
    cr_flr.name = f"CR_Floor_{room_id}"
    cr_flr.scale = (rw - 0.2, rd - 0.2, 0.08)
    cr_flr.data.materials.append(materials['restroom_floor'])

    stalls = 4
    sw = (rw * 0.8) / stalls
    start_x = cx - (rw * 0.4) + sw / 2.0
    for i in range(stalls):
        sx = start_x + i * sw
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(sx, cy, cz + 1.1))
        part = bpy.context.active_object
        part.name = f"Toilet_Partition_{room_id}_{i}"
        part.scale = (0.05, rd * 0.7, 2.0)
        part.data.materials.append(materials['wall_teal'])

        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(sx + sw * 0.3, cy - rd * 0.15, cz + 0.25))
        toilet = bpy.context.active_object
        toilet.name = f"Toilet_{room_id}_{i}"
        toilet.scale = (0.4, 0.55, 0.45)
        toilet.data.materials.append(materials['wall_warm'])

def add_basketball_court(room_id, cx, cy, cz, rw, rd, materials):
    """Super Malaking Full-Size Gymnasium Arena na FIT na FIT at walang overflow!"""
    # 1. Hardwood Maple Sports Arena Slab
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, cy, cz + 0.08))
    floor = bpy.context.active_object
    floor.name = f"ROOM_FLOOR_{room_id}"
    floor.scale = (rw - 0.2, rd - 0.2, 0.14)
    floor.data.materials.append(materials['gym_court'])
    floor["room_id"] = room_id
    floor["floor"] = "4F"

    # Court dimension constants (FIBA Official: 28m x 15m)
    court_w = 28.0
    court_d = 15.0
    line_z = cz + 0.16
    line_th = 0.09

    # 2. Outer Court Boundary Lines (White Box)
    for sy in [-court_d/2.0, court_d/2.0]:
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, cy + sy, line_z))
        bline = bpy.context.active_object
        bline.name = f"Court_Bound_Y_{sy}"
        bline.scale = (court_w, line_th, 0.02)
        bline.data.materials.append(materials['court_lines'])

    for sx in [-court_w/2.0, court_w/2.0]:
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx + sx, cy, line_z))
        bline = bpy.context.active_object
        bline.name = f"Court_Bound_X_{sx}"
        bline.scale = (line_th, court_d, 0.02)
        bline.data.materials.append(materials['court_lines'])

    # 3. Center Half-Court Line
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, cy, line_z))
    half_line = bpy.context.active_object
    half_line.name = f"Court_Half_Line_{room_id}"
    half_line.scale = (line_th, court_d, 0.02)
    half_line.data.materials.append(materials['court_lines'])

    # 4. Center Circle
    bpy.ops.mesh.primitive_cylinder_add(radius=1.8, depth=0.02, location=(cx, cy, line_z))
    circle = bpy.context.active_object
    circle.name = f"Court_Center_Circle_{room_id}"
    circle.data.materials.append(materials['court_lines'])

    # 5. Free Throw Key Areas (Left and Right)
    key_w = 5.8
    key_d = 4.9
    for side, kx in [(-1, cx - court_w/2.0 + key_w/2.0), (1, cx + court_w/2.0 - key_w/2.0)]:
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(kx, cy, line_z))
        key_box = bpy.context.active_object
        key_box.name = f"Court_Key_{side}"
        key_box.scale = (key_w, key_d, 0.015)
        key_box.data.materials.append(materials['court_key'])

        ft_x = cx + side * (court_w/2.0 - key_w)
        bpy.ops.mesh.primitive_cylinder_add(radius=1.8, depth=0.02, location=(ft_x, cy, line_z))
        ft_circle = bpy.context.active_object
        ft_circle.name = f"Court_FT_Circle_{side}"
        ft_circle.data.materials.append(materials['court_lines'])

    # 6. Two Heavy-Duty Basketball Stanchion Hoops (Left and Right)
    for side, hx in [(-1, cx - court_w/2.0 - 0.5), (1, cx + court_w/2.0 + 0.5)]:
        # Blue Stanchion Base & Angled Post
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(hx, cy, cz + 0.6))
        base = bpy.context.active_object
        base.name = f"Hoop_Base_{room_id}_{side}"
        base.scale = (0.9, 1.4, 1.0)
        base.data.materials.append(materials['hoop_blue'])

        bpy.ops.mesh.primitive_cylinder_add(radius=0.13, depth=4.0, location=(hx - side * 0.4, cy, cz + 2.5))
        post = bpy.context.active_object
        post.rotation_euler = (0, math.radians(-15 * side), 0)
        post.name = f"Hoop_Post_{room_id}_{side}"
        post.data.materials.append(materials['hoop_blue'])

        # Transparent Backboard
        bb_x = hx - side * 0.9
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(bb_x, cy, cz + 3.3))
        bb = bpy.context.active_object
        bb.name = f"Backboard_{room_id}_{side}"
        bb.scale = (0.08, 1.85, 1.1)
        bb.data.materials.append(materials['curtain_glass'])

        # Orange-Red Rim
        rim_x = bb_x - side * 0.38
        bpy.ops.mesh.primitive_cylinder_add(radius=0.25, depth=0.04, location=(rim_x, cy, cz + 3.05))
        rim = bpy.context.active_object
        rim.name = f"Rim_{room_id}_{side}"
        rim.data.materials.append(materials['chiller_red'])

    # 7. 5-Tier Spectator Bleachers along North Side of Arena
    bleacher_x = cx
    bleacher_y = cy + court_d/2.0 + 2.2
    bleacher_w = 30.0
    for tier in range(5):
        by = bleacher_y + tier * 0.85
        bz = cz + 0.25 + tier * 0.40
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(bleacher_x, by, bz))
        bench = bpy.context.active_object
        bench.name = f"Bleacher_Tier_{tier}"
        bench.scale = (bleacher_w, 0.75, 0.35)
        bench.data.materials.append(materials['desk_birch'])

    # 8. Player Benches and Officials Table along South Side
    south_bench_y = cy - court_d/2.0 - 2.2
    for b_side, bx in [(-1, cx - 7.0), (1, cx + 7.0)]:
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(bx, south_bench_y, cz + 0.3))
        pbench = bpy.context.active_object
        pbench.name = f"Player_Bench_{b_side}"
        pbench.scale = (8.0, 0.7, 0.4)
        pbench.data.materials.append(materials['hoop_blue'])

    # Scorer / Officials Table Center South
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, south_bench_y, cz + 0.45))
    stable = bpy.context.active_object
    stable.name = f"Scorer_Table_{room_id}"
    stable.scale = (4.0, 0.8, 0.75)
    stable.data.materials.append(materials['wall_accent_navy'])

    # 9. Perimeter Arena Wainscot Walls (Navy and Warm Oak)
    hw, hd = rw / 2.0, rd / 2.0
    arena_walls = [
        ('N', cx, cy + hd - 0.1, rw, 0.25),
        ('S', cx, cy - hd + 0.1, rw, 0.25),
        ('W', cx - hw + 0.1, cy, 0.25, rd),
        ('E', cx + hw - 0.1, cy, 0.25, rd)
    ]
    for w_dir, wx, wy, ww, wd in arena_walls:
        # Lower Wainscoting (Warm Oak Sports Panel)
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(wx, wy, cz + 0.75))
        w_low = bpy.context.active_object
        w_low.name = f"GYM_WALL_OAK_{w_dir}"
        w_low.scale = (ww, wd, 1.5)
        w_low.data.materials.append(materials['wall_gym_wood'])
        w_low["room_id"] = room_id
        w_low["floor"] = "4F"

        # Upper Wall (Deep NPC Athletic Navy)
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(wx, wy, cz + 2.15))
        w_up = bpy.context.active_object
        w_up.name = f"GYM_WALL_NAVY_{w_dir}"
        w_up.scale = (ww, wd, 1.3)
        w_up.data.materials.append(materials['wall_accent_navy'])
        w_up["room_id"] = room_id
        w_up["floor"] = "4F"

        # Gold Trim Band
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(wx, wy, cz + 1.5))
        trim = bpy.context.active_object
        trim.name = f"GYM_WALL_TRIM_{w_dir}"
        trim.scale = (ww + 0.02, wd + 0.02, 0.08)
        trim.data.materials.append(materials['wall_accent_gold'])

    # Double Wooden Entrance Doors on South Arena Wall
    add_brown_door(room_id, cx - 1.2, cy - hd + 0.1, cz, width=1.1, height=2.3, wall_dir='x', materials=materials)
    add_brown_door(room_id, cx + 1.2, cy - hd + 0.1, cz, width=1.1, height=2.3, wall_dir='x', materials=materials)

def add_ac_condenser_units(cx, cy, cz, count, materials):
    """Red Outdoor Rooftop AC Condenser / Chiller Units (Picture 0 & 1)"""
    spacing_y = 2.6
    start_y = cy - ((count - 1) * spacing_y) / 2.0
    for i in range(count):
        uy = start_y + i * spacing_y
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, uy, cz + 0.9))
        chiller = bpy.context.active_object
        chiller.name = f"AC_Condenser_{cx}_{i}"
        chiller.scale = (1.5, 1.2, 1.8)
        chiller.data.materials.append(materials['chiller_red'])

        for f_off in [-0.38, 0.38]:
            bpy.ops.mesh.primitive_cylinder_add(radius=0.34, depth=0.06, location=(cx + 0.76, uy + f_off, cz + 1.25))
            fan = bpy.context.active_object
            fan.rotation_euler = (0, math.radians(90), 0)
            fan.name = f"AC_Fan_{cx}_{i}_{f_off}"
            fan.data.materials.append(materials['chiller_fan'])

# ══════════════════ MASTER ARCHITECTURAL BUILDER ══════════════════

def create_npc_model():
    reset_scene()

    # 1. Vibrant PBR Architectural Materials Palette ("lagyan mo kulay yung mga pader")
    mats = {
        # Wall Materials: Colorful & Distinctive
        'wall_warm':          create_material("NPC_Wall_Warm_Cream",     (0.93, 0.90, 0.85, 1.0), roughness=0.35),
        'wall_accent_navy':   create_material("NPC_Wall_Accent_Navy",    (0.03, 0.12, 0.28, 1.0), roughness=0.28),
        'wall_accent_gold':   create_material("NPC_Wall_Accent_Gold",    (0.92, 0.72, 0.12, 1.0), roughness=0.25, metallic=0.25),
        'wall_tech_slate':    create_material("NPC_Wall_Tech_Slate",     (0.12, 0.16, 0.22, 1.0), roughness=0.30),
        'wall_tech_blue':     create_material("NPC_Wall_Tech_Blue",      (0.04, 0.40, 0.70, 1.0), roughness=0.26),
        'wall_teal':          create_material("NPC_Wall_Restroom_Teal",  (0.06, 0.54, 0.50, 1.0), roughness=0.25),
        'wall_gym_wood':      create_material("NPC_Wall_Gym_Oak",        (0.62, 0.36, 0.12, 1.0), roughness=0.28),
        'wall_col_navy':      create_material("NPC_Column_Navy",         (0.01, 0.07, 0.18, 1.0), roughness=0.25),
        'wall_cap_dark':      create_material("NPC_Wall_Cap_Dark",       (0.08, 0.09, 0.11, 1.0), roughness=0.22),
        'wall_corridor':      create_material("NPC_Wall_Corridor_Pearl", (0.88, 0.89, 0.91, 1.0), roughness=0.30),

        # Doors & Woodwork
        'door_brown':         create_material("NPC_Door_Timber_Brown",   (0.33, 0.17, 0.07, 1.0), roughness=0.35),
        'door_frame':         create_material("NPC_Door_Frame_Charcoal", (0.14, 0.08, 0.04, 1.0), roughness=0.35),
        'desk_birch':         create_material("NPC_Desk_Birch_Light",    (0.76, 0.54, 0.30, 1.0), roughness=0.35),
        'lab_bench':          create_material("NPC_Lab_Bench_Dark",      (0.12, 0.14, 0.18, 1.0), roughness=0.30),

        # Floors
        'floor_tile':         create_material("NPC_Floor_Tile_Light",    (0.91, 0.89, 0.85, 1.0), roughness=0.25),
        'restroom_floor':     create_material("NPC_Restroom_Floor",      (0.38, 0.46, 0.54, 1.0), roughness=0.32),
        'gym_court':          create_material("NPC_Gym_Hardwood_Maple",  (0.86, 0.56, 0.22, 1.0), roughness=0.20),
        'court_key':          create_material("NPC_Court_Key_Wood",      (0.72, 0.42, 0.14, 1.0), roughness=0.22),
        'court_lines':        create_material("NPC_Court_Lines_White",   (0.99, 0.99, 0.99, 1.0), roughness=0.15),

        # Mechanical & Equipment
        'chiller_red':        create_material("NPC_AC_Chiller_Red",      (0.85, 0.10, 0.10, 1.0), roughness=0.28),
        'chiller_fan':        create_material("NPC_AC_Fan_Black",        (0.06, 0.06, 0.06, 1.0), roughness=0.20),
        'hoop_blue':          create_material("NPC_Hoop_Stanchion_Blue", (0.05, 0.32, 0.78, 1.0), roughness=0.25),
        'glassboard':         create_material("NPC_Frosted_Glassboard",  (0.90, 0.95, 0.98, 0.85), roughness=0.08, alpha=0.85),
        'proj_screen':        create_material("NPC_Projector_Screen",    (0.99, 0.99, 0.99, 1.0), roughness=0.80),
        'chrome':             create_material("NPC_Chrome_Metal",        (0.92, 0.94, 0.96, 1.0), roughness=0.10, metallic=0.95),
        'screen_black':       create_material("NPC_Display_Black",       (0.06, 0.08, 0.12, 1.0), roughness=0.10),
        'louvers_timber':     create_material("NPC_Facade_Louvers",      (0.76, 0.56, 0.36, 1.0), roughness=0.45),
        'curtain_glass':      create_material("NPC_Curtain_Glass",       (0.15, 0.25, 0.35, 0.45), roughness=0.08, alpha=0.45),
        'npc_gold':           create_material("NPC_Seal_Gold",           (0.95, 0.78, 0.15, 1.0), roughness=0.25, metallic=0.85)
    }

    # 2. Super Expanded Dimensions & Elevations ("SUBRA LAKI PARA FIT LAHAT")
    width = 76.0
    depth = 76.0
    void_w = 18.0  # Open Central Courtyard / Lightwell
    void_d = 18.0

    z_1st  = 0.0
    z_2nd  = 4.20
    z_3rd  = 7.90
    z_4th  = 11.60
    z_roof = 15.30
    slab_th = 0.28
    flr_h   = 2.75  # Architectural dollhouse cutaway height

    # 3. Floor Slabs: 1F, 2F, 3F have central lightwell; 4F is SOLID for the Gymnasium Arena!
    build_floor_plate("Floor_1F_Slab", width, depth, z_1st,  slab_th, mats['floor_tile'], void_w, void_d)
    build_floor_plate("Floor_2F_Slab", width, depth, z_2nd,  slab_th, mats['floor_tile'], void_w, void_d)
    build_floor_plate("Floor_3F_Slab", width, depth, z_3rd,  slab_th, mats['floor_tile'], void_w, void_d)
    build_floor_plate("Floor_4F_Slab", width, depth, z_4th,  slab_th, mats['floor_tile'], 0.0,    0.0) # SOLID!
    build_floor_plate("Floor_RD_Slab", width, depth, z_roof, slab_th, mats['floor_tile'], void_w, void_d)

    # 4. Atrium Safety Glass Balustrade on 2F and 3F
    for z in [z_2nd, z_3rd, z_roof]:
        rail_th = 0.08
        rail_h = 1.05
        for side, rx, ry, rw, rd in [
            ('N', 0, void_d/2.0, void_w, rail_th),
            ('S', 0, -void_d/2.0, void_w, rail_th),
            ('W', -void_w/2.0, 0, rail_th, void_d),
            ('E', void_w/2.0, 0, rail_th, void_d)
        ]:
            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(rx, ry, z + rail_h/2.0))
            rail = bpy.context.active_object
            rail.name = f"Atrium_Rail_{side}_{z}"
            rail.scale = (rw, rd, rail_h)
            rail.data.materials.append(mats['curtain_glass'])

    # 5. Reinforced Concrete Columns Grid in Institutional Navy (10x10 Grid: A to J, 1 to 10)
    grid_x = [-36.0, -28.0, -20.0, -12.0, -4.0, 4.0, 12.0, 20.0, 28.0, 36.0]
    grid_y = [-36.0, -28.0, -20.0, -12.0, -4.0, 4.0, 12.0, 20.0, 28.0, 36.0]
    for gx in grid_x:
        for gy in grid_y:
            # Skip columns inside central lightwell on lower floors
            if abs(gx) < void_w/2.0 - 0.5 and abs(gy) < void_d/2.0 - 0.5:
                continue
            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(gx, gy, z_roof / 2.0))
            col = bpy.context.active_object
            col.name = f"Column_{gx}_{gy}"
            col.scale = (0.85, 0.85, z_roof)
            col.data.materials.append(mats['wall_col_navy'])

    # 6. ROOM BUILDER WITH COLORFUL WALLS ("lagyan mo kulay yung mga pader")
    def build_architectural_room(room_id, name, floor, cx, cy, cz, rw, rd, room_type='classroom', door_side='inner', wall_theme='academic'):
        # Floor Tile
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(cx, cy, cz + 0.04))
        rf = bpy.context.active_object
        rf.name = f"ROOM_FLOOR_{room_id}"
        rf.scale = (rw - 0.15, rd - 0.15, 0.08)
        rf.data.materials.append(mats['floor_tile'])
        rf["room_id"] = room_id
        rf["floor"] = floor

        wall_th = 0.20
        hw, hd = rw / 2.0, rd / 2.0
        door_w = 1.05
        door_h = 2.15

        # Wall Pieces: (x, y, scale_x, scale_y, is_accent_wall)
        wall_pieces = []
        door_info = None

        if door_side == 'inner_east':
            door_y = cy - rd * 0.20
            door_info = (cx + hw, door_y, 'y')
            # South wall is FRONT accent wall
            wall_pieces.append((cx, cy - hd + wall_th/2.0, rw, wall_th, True))
            # North wall
            wall_pieces.append((cx, cy + hd - wall_th/2.0, rw, wall_th, False))
            # West wall
            wall_pieces.append((cx - hw + wall_th/2.0, cy, wall_th, rd - wall_th*2, False))
            # East wall with door
            e1_len = (cy + hd) - (door_y + door_w/2.0)
            if e1_len > 0.2:
                wall_pieces.append((cx + hw - wall_th/2.0, (door_y + door_w/2.0) + e1_len/2.0, wall_th, e1_len, False))
            e2_len = (door_y - door_w/2.0) - (cy - hd)
            if e2_len > 0.2:
                wall_pieces.append((cx + hw - wall_th/2.0, (cy - hd) + e2_len/2.0, wall_th, e2_len, False))

        elif door_side == 'inner_west':
            door_y = cy - rd * 0.20
            door_info = (cx - hw, door_y, 'y')
            wall_pieces.append((cx, cy - hd + wall_th/2.0, rw, wall_th, True)) # Front accent
            wall_pieces.append((cx, cy + hd - wall_th/2.0, rw, wall_th, False))
            wall_pieces.append((cx + hw - wall_th/2.0, cy, wall_th, rd - wall_th*2, False))
            w1_len = (cy + hd) - (door_y + door_w/2.0)
            if w1_len > 0.2:
                wall_pieces.append((cx - hw + wall_th/2.0, (door_y + door_w/2.0) + w1_len/2.0, wall_th, w1_len, False))
            w2_len = (door_y - door_w/2.0) - (cy - hd)
            if w2_len > 0.2:
                wall_pieces.append((cx - hw + wall_th/2.0, (cy - hd) + w2_len/2.0, wall_th, w2_len, False))

        elif door_side == 'inner_north':
            door_x = cx - rw * 0.25
            door_info = (door_x, cy + hd, 'x')
            wall_pieces.append((cx, cy - hd + wall_th/2.0, rw, wall_th, True)) # Front accent
            wall_pieces.append((cx - hw + wall_th/2.0, cy, wall_th, rd, False))
            wall_pieces.append((cx + hw - wall_th/2.0, cy, wall_th, rd, False))
            n1_len = (door_x - door_w/2.0) - (cx - hw)
            if n1_len > 0.2:
                wall_pieces.append(((cx - hw) + n1_len/2.0, cy + hd - wall_th/2.0, n1_len, wall_th, False))
            n2_len = (cx + hw) - (door_x + door_w/2.0)
            if n2_len > 0.2:
                wall_pieces.append(((door_x + door_w/2.0) + n2_len/2.0, cy + hd - wall_th/2.0, n2_len, wall_th, False))

        else: # inner_south
            door_x = cx - rw * 0.25
            door_info = (door_x, cy - hd, 'x')
            wall_pieces.append((cx, cy + hd - wall_th/2.0, rw, wall_th, True)) # Front accent
            wall_pieces.append((cx - hw + wall_th/2.0, cy, wall_th, rd, False))
            wall_pieces.append((cx + hw - wall_th/2.0, cy, wall_th, rd, False))
            s1_len = (door_x - door_w/2.0) - (cx - hw)
            if s1_len > 0.2:
                wall_pieces.append(((cx - hw) + s1_len/2.0, cy - hd + wall_th/2.0, s1_len, wall_th, False))
            s2_len = (cx + hw) - (door_x + door_w/2.0)
            if s2_len > 0.2:
                wall_pieces.append(((door_x + door_w/2.0) + s2_len/2.0, cy - hd + wall_th/2.0, s2_len, wall_th, False))

        # Select Architectural Wall Color Material Palette
        if wall_theme == 'academic':
            accent_mat = mats['wall_accent_navy']
            base_mat   = mats['wall_warm']
        elif wall_theme == 'complab':
            accent_mat = mats['wall_tech_blue']
            base_mat   = mats['wall_tech_slate']
        elif wall_theme == 'restroom':
            accent_mat = mats['wall_teal']
            base_mat   = mats['wall_teal']
        elif wall_theme == 'executive':
            accent_mat = mats['wall_accent_gold']
            base_mat   = mats['wall_accent_navy']
        else:
            accent_mat = mats['wall_accent_navy']
            base_mat   = mats['wall_warm']

        # Build colored wall segments
        for idx, (wx, wy, ww, wd, is_accent) in enumerate(wall_pieces):
            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(wx, wy, cz + flr_h/2.0))
            w_obj = bpy.context.active_object
            mat_to_use = accent_mat if is_accent else base_mat
            w_obj.name = f"ROOM_WALLS_{room_id}_{idx}"
            w_obj.scale = (ww, wd, flr_h)
            w_obj.data.materials.append(mat_to_use)
            w_obj["room_id"] = room_id
            w_obj["floor"] = floor

            # Dark Wall Cap for sharp AutoCAD line definition
            bpy.ops.mesh.primitive_cube_add(size=1.0, location=(wx, wy, cz + flr_h + 0.02))
            cap = bpy.context.active_object
            cap.name = f"WallCap_{room_id}_{idx}"
            cap.scale = (ww + 0.04, wd + 0.04, 0.04)
            cap.data.materials.append(mats['wall_cap_dark'])

        # Real Brown Timber Door
        if door_info:
            dx, dy, wdir = door_info
            add_brown_door(room_id, dx, dy, cz, width=1.0, height=door_h, wall_dir=wdir, materials=mats)

        # Interior Amenities
        front_y = cy - rd / 2.0 + wall_th + 0.05
        front_x = cx
        z_board = cz + 1.55

        if room_type == 'classroom':
            add_glassboard(room_id, front_x, front_y, z_board, width=rw * 0.65, height=1.25, materials=mats)
            add_projector_and_screen(room_id, front_x, front_y, cz + flr_h, screen_w=rw * 0.50, screen_h=1.65, materials=mats)
            add_classroom_desks(room_id, cx, cy, cz, rw, rd, mats)
        elif room_type == 'complab':
            add_glassboard(room_id, front_x, front_y, z_board, width=rw * 0.70, height=1.25, materials=mats)
            add_complab_desks(room_id, cx, cy, cz, rw, rd, mats)
        elif room_type == 'restroom':
            add_restroom_cubicles(room_id, cx, cy, cz, rw, rd, mats)

    # ══════════════════ 2F ACADEMIC FLOOR (SUPER LAKI: 76m x 76m) ══════════════════
    # West Wing Classrooms (201, 202, 203, 204, 205)
    build_architectural_room("2F-03", "Room 201", "2F", -26.0, -16.0, z_2nd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')
    build_architectural_room("2F-04", "Room 202", "2F", -26.0, -5.0,  z_2nd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')
    build_architectural_room("2F-05", "Room 203", "2F", -26.0, 6.0,   z_2nd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')
    build_architectural_room("2F-06", "Room 204", "2F", -26.0, 17.0,  z_2nd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')
    build_architectural_room("2F-07", "Room 205", "2F", -26.0, 28.0,  z_2nd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')

    # East Wing Classrooms & Labs (207, 208, 209, 210)
    build_architectural_room("2F-19", "Room 207", "2F", 26.0, -16.0, z_2nd, 12.0, 9.0, 'classroom', 'inner_west', 'academic')
    build_architectural_room("2F-20", "Room 208", "2F", 26.0, -5.0,  z_2nd, 12.0, 9.0, 'classroom', 'inner_west', 'academic')
    build_architectural_room("2F-21", "Room 209", "2F", 26.0, 6.0,   z_2nd, 12.0, 9.0, 'classroom', 'inner_west', 'academic')
    build_architectural_room("2F-22", "Room 210", "2F", 26.0, 17.0,  z_2nd, 12.0, 9.0, 'classroom', 'inner_west', 'academic')

    # North Wing Computer Laboratories (Modern Tech Cyan & Slate Theme)
    build_architectural_room("2F-09", "Comp Lab 1 (50 PCs)", "2F", -15.0, 28.0, z_2nd, 15.0, 9.5, 'complab', 'inner_south', 'complab')
    build_architectural_room("2F-11", "Comp Lab 2 (45 PCs)", "2F", 15.0,  28.0, z_2nd, 15.0, 9.5, 'complab', 'inner_south', 'complab')
    build_architectural_room("2F-13", "Canteen & Dining",    "2F", 0.0,   28.0, z_2nd, 13.0, 9.5, 'classroom', 'inner_south', 'academic')

    # South Wing Administrative Offices
    build_architectural_room("2F-01", "Cashier & Assessment", "2F", -26.0, -28.0, z_2nd, 12.0, 8.0, 'classroom', 'inner_north', 'executive')
    build_architectural_room("2F-02", "Office of Registrar",  "2F", 26.0,  -28.0, z_2nd, 12.0, 8.0, 'classroom', 'inner_north', 'executive')
    build_architectural_room("2F-28", "AVR / Multi-Purpose",  "2F", 0.0,   -28.0, z_2nd, 28.0, 8.0, 'classroom', 'inner_north', 'academic')

    # Restrooms (Vibrant Aqua Glazed Teal Tiles)
    build_architectural_room("2F-CR-NW", "Male Restroom (West)", "2F", -33.0, 33.0, z_2nd, 7.0, 7.0, 'restroom', 'inner_south', 'restroom')
    build_architectural_room("2F-CR-NE", "Female Restroom (East)", "2F", 33.0, 33.0, z_2nd, 7.0, 7.0, 'restroom', 'inner_south', 'restroom')
    build_architectural_room("2F-CR-SW", "Faculty Restroom SW", "2F", -33.0, -33.0, z_2nd, 7.0, 7.0, 'restroom', 'inner_north', 'restroom')
    build_architectural_room("2F-CR-SE", "Faculty Restroom SE", "2F", 33.0, -33.0, z_2nd, 7.0, 7.0, 'restroom', 'inner_north', 'restroom')

    # Fire Alarm Red Strobe Boxes along hallways
    for ax, ay in [(-18.0, -20.0), (18.0, -20.0), (-18.0, 2.0), (18.0, 2.0), (-12.0, 20.0), (12.0, 20.0)]:
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(ax, ay, z_2nd + 0.40))
        alarm = bpy.context.active_object
        alarm.name = f"Alarm_Box_{ax}_{ay}"
        alarm.scale = (0.7, 0.7, 0.35)
        alarm.data.materials.append(mats['chiller_red'])

    # ══════════════════ 3F LIBRARY & SCIENCE LABS ══════════════════
    build_architectural_room("3F-01", "NPC Central Library", "3F", 0.0, 26.0, z_3rd, 32.0, 11.0, 'complab', 'inner_south', 'complab')
    build_architectural_room("3F-10", "Science Lab 1 (Chem)", "3F", -26.0, 16.0, z_3rd, 14.0, 10.0, 'complab', 'inner_south', 'complab')
    build_architectural_room("3F-15", "Science Lab 2 (Bio)",  "3F", 26.0,  16.0, z_3rd, 14.0, 10.0, 'complab', 'inner_south', 'complab')
    build_architectural_room("3F-03", "Room 301", "3F", -26.0, -10.0, z_3rd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')
    build_architectural_room("3F-04", "Room 302", "3F", -26.0, 2.0,   z_3rd, 12.0, 9.0, 'classroom', 'inner_east', 'academic')
    build_architectural_room("3F-19", "Room 307", "3F", 26.0,  -10.0, z_3rd, 12.0, 9.0, 'classroom', 'inner_west', 'academic')
    build_architectural_room("3F-20", "Room 308", "3F", 26.0,  2.0,   z_3rd, 12.0, 9.0, 'classroom', 'inner_west', 'academic')

    # ══════════════════ 4F GYMNASIUM ARENA (FIT NA FIT, WALANG OVERFLOW!) ══════════════════
    # Massive 42m x 30m Full Regulation Arena na nakapatong sa solidong 4F floor plate!
    add_basketball_court("4F-GYM", 12.0, 16.0, z_4th, 42.0, 30.0, mats)

    # 4F West Wing Executive Suite & Sports Administration
    build_architectural_room("4F-01", "Dean's Office & Suite", "4F", -26.0, -14.0, z_4th, 14.0, 9.5, 'classroom', 'inner_east', 'executive')
    build_architectural_room("4F-02", "Faculty Executive Lounge", "4F", -26.0, -2.0, z_4th, 14.0, 9.5, 'classroom', 'inner_east', 'executive')
    build_architectural_room("4F-03", "Sports Admin & Coaches", "4F", -26.0, 10.0, z_4th, 14.0, 9.5, 'classroom', 'inner_east', 'academic')
    build_architectural_room("4F-CR-NW", "Gym Restrooms & Showers", "4F", -31.0, 28.0, z_4th, 9.0, 9.0, 'restroom', 'inner_south', 'restroom')

    # Red Outdoor AC Chiller Units on 4F Terrace
    add_ac_condenser_units(-20.0, 26.0, z_4th, 3, mats)
    add_ac_condenser_units(34.0, 16.0, z_4th, 3, mats)

    # ══════════════════ 1F GROUND ENTRANCE LOBBY ══════════════════
    build_architectural_room("1F-LOBBY", "Main Entrance Lobby", "1F", 0.0, -28.0, z_1st, 34.0, 12.0, 'classroom', 'inner_north', 'executive')

    # ══════════════════ EXTERIOR TIMBER LOUVERS & RIBBON GLASS ══════════════════
    louver_panel_w = 36.0
    louver_panel_h = z_roof - z_2nd
    louver_count = 38
    spacing = louver_panel_h / (louver_count + 1)
    for i in range(1, louver_count + 1):
        ly = -depth / 2.0 - 0.20
        lz = z_2nd + i * spacing
        bpy.ops.mesh.primitive_cube_add(size=1.0, location=(6.0, ly, lz))
        slat = bpy.context.active_object
        slat.name = f"Facade_Louver_{i}"
        slat.scale = (louver_panel_w, 0.08, 0.12)
        slat.data.materials.append(mats['louvers_timber'])

    # Top Clerestory Ribbon Glass
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(0.0, -depth/2.0 - 0.15, z_roof - 1.2))
    ribbon = bpy.context.active_object
    ribbon.name = "Clerestory_Ribbon_Glass"
    ribbon.scale = (60.0, 0.08, 2.2)
    ribbon.data.materials.append(mats['curtain_glass'])

    # Grand Entrance Drop-Off Canopy
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(0.0, -depth/2.0 - 4.5, 3.8))
    canopy = bpy.context.active_object
    canopy.name = "Entrance_Canopy"
    canopy.scale = (26.0, 9.0, 0.40)
    canopy.data.materials.append(mats['wall_warm'])

    # 3D Signage & Gold Seal
    bpy.ops.mesh.primitive_cube_add(size=1.0, location=(-8.0, -depth/2.0 - 0.15, z_roof + 1.8))
    sign = bpy.context.active_object
    sign.name = "Signage_Panel"
    sign.scale = (44.0, 0.08, 1.6)
    sign.data.materials.append(mats['wall_accent_navy'])

    bpy.ops.mesh.primitive_cylinder_add(radius=1.8, depth=0.18, location=(-28.0, -depth/2.0 - 0.22, z_3rd + 2.0))
    emblem = bpy.context.active_object
    emblem.rotation_euler = (math.radians(90), 0, 0)
    emblem.name = "NPC_Official_Seal"
    emblem.data.materials.append(mats['npc_gold'])

    print("NPC Full Super-Sized Architectural BIM Model created with Vibrant Colored Walls & Perfectly Fitted Gym!")

create_npc_model()

# Save .blend project
blend_path = os.path.join(os.path.dirname(__file__), "npc_campus.blend")
bpy.ops.wm.save_as_mainfile(filepath=blend_path)
print(f"Saved Blend Project: {blend_path}")

# Export to GLB
output_dir = os.path.join(os.path.dirname(__file__), "assets", "models")
os.makedirs(output_dir, exist_ok=True)
glb_path = os.path.join(output_dir, "npc_campus_blender.glb")

try:
    bpy.ops.export_scene.gltf(
        filepath=glb_path,
        export_format='GLB',
        use_selection=False,
        export_apply=True,
        export_materials='EXPORT',
        export_cameras=False,
        export_lights=False
    )
    print(f"GLTF Export Successful: {glb_path} ({os.path.getsize(glb_path)} bytes)")
except Exception as e:
    print(f"GLTF Export failed: {e}")
