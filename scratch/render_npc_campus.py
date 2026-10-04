import bpy
from mathutils import Vector

OUTPUT_BLEND = r"D:\xampp\htdocs\llama-b10483-bin-win-cpu-x64\LocalAI\app\npc_campus_rendered.blend"
OUTPUT_IMAGE = r"D:\xampp\htdocs\llama-b10483-bin-win-cpu-x64\LocalAI\app\assets\img\npc-campus-3d-render.png"


def point_at(obj, target):
    obj.rotation_euler = (Vector(target) - obj.location).to_track_quat('-Z', 'Y').to_euler()


scene = bpy.context.scene

# Remove only cameras/lights created by a prior run of this render setup.
for obj in list(bpy.data.objects):
    if obj.name.startswith('Render_') and obj.type in {'CAMERA', 'LIGHT'}:
        bpy.data.objects.remove(obj, do_unlink=True)

# Warm studio-style exterior lighting for a readable architectural render.
world = bpy.data.worlds.get('World') or bpy.data.worlds.new('World')
scene.world = world
world.use_nodes = True
background = world.node_tree.nodes.get('Background')
background.inputs['Color'].default_value = (0.025, 0.045, 0.085, 1.0)
background.inputs['Strength'].default_value = 0.28


def add_area(name, location, energy, size, color, target=(0, 0, 7)):
    data = bpy.data.lights.new(name, 'AREA')
    data.energy = energy
    data.shape = 'DISK'
    data.size = size
    data.color = color
    obj = bpy.data.objects.new(name, data)
    bpy.context.collection.objects.link(obj)
    obj.location = location
    point_at(obj, target)
    return obj


def add_sun(name, rotation, energy, color):
    data = bpy.data.lights.new(name, 'SUN')
    data.energy = energy
    data.color = color
    obj = bpy.data.objects.new(name, data)
    bpy.context.collection.objects.link(obj)
    obj.rotation_euler = rotation
    return obj


add_sun('Render_Sun', (0.55, -0.38, -0.52), 2.0, (1.0, 0.77, 0.53))
add_area('Render_Key', (46, -56, 60), 3400, 24, (0.72, 0.86, 1.0))
add_area('Render_Fill', (-48, -18, 34), 1900, 20, (0.35, 0.58, 1.0))
add_area('Render_Rim', (15, 48, 42), 2700, 18, (0.38, 0.85, 1.0))

# Isometric hero camera, framing the complete building and roof deck.
camera_data = bpy.data.cameras.new('Render_Camera')
camera_data.lens = 52
camera_data.sensor_width = 36
camera = bpy.data.objects.new('Render_Camera', camera_data)
bpy.context.collection.objects.link(camera)
camera.location = (84, -108, 74)
point_at(camera, (0, -4, 7.5))
scene.camera = camera

scene.render.engine = 'BLENDER_EEVEE'
scene.render.resolution_x = 1920
scene.render.resolution_y = 1080
scene.render.resolution_percentage = 100
scene.render.image_settings.file_format = 'PNG'
scene.render.image_settings.color_mode = 'RGBA'
scene.render.image_settings.color_depth = '8'
scene.render.filepath = OUTPUT_IMAGE
scene.render.film_transparent = False
scene.render.use_compositing = True
scene.view_settings.look = 'AgX - Medium High Contrast'

bpy.ops.wm.save_as_mainfile(filepath=OUTPUT_BLEND)
bpy.ops.render.render(write_still=True)
print(f'RENDERED: {OUTPUT_IMAGE}')
print(f'SAVED: {OUTPUT_BLEND}')
