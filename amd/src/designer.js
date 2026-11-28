// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The "My VR avatar" designer: a 3D preview of the avatar that follows the form, the choices on
 * tabs as picture tiles, ready-made looks, Surprise me, Undo and Redo, a save bar that says when
 * there are unsaved changes, saving through the local_vravatar_save_design web service, Reset to
 * default and a local camera preview.
 *
 * The preview is drawn by VR room's own avatar builder (vrroomruntime_builtin/live/avatarmodel), the
 * same module that draws the avatars in live rooms, so what a user designs is what others see. The
 * camera preview stays in this browser: nothing is sent anywhere. A costume (or a hat that is a
 * costume model) is a glTF model of this plugin: its address is on the radio button
 * (data-model) and the builder loads it.
 *
 * @module     local_vravatar/designer
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as THREE from 'vrroomruntime_builtin/three';
import * as AvatarModel from 'vrroomruntime_builtin/live/avatarmodel';
import Ajax from 'core/ajax';
import Log from 'core/log';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getString, getStrings} from 'core/str';
import {createTiles} from 'local_vravatar/tiles';
import {setupTabs, setupCostumeFilter, setupParts} from 'local_vravatar/tabs';
import {buildHand} from 'local_vravatar/handmodels';
import {setupCoach} from 'local_vravatar/coach';

/** Speed of the idle sway (radians per second at its middle, halved). */
const SPIN_SPEED = 0.25;

/** How far the idle sway turns the avatar each way (radians). */
const SWAY = 0.5;

/** How long the preview holds the face towards the viewer after a change or at the start (ms). */
const HOLD_MS = 3000;

/** How long the turntable waits after the user turned the avatar before it turns again (ms). */
const IDLE_MS = 5000;

/** How fast the avatar turns back to face the viewer (share of the angle per second). */
const RETURN_RATE = 6;

/** Radians turned per pixel dragged. */
const DRAG_SPEED = 0.01;

/** Radians turned per arrow key press. */
const KEY_STEP = Math.PI / 12;

/** Field of view of the preview camera (degrees). */
const FOV = 30;

/** How far below the head the close-up reaches, to show the shoulders (m). */
const SHOULDERS = 0.3;

/** Widest a body costume's shoulders make the close-up, in SHOULDERS (sleeves held out stop here). */
const WIDEST = 3;

/** Room left above the head (and its hat) in the close-up (m). */
const HEADROOM = 0.04;

/** How long the preview follows the avatar's size after a change, while a costume model loads (ms). */
const SETTLE_MS = 4000;

/** Camera video size for the preview, as in live rooms. */
const VIDEO = {width: {ideal: 320}, height: {ideal: 240}, frameRate: {ideal: 15}};

/** The walk shown in place: speed (m/s) and how long (ms); how long a wave lasts (ms). */
const WALK_SPEED = 1.1;
const WALK_MS = 3200;
const WAVE_MS = 2400;

/**
 * Moves the whole avatar plays now and then in the full-length view: the first after FIRST ms
 * (and a random part of SPREAD), then every EVERY ms (and SPREAD); none within QUIET ms of a
 * drag or a change.
 */
const MOVES_AUTO = Object.freeze({first: 8000, every: 12000, spread: 10000, quiet: 4000});

/** How far the preview turns to show the walk (radians). */
const WALK_TURN = 0.9;

/**
 * Keys of the document that take a name (radio groups; "outfit" is the costume picker). The face,
 * hair and headwear keys are those of VR room's avatarface, avatarhair and avatarheadwear modules
 * (design::MODULE_KEYS on the server).
 */
const CHOICES = [
    'body', 'head', 'skin', 'color', 'accent', 'hair', 'haircolor', 'eyes', 'brows', 'nose', 'beard', 'hat', 'outfit',
    'hands', 'handcolor', 'face',
    'eyecolor', 'eyespace', 'browcolor', 'mouth', 'lips', 'earshape', 'freckles', 'cheeks', 'marks', 'lines', 'makeup',
    'hairgrey', 'beardcolor', 'eyewear', 'eyewearcolor', 'facelayout',
];

/**
 * Surprise me: how often a key keeps its plain value (key => [value, chance]), so a random look has
 * a few extras, not all of them at once.
 */
const SURPRISE_PLAIN = {
    hat: ['none', 0.6],
    facelayout: ['balanced', 0.7],
    beard: ['none', 0.6],
    eyewear: ['none', 0.7],
    handcolor: ['skin', 0.5],
    eyecolor: ['classic', 0.2],
    eyespace: ['normal', 0.6],
    browcolor: ['hair', 0.75],
    beardcolor: ['hair', 0.7],
    hairgrey: ['none', 0.75],
    lips: ['none', 0.6],
    freckles: ['none', 0.6],
    cheeks: ['none', 0.6],
    marks: ['none', 0.75],
    lines: ['none', 0.65],
    makeup: ['none', 0.65],
};

/** Keys Surprise me leaves alone: the skin tone is the user's own. */
const KEEP_ON_SURPRISE = ['skin', 'picture'];

/** Most steps Undo remembers. */
const HISTORY_MAX = 50;

/** The keys whose chosen radio button may name a model to load (data-model), by asset name. */
const ASSETS = {outfit: 'outfit', hat: 'hat'};

/** Keys of the document that are true or false (checkboxes). */
const FLAGS = ['ears', 'glasses', 'picture'];

/** The strings the page uses, in this order. */
const STRINGS = [
    'saving', 'saved', 'camera_on', 'camera_off', 'camera_error', 'camerapreview', 'camerapreviewstop',
    'showwhole', 'surprisedone', 'undone', 'redone', 'costumefilter', 'costumeall', 'resetundo', 'partfilter',
];

/**
 * Read the design from the form.
 *
 * @param {HTMLFormElement} form The form.
 * @param {Object} defaults The default document.
 * @returns {Object} The document.
 */
const readForm = (form, defaults) => {
    const design = Object.assign({}, defaults);
    CHOICES.forEach((key) => {
        const checked = form.querySelector(`input[type="radio"][name="${key}"]:checked`);
        if (checked) {
            design[key] = checked.value;
        }
    });
    FLAGS.forEach((key) => {
        const box = form.querySelector(`input[type="checkbox"][name="${key}"]`);
        if (box) {
            design[key] = box.checked;
        }
    });
    // "glasses" has no box: it follows the eyewear, for viewers that know only the flag.
    if (typeof design.eyewear === 'string') {
        design.glasses = design.eyewear !== 'none';
    }
    return design;
};

/**
 * The models the form's choices name, for the avatar builder: asset name => address.
 *
 * @param {HTMLFormElement} form The form.
 * @returns {Object} {outfit: url, hat: url}, each only when chosen.
 */
const readAssets = (form) => {
    const assets = {};
    Object.keys(ASSETS).forEach((name) => {
        const checked = form.querySelector(`input[type="radio"][name="${ASSETS[name]}"]:checked`);
        if (checked && checked.dataset.model) {
            assets[name] = checked.dataset.model;
        }
    });
    return assets;
};

/**
 * Put a design into the form.
 *
 * @param {HTMLFormElement} form The form.
 * @param {Object} design The document.
 */
const writeForm = (form, design) => {
    CHOICES.forEach((key) => {
        form.querySelectorAll(`input[type="radio"][name="${key}"]`).forEach((radio) => {
            radio.checked = radio.value === design[key];
        });
    });
    FLAGS.forEach((key) => {
        const box = form.querySelector(`input[type="checkbox"][name="${key}"]`);
        if (box) {
            box.checked = !!design[key];
        }
    });
};

/**
 * The avatar builder's palette for one kind of swatch (data-palette), when it exports one.
 *
 * @param {string} palette "colour", "hair" or "skin".
 * @returns {Object|undefined} name => colour (CSS string or number)
 */
const builderPalette = (palette) => {
    switch (palette) {
        case 'hair':
            return AvatarModel.HAIR_COLOURS;
        case 'skin':
            return AvatarModel.SKIN_TONES || AvatarModel.SKIN_COLOURS;
        case 'colour':
            return AvatarModel.COLOURS;
        default:
            return undefined;
    }
};

/**
 * Paint the swatches with the avatar builder's own colours when it lists them, so a swatch shows
 * exactly the colour of the avatar.
 *
 * @param {HTMLElement} root The designer.
 */
const paintSwatches = (root) => {
    root.querySelectorAll('[data-swatch]').forEach((swatch) => {
        const palette = builderPalette(swatch.dataset.palette);
        const colour = palette && palette[swatch.dataset.swatch];
        if (typeof colour === 'string') {
            swatch.style.backgroundColor = colour;
        } else if (typeof colour === 'number') {
            swatch.style.backgroundColor = '#' + colour.toString(16).padStart(6, '0');
        }
    });
};

/**
 * Whether this browser can draw WebGL.
 *
 * @returns {boolean}
 */
const hasWebgl = () => {
    try {
        const probe = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (probe.getContext('webgl2') || probe.getContext('webgl')));
    } catch (e) {
        return false;
    }
};

/**
 * The angle between -pi and pi that turns the same way as the given one.
 *
 * @param {number} angle Radians.
 * @returns {number} Radians.
 */
const wrapAngle = (angle) => {
    const turn = Math.PI * 2;
    let wrapped = angle % turn;
    if (wrapped > Math.PI) {
        wrapped -= turn;
    } else if (wrapped < -Math.PI) {
        wrapped += turn;
    }
    return wrapped;
};

/**
 * The 3D preview: the avatar on a turntable, facing the viewer at first and after every change,
 * shown as a head-and-shoulders close-up or whole. Drag or the arrow keys turn it.
 *
 * The avatar looks along -Z (see vrroomruntime_builtin/live/avatarmodel), so the camera stands on
 * -Z looking back at it: with the turntable at 0 the face is towards the viewer.
 *
 * @param {HTMLCanvasElement} canvas The canvas.
 * @param {Object} design The document to show first.
 * @param {Object} assets The models it names ({outfit, hat}).
 * @param {Object} config Page config.
 * @returns {Object|null} {update(design, assets), setVideo(video|null), setFraming(framing),
 *     getFraming(), reframe()} or null when it
 *     cannot draw.
 */
const createPreview = (canvas, design, assets, config) => {
    if (typeof AvatarModel.createAvatar !== 'function') {
        Log.warn('local_vravatar: the VR room avatar builder is missing createAvatar');
        return null;
    }
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({canvas, antialias: true, alpha: true});
    } catch (e) {
        Log.warn('local_vravatar: no WebGL for the preview: ' + e);
        return null;
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) {
        renderer.outputColorSpace = THREE.SRGBColorSpace;
    }

    // Lights stay with the viewer (on -Z), not on the turntable, so the face is always lit when it
    // looks at the viewer. A warm, light ground colour keeps pale skin from looking grey.
    const scene = new THREE.Scene();
    scene.add(new THREE.HemisphereLight(0xffffff, 0xe6dccf, 1.1));
    const key = new THREE.DirectionalLight(0xfff8f0, 1.7);
    key.position.set(0.8, 3.4, -2.6);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.6);
    fill.position.set(-2.6, 1.8, -1.2);
    scene.add(fill);
    const rim = new THREE.DirectionalLight(0xffffff, 0.45);
    rim.position.set(0.5, 2.5, 3);
    scene.add(rim);

    const camera = new THREE.PerspectiveCamera(FOV, 3 / 4, 0.05, 50);
    const turntable = new THREE.Group();
    scene.add(turntable);

    const avatar = AvatarModel.createAvatar(THREE, design, {
        name: config.name,
        pictureurl: design.picture ? config.pictureurl : '',
        seed: config.seed,
        assets,
    });
    // The bouncer carries the idle motion and the little bounce after a change, so the framing
    // (worked out without it) stays still.
    const bouncer = new THREE.Group();
    turntable.add(bouncer);
    bouncer.add(avatar.group);
    // The avatar stands on the turntable's axis (its head is at x = z = 0), so it turns on the spot.
    avatar.group.position.set(0, 0, 0);

    // "face": the head close up; "whole": all of it, with the name tag; "hands": head, shoulders and
    // the hands held up.
    let framing = 'face';
    const HEAD = AvatarModel.STANDING_HEAD_HEIGHT || 1.6;
    let wavefrom = -Infinity;
    let waveuntil = -Infinity;
    // The walk shown in place (Walk button, or a body or costume change), until this time.
    let walkuntil = -Infinity;
    let bouncefrom = -Infinity;

    // Hand styles: drawn here until the avatar builder knows them.
    const ownhands = !(AvatarModel.CHOICES && AvatarModel.CHOICES.hands);
    let handmodels = [];
    let look = design;
    const cssColour = (list, name) => {
        const value = list ? list[name] : undefined;
        if (typeof value === 'number') {
            return '#' + value.toString(16).padStart(6, '0');
        }
        return typeof value === 'string' ? value : '#c99266';
    };
    const dressHands = () => {
        if (!ownhands) {
            return;
        }
        const body = cssColour(AvatarModel.COLOURS, look.color);
        const skin = look.skin === 'match' ? body : cssColour(AvatarModel.SKIN_TONES, look.skin);
        const colour = look.handcolor && look.handcolor !== 'skin' ? cssColour(AvatarModel.COLOURS, look.handcolor) : skin;
        const accent = cssColour(AvatarModel.COLOURS, look.accent);
        handmodels.forEach((made) => {
            made.group.removeFromParent();
            made.dispose();
        });
        handmodels = [];
        ['lefthand', 'righthand'].forEach((name) => {
            const mesh = avatar.group.getObjectByName(name);
            if (!mesh) {
                return;
            }
            if (!mesh.userData.localvravatar) {
                // The builder's ball is replaced by the style: the mesh keeps its place and visibility.
                mesh.geometry = new THREE.BufferGeometry();
                mesh.userData.localvravatar = true;
            }
            const made = buildHand(THREE, look.hands || 'sphere', colour, accent);
            if (name === 'lefthand') {
                made.group.scale.x = -1;
            }
            mesh.add(made.group);
            handmodels.push(made);
        });
    };

    /**
     * Pose the hands for the framing (the builder shows hands only in an immersive mode, so the
     * preview poses them as for a person in a headset), waving after a hand change.
     *
     * @param {number} now Time (ms).
     */
    const poseHands = (now) => {
        if (typeof avatar.setPose !== 'function') {
            return;
        }
        const head = [0, HEAD, 0, 0, 0, 0, 1];
        const waving = now < waveuntil;
        if (framing === 'face' || (framing === 'whole' && !waving && !ownhands)) {
            // As a desktop person: the arms hang from the shoulders and move a little (the builder's
            // resting hands, breathing and, during the walk, the arm swing).
            avatar.setPose({h: head, l: null, r: null}, '3d');
            return;
        }
        // The avatar looks along -Z, so its left hand is on +X.
        // Held up beside the head, palms to the viewer (not pointing at the camera, which would
        // foreshorten them), or by the hips.
        const up = framing === 'hands';
        const y = up ? HEAD - 0.12 : HEAD - 0.7;
        const z = up ? -0.1 : -0.08;
        const x = up ? 0.36 : 0.3;
        const wave = waving ? Math.sin((now - wavefrom) / 1000 * Math.PI * 4) * 0.05 : 0;
        const lift = waving && !up ? 0.45 : 0;
        // Hand rotations in the hand's grip frame (-Z along the index finger, +Y the back of the
        // hand): turned +90 degrees about X the fingers point up and the palm faces the viewer;
        // -90 degrees, the fingers hang down.
        const s = Math.SQRT1_2;
        const raised = [s, 0, 0, s];
        const hanging = [-s, 0, 0, s];
        const left = (up ? raised : hanging);
        const right = (up || lift ? raised : hanging);
        avatar.setPose({h: head, l: [x, y, z].concat(left),
            r: [-x + wave, y + lift, z - (lift ? 0.1 : 0)].concat(right)}, 'vr');
        handmodels.forEach((made) => {
            // Fingers up when the hands are held up (or waving), down by the hips.
            made.group.rotation.z = up || (lift && made.group.scale.x > 0) ? 0 : Math.PI;
        });
    };

    /**
     * The name tag shows only with the whole avatar ("how others see me").
     */
    const showLabel = () => {
        const label = avatar.group.getObjectByName('label');
        if (label) {
            label.visible = framing === 'whole';
        }
        const ring = avatar.group.getObjectByName('ring');
        if (ring) {
            ring.visible = framing === 'whole';
        }
    };

    /**
     * Point the camera at a box (in turntable coordinates) from -Z so that it fits the canvas.
     *
     * @param {number} bottom Lowest point (m).
     * @param {number} top Highest point (m).
     * @param {number} width Width (m).
     * @param {number} depth Depth (m).
     * @param {number} margin Extra room around it (1 = none).
     */
    const aim = (bottom, top, width, depth, margin) => {
        const middle = (bottom + top) / 2;
        const height = Math.max(top - bottom, width / Math.max(camera.aspect, 0.1)) * margin;
        const distance = (height / 2) / Math.tan(THREE.MathUtils.degToRad(FOV / 2)) + depth / 2;
        camera.position.set(0, middle + height * 0.04, -distance);
        camera.lookAt(0, middle, 0);
    };

    /**
     * Grow a box by the parts of an object that are drawn: a hidden part (the head a device takes
     * the place of, the body a costume takes the place of) does not count, nor anything under it.
     *
     * @param {Object} object THREE.Object3D, its world matrices up to date.
     * @param {Object} box THREE.Box3 to grow.
     * @param {Function} [keep] Called with (x, y, z) world points; only those it accepts count.
     * @returns {Object} The box.
     */
    const visibleBox = (object, box, keep) => {
        const part = new THREE.Box3();
        const point = new THREE.Vector3();
        const visit = (node) => {
            if (!node.visible) {
                return;
            }
            const geometry = node.geometry;
            if (geometry && (node.isMesh || node.isSprite || node.isLine || node.isPoints)) {
                if (keep && geometry.attributes && geometry.attributes.position && !node.isSkinnedMesh) {
                    // Only the points that are kept: the vertices themselves (costumes are small).
                    const position = geometry.attributes.position;
                    for (let i = 0; i < position.count; i++) {
                        point.fromBufferAttribute(position, i).applyMatrix4(node.matrixWorld);
                        if (keep(point.x, point.y, point.z)) {
                            box.expandByPoint(point);
                        }
                    }
                } else {
                    if (node.isSkinnedMesh && typeof node.computeBoundingBox === 'function') {
                        node.computeBoundingBox();
                        part.copy(node.boundingBox);
                    } else {
                        if (!geometry.boundingBox) {
                            geometry.computeBoundingBox();
                        }
                        part.copy(geometry.boundingBox);
                    }
                    part.applyMatrix4(node.matrixWorld);
                    if (!keep || keep((part.min.x + part.max.x) / 2, (part.min.y + part.max.y) / 2,
                        (part.min.z + part.max.z) / 2)) {
                        box.union(part);
                    }
                }
            }
            node.children.forEach(visit);
        };
        visit(object);
        return box;
    };

    // Frame the avatar with the turntable facing front: the head and shoulders, or all of it.
    // eslint-disable-next-line complexity
    const frameNow = () => {
        const whole = framing === 'whole';
        const angle = turntable.rotation.y;
        turntable.rotation.y = 0;
        turntable.updateMatrixWorld(true);
        if (framing === 'hands') {
            // The head and both hands, with a little room round them.
            const handsbox = new THREE.Box3();
            ['head', 'lefthand', 'righthand'].forEach((name) => {
                const part = avatar.group.getObjectByName(name);
                if (part && part.visible) {
                    visibleBox(part, handsbox);
                }
            });
            turntable.rotation.y = angle;
            turntable.updateMatrixWorld(true);
            if (!handsbox.isEmpty()) {
                const hsize = handsbox.getSize(new THREE.Vector3());
                const hwidth = 2 * Math.max(Math.abs(handsbox.min.x), Math.abs(handsbox.max.x));
                aim(handsbox.min.y - 0.08, handsbox.max.y + HEADROOM, hwidth, hsize.z, 1.15);
                return;
            }
            turntable.rotation.y = 0;
            turntable.updateMatrixWorld(true);
        }
        // Looked up each time: a costume may change the parts.
        const head = avatar.group.getObjectByName('head');
        const label = avatar.group.getObjectByName('label');
        const costume = avatar.group.getObjectByName('costume');
        // A device (a laptop, a monitor, a TV, a computer) hides the head: its screen is the face.
        const device = !!(costume && head && !head.visible);
        const closeup = !whole && !!head;
        const box = new THREE.Box3();
        if (!closeup) {
            // Everything that is drawn: wide sleeves, a TV's sides and a computer's base fit too.
            visibleBox(avatar.group, box);
            if (box.isEmpty()) {
                box.setFromObject(avatar.group);
            }
        } else if (device) {
            // The device takes the place of the head: all of it, with its frame round the screen.
            visibleBox(costume, box);
            const screen = avatar.group.getObjectByName('facescreen');
            if (screen) {
                visibleBox(screen, box);
            }
        } else {
            visibleBox(head, box);
            if (box.isEmpty()) {
                box.setFromObject(head);
            }
        }
        // The close-up keeps the name tag above the head in view (a tall hair style or hat pushes
        // it up).
        if (closeup && !box.isEmpty() && label && label.visible) {
            box.union(new THREE.Box3().setFromObject(label));
        }
        // A body costume's shoulders and sleeves: as wide as the costume is at the shoulders.
        let shoulders = 0;
        if (closeup && !device && costume && costume.visible && !box.isEmpty()) {
            const low = box.min.y - SHOULDERS;
            const high = box.min.y + 0.02;
            const band = visibleBox(costume, new THREE.Box3(), (x, y) => y >= low && y <= high);
            if (!band.isEmpty()) {
                shoulders = Math.min(2 * Math.max(Math.abs(band.min.x), Math.abs(band.max.x)), SHOULDERS * WIDEST);
            }
        }
        turntable.rotation.y = angle;
        turntable.updateMatrixWorld(true);
        if (box.isEmpty()) {
            const y = AvatarModel.STANDING_HEAD_HEIGHT || 1.6;
            camera.position.set(0, y, whole ? -4 : -1.5);
            camera.lookAt(0, whole ? y / 2 : y - 0.1, 0);
            return;
        }
        const size = box.getSize(new THREE.Vector3());
        // Keep the axis in the middle of the picture: as wide as the widest side of it.
        const width = 2 * Math.max(Math.abs(box.min.x), Math.abs(box.max.x));
        if (!closeup) {
            // Room under the feet for the shadow, and for the toolbar where it lies over the preview.
            const under = toolbarOver() ? 0.16 : 0.05;
            // Room over the name tag for the tip, while one is shown over the top of the preview.
            const over = tipShown() ? 0.14 : 0;
            aim(box.min.y - size.y * under, box.max.y + size.y * over, width, size.z, 1.04);
        } else if (device) {
            aim(box.min.y, box.max.y + HEADROOM, width, size.z, 1.1);
        } else {
            aim(box.min.y - SHOULDERS, box.max.y + HEADROOM, Math.max(width, SHOULDERS * 1.6, shoulders), size.z, 1.05);
        }
    };

    /**
     * Whether a coached-start tip is shown over the top of the preview.
     *
     * @returns {boolean}
     */
    const tipShown = () => {
        const tip = canvas.parentNode ? canvas.parentNode.querySelector('[data-region="coach"]') : null;
        return !!tip && !tip.hidden;
    };

    /**
     * Whether the toolbar lies over the bottom of the preview (on a phone it has its own strip).
     *
     * @returns {boolean}
     */
    const toolbarOver = () => {
        const bar = canvas.parentNode ? canvas.parentNode.querySelector('[data-region="tools"]') : null;
        if (!bar || bar.hidden) {
            return false;
        }
        const barrect = bar.getBoundingClientRect();
        return barrect.top < canvas.getBoundingClientRect().bottom - 1;
    };

    /**
     * The builder's soft shadow lies on the room's floor, 1.6 m under the head. The preview has no
     * floor: the shadow goes just under the bottom of the body, so the figure stands on it instead
     * of floating high above it, and it is left out when framing.
     *
     * @returns {Object|null} The shadow, or null.
     */
    const groundShadow = () => {
        const shadow = avatar.group.getObjectByName('shadow');
        if (!shadow) {
            return null;
        }
        const shown = shadow.visible;
        shadow.visible = false;
        avatar.group.updateMatrixWorld(true);
        const box = visibleBox(avatar.group, new THREE.Box3());
        shadow.visible = shown;
        if (!box.isEmpty()) {
            shadow.position.y = box.min.y - 0.01;
        }
        return shadow;
    };

    // Frame without the idle motion, the bounce and the shadow.
    const frame = () => {
        const y = bouncer.position.y;
        const scale = bouncer.scale.x;
        bouncer.position.y = 0;
        bouncer.scale.setScalar(1);
        const shadow = groundShadow();
        const shown = shadow ? shadow.visible : false;
        if (shadow) {
            shadow.visible = false;
        }
        frameNow();
        if (shadow) {
            shadow.visible = shown;
        }
        bouncer.position.y = y;
        bouncer.scale.setScalar(scale);
    };

    const resize = () => {
        const width = canvas.clientWidth || 300;
        const height = canvas.clientHeight || 400;
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        frame();
    };

    const reducedmotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    const still = () => !!(reducedmotion && reducedmotion.matches);
    // Moves (the builder's dances): the last played, when the next plays by itself, the last drag or change.
    let lastmove = null;
    let nextmove = performance.now() + MOVES_AUTO.first + Math.random() * MOVES_AUTO.spread * 0.4;
    let interacted = -Infinity;
    const moving = () => typeof avatar.isMoving === 'function' && avatar.isMoving();
    /**
     * Play a move (one not just played, when no name is given).
     *
     * @param {string|null} name Move name, or null for the next one.
     * @returns {number} How long it lasts (ms), 0 if none plays.
     */
    const playMove = (name) => {
        if (typeof avatar.move !== 'function') {
            return 0;
        }
        const list = avatar.moves();
        const others = list.filter((n) => n !== lastmove);
        const from = others.length ? others : list;
        const pick = name || from[Math.floor(Math.random() * from.length)];
        const ms = pick ? avatar.move(pick) : 0;
        if (ms) {
            lastmove = pick;
        }
        return ms;
    };
    let dragging = null;
    let hovering = false;
    let focused = false;
    let last = performance.now();
    // Time spent swaying (s).
    let swaytime = 0;
    // Hold the face towards the viewer until then, then turn slowly.
    let holduntil = last + HOLD_MS;
    // The user turned the avatar: leave it as they put it until then.
    let touched = -Infinity;
    // Turn back to face the viewer (after a change).
    let returning = false;
    // Until then, follow the head while it grows or shrinks with the camera video.
    let videofade = -Infinity;
    // Until then, follow the avatar's size while a costume model loads after a change.
    let settleuntil = -Infinity;
    let dirty = true;
    let videoshown = false;

    /**
     * Show the face to the viewer: at once with reduced motion, else by turning back.
     */
    const faceFront = () => {
        holduntil = performance.now() + HOLD_MS;
        swaytime = 0;
        touched = -Infinity;
        if (still()) {
            turntable.rotation.y = 0;
            returning = false;
        } else {
            turntable.rotation.y = wrapAngle(turntable.rotation.y);
            returning = true;
        }
        dirty = true;
    };

    const draw = () => {
        renderer.render(scene, camera);
        dirty = false;
    };
    // The preview's frame loop: turntable, return to the front, framing and the webcam fade.
    // eslint-disable-next-line complexity
    const tick = (now) => {
        const dt = Math.min((now - last) / 1000, 0.1);
        last = now;
        let moved = false;
        if (returning && !dragging) {
            const angle = turntable.rotation.y;
            if (Math.abs(angle) < 0.002) {
                turntable.rotation.y = 0;
                returning = false;
            } else {
                turntable.rotation.y = angle - angle * Math.min(1, RETURN_RATE * dt);
            }
            moved = true;
        } else if (!dragging && !hovering && !focused && !still() && now >= holduntil && now - touched >= IDLE_MS) {
            // A slow sway from side to side, never far from the front, so the face stays in view.
            // Turned further by the user: back to the front first.
            if (Math.abs(wrapAngle(turntable.rotation.y)) > SWAY + 0.01) {
                turntable.rotation.y = wrapAngle(turntable.rotation.y);
                returning = true;
                swaytime = 0;
            }
            swaytime += dt;
            turntable.rotation.y = SWAY * Math.sin(swaytime * SPIN_SPEED * 2);
            moved = true;
        }
        // A gentle breathing motion, a bounce after a change, a wave after a hand change.
        if (!still()) {
            const since = now - bouncefrom;
            const bounce = since < 450 ? Math.sin(since / 450 * Math.PI) : 0;
            bouncer.position.y = Math.sin(now / 900) * 0.006 + bounce * 0.035;
            bouncer.scale.setScalar(1 + bounce * 0.04);
            // The arms, the breathing and the walk move every frame.
            if (walkuntil > 0 && now >= walkuntil && typeof avatar.setWalkDemo === 'function') {
                avatar.setWalkDemo(0);
                walkuntil = -Infinity;
                returning = true;
            }
            // Now and then a move, in the full-length view, while nobody is turning or changing the avatar.
            if (framing !== 'whole' || dragging || now - interacted < MOVES_AUTO.quiet || now < walkuntil || moving()) {
                nextmove = Math.max(nextmove, now + 1000);
            } else if (now >= nextmove) {
                nextmove = now + playMove(null) + MOVES_AUTO.every + Math.random() * MOVES_AUTO.spread;
            }
            poseHands(now);
            if (typeof avatar.update === 'function') {
                avatar.update(now);
            }
            if (now < waveuntil + 100 || moving()) {
                groundShadow();
            }
            moved = true;
        } else if (moving()) {
            // Reduced motion: no move plays by itself, but one asked for (the button) is shown.
            poseHands(now);
            avatar.update(now);
            groundShadow();
            moved = true;
        }
        const fading = now < videofade;
        const settling = now < settleuntil;
        if (fading || videoshown || settling) {
            if (typeof avatar.update === 'function') {
                avatar.update(now);
            }
            if (fading || settling) {
                frame();
            }
        }
        // A live camera picture changes every frame; a loading model appears when it arrives.
        if (moved || dirty || videoshown || fading || settling) {
            draw();
        }
        window.requestAnimationFrame(tick);
    };

    /**
     * The user turned the avatar by a number of radians.
     *
     * @param {number} by Radians.
     */
    const turn = (by) => {
        interacted = performance.now();
        turntable.rotation.y = wrapAngle(turntable.rotation.y + by);
        returning = false;
        // The sway starts again from where the user left the avatar.
        swaytime = Math.asin(Math.max(-1, Math.min(1, turntable.rotation.y / SWAY))) / (SPIN_SPEED * 2);
        touched = performance.now();
        dirty = true;
    };

    canvas.addEventListener('pointerdown', (e) => {
        dragging = {x: e.clientX, id: e.pointerId};
        canvas.setPointerCapture(e.pointerId);
        returning = false;
    });
    canvas.addEventListener('pointermove', (e) => {
        if (dragging && dragging.id === e.pointerId) {
            turn((e.clientX - dragging.x) * DRAG_SPEED);
            dragging.x = e.clientX;
        }
    });
    const release = (e) => {
        if (dragging && dragging.id === e.pointerId) {
            dragging = null;
            touched = performance.now();
        }
    };
    canvas.addEventListener('pointerup', release);
    canvas.addEventListener('pointercancel', release);
    // The turntable stops while the pointer is on the preview or it has the focus.
    canvas.addEventListener('pointerenter', () => {
        hovering = true;
    });
    canvas.addEventListener('pointerleave', () => {
        hovering = false;
    });
    canvas.addEventListener('focus', () => {
        focused = true;
    });
    canvas.addEventListener('blur', () => {
        focused = false;
    });
    canvas.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            turn(e.key === 'ArrowLeft' ? -KEY_STEP : KEY_STEP);
            e.preventDefault();
        }
    });
    if (reducedmotion && typeof reducedmotion.addEventListener === 'function') {
        reducedmotion.addEventListener('change', () => {
            if (still()) {
                faceFront();
            }
        });
    }

    if (window.ResizeObserver) {
        new ResizeObserver(() => {
            resize();
            dirty = true;
        }).observe(canvas);
    } else {
        window.addEventListener('resize', () => {
            resize();
            dirty = true;
        });
    }
    dressHands();
    showLabel();
    poseHands(performance.now());
    turntable.rotation.y = 0;
    resize();
    draw();
    window.requestAnimationFrame(tick);

    /**
     * Frame the avatar again once a loading costume model has arrived, when the builder says so.
     *
     * @param {*} result What the builder returned: a promise when it loads a model.
     */
    const whenLoaded = (result) => {
        settleuntil = performance.now() + SETTLE_MS;
        // The builder's whenDressed() waits for every model asked for (costume and model hat).
        const dressed = typeof avatar.whenDressed === 'function' ? avatar.whenDressed() : result;
        if (dressed && typeof dressed.then === 'function') {
            Promise.resolve(dressed).then(() => {
                frame();
                dirty = true;
                return null;
            }).catch((e) => {
                Log.debug('local_vravatar: costume model: ' + e);
            });
        }
    };
    whenLoaded(avatar.ready);

    /**
     * Show the avatar walking in place, or waving, for a moment (as others see it move).
     *
     * @param {string} what "walk", "wave" or "move" (the next dance).
     */
    const demo = (what) => {
        const now = performance.now();
        interacted = now;
        if (what === 'move' || (what === 'wave' && framing === 'whole' && typeof avatar.move === 'function')) {
            // Asked for: plays with reduced motion too (short, and only when asked).
            playMove(what === 'wave' ? 'wave' : null);
            return;
        }
        if (still()) {
            return;
        }
        if (what === 'walk' && typeof avatar.setWalkDemo === 'function') {
            avatar.setWalkDemo(WALK_SPEED);
            walkuntil = now + WALK_MS;
            // Seen from three-quarters, where a stride reads; back to the front afterwards.
            turntable.rotation.y = WALK_TURN;
            returning = false;
            holduntil = walkuntil;
            dirty = true;
        } else if (what === 'wave') {
            wavefrom = now;
            waveuntil = now + WAVE_MS;
        }
    };

    return {
        demo: demo,
        update: (next, nextassets) => {
            const handschanged = next.hands !== look.hands || next.handcolor !== look.handcolor;
            const bodychanged = next.body !== look.body || next.outfit !== look.outfit;
            look = next;
            interacted = performance.now();
            whenLoaded(avatar.setAppearance(next, next.picture ? config.pictureurl : '', nextassets));
            dressHands();
            showLabel();
            poseHands(performance.now());
            frame();
            faceFront();
            if (handschanged && framing === 'whole' && typeof avatar.move === 'function') {
                playMove('wave');
            } else if (handschanged) {
                wavefrom = performance.now();
                waveuntil = wavefrom + WAVE_MS / 2;
            } else {
                bouncefrom = performance.now();
            }
            if (bodychanged && framing === 'whole') {
                demo('walk');
            }
        },
        setVideo: (video) => {
            avatar.setVideo(video);
            videoshown = !!video;
            // The head grows or shrinks with the video's fade: follow it a little longer than that.
            videofade = performance.now() + (AvatarModel.FADE_MS || 300) + 400;
            faceFront();
        },
        setFraming: (value) => {
            const was = framing;
            framing = value === 'whole' || value === 'hands' ? value : 'face';
            if (framing === 'whole' && was !== 'whole') {
                nextmove = performance.now() + MOVES_AUTO.first + Math.random() * MOVES_AUTO.spread * 0.4;
            }
            showLabel();
            poseHands(performance.now());
            frame();
            faceFront();
        },
        getFraming: () => framing,
        reframe: () => {
            frame();
            dirty = true;
        },
    };
};

/**
 * The zoom button: shows the whole avatar or the head-and-shoulders close-up.
 *
 * @param {HTMLButtonElement} button The button.
 * @param {Object} preview The preview.
 * @returns {Object} {tab(framing): the tab's framing ("face", "whole" or "hands"), whole(): the whole
 *     avatar for now (after a new look)}
 */
const setupZoom = (button, preview) => {
    // A toggle button: the label stays "Show the whole avatar", aria-pressed (and the active look)
    // says whether it is on. Each tab chooses its own framing; the button switches between that and
    // the whole avatar.
    let tabframing = 'face';
    const show = (value) => {
        preview.setFraming(value);
        const whole = value === 'whole';
        button.setAttribute('aria-pressed', whole ? 'true' : 'false');
        button.classList.toggle('active', whole);
    };
    button.hidden = false;
    button.addEventListener('click', () => show(preview.getFraming() === 'whole' && tabframing !== 'whole' ? tabframing : 'whole'));
    return {
        tab: (value) => {
            tabframing = value;
            show(value);
        },
        whole: () => show('whole'),
    };
};

/**
 * The camera preview button: shows the user's own camera on the face screen of the preview, as
 * the others in a live room would see it. Nothing is sent.
 *
 * @param {HTMLButtonElement} button The button.
 * @param {Object} preview The preview.
 * @param {Function} status Sets the status line.
 * @param {Object} str Strings.
 */
const setupCamera = (button, preview, status, str) => {
    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
        return;
    }
    let stream = null;
    let video = null;
    // The button's words (beside its icon); on a phone they are for screen readers only.
    const setText = (text) => {
        const words = button.querySelector('[data-region="cameralabel"]');
        (words || button).textContent = text;
        button.title = text;
    };
    const stop = () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
        }
        stream = null;
        video = null;
        preview.setVideo(null);
        button.setAttribute('aria-pressed', 'false');
        setText(str.camerapreview);
    };
    button.hidden = false;
    button.addEventListener('click', async() => {
        if (stream) {
            stop();
            status(str.camera_off);
            return;
        }
        button.disabled = true;
        try {
            stream = await navigator.mediaDevices.getUserMedia({video: VIDEO, audio: false});
            video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.srcObject = stream;
            await video.play();
            preview.setVideo(video);
            button.setAttribute('aria-pressed', 'true');
            setText(str.camerapreviewstop);
            status(str.camera_on);
        } catch (e) {
            Log.debug('local_vravatar: camera preview: ' + e);
            stop();
            status(str.camera_error);
        } finally {
            button.disabled = false;
        }
    });
    window.addEventListener('pagehide', stop);
};

/**
 * Start the designer.
 *
 * @param {Object|null} config {design, defaults, pictureurl, pictureallowed, name, seed}; null: read from the page
 */
export const init = async(config = null) => {
    const pending = new Pending('local_vravatar/designer:init');
    const root = document.querySelector('[data-region="local-vravatar-designer"]');
    // The page's settings: given, or in the page's data attribute (too long to pass to js_call_amd).
    const holder = document.querySelector('[data-region="local-vravatar-config"]');
    if (!config && holder) {
        config = JSON.parse(holder.dataset.config || 'null');
    }
    if (!root || !config) {
        pending.resolve();
        return;
    }
    const form = root.querySelector('[data-region="form"]');
    const statusline = root.querySelector('[data-region="status"]');
    const resetbutton = root.querySelector('[data-action="reset"]');
    const savebutton = root.querySelector('[data-action="save"]');
    const removeform = root.querySelector('[data-region="removeform"]');
    const nodesign = root.querySelector('[data-region="nodesign"]');
    const canvas = root.querySelector('[data-region="canvas"]');
    const fallback = root.querySelector('[data-region="fallback"]');
    const previewhelp = root.querySelector('[data-region="previewhelp"]');
    const camerabutton = root.querySelector('[data-action="camera"]');
    const zoombutton = root.querySelector('[data-action="zoom"]');

    const values = await getStrings(STRINGS.map((key) => ({key, component: 'local_vravatar'})));
    const str = {};
    STRINGS.forEach((key, i) => {
        str[key] = values[i];
    });
    const status = (text) => {
        statusline.textContent = text;
    };

    paintSwatches(root);
    root.classList.add('local-vravatar-js');
    // The user context's header offers "Message" (to oneself) on this page of one's own: hidden. Its
    // Bootstrap d-block class is !important, so only an inline !important hides it.
    const headerbuttons = document.querySelector('.page-context-header .header-button-group');
    if (headerbuttons) {
        headerbuttons.style.setProperty('display', 'none', 'important');
    }

    let preview = null;
    let setZoom = null;
    if (hasWebgl()) {
        canvas.hidden = false;
        preview = createPreview(canvas, readForm(form, config.defaults), readAssets(form), config);
        if (preview) {
            fallback.hidden = true;
            previewhelp.hidden = false;
            setZoom = setupZoom(zoombutton, preview);
            setupCamera(camerabutton, preview, status, str);
            ['walk', 'wave', 'move'].forEach((what) => {
                const button = root.querySelector('[data-action="demo' + what + '"]');
                if (button) {
                    button.hidden = false;
                    button.addEventListener('click', () => {
                        if (setZoom) {
                            setZoom.whole();
                        }
                        preview.demo(what);
                    });
                }
            });
        } else {
            canvas.hidden = true;
        }
    }
    const tilepainter = hasWebgl() ? createTiles(config) : null;

    /**
     * The colour of a palette entry: the avatar builder's own, else the page's swatch.
     *
     * @param {string} palette "colour", "hair" or "skin".
     * @param {string} name Entry.
     * @returns {string} A CSS colour, or ''.
     */
    const colourOf = (palette, name) => {
        const list = builderPalette(palette);
        const colour = list && list[name];
        if (typeof colour === 'string') {
            return colour;
        }
        if (typeof colour === 'number') {
            return '#' + colour.toString(16).padStart(6, '0');
        }
        const swatch = root.querySelector(`[data-palette="${palette}"][data-swatch="${name}"]`);
        return swatch ? swatch.style.backgroundColor : '';
    };

    /**
     * Paint what follows other choices: the hand silhouettes (in the hand colour), and the swatches
     * without a colour of their own (data-follows): "Same as body colour" (the body colour), "Same
     * as skin" and no lip colour (the skin colour), "Same as hair" (the hair colour) and "Greying"
     * (the hair colour, with grey strands drawn by the style sheet).
     *
     * @param {Object} design The design in the form.
     */
    const paintFollowers = (design) => {
        const body = colourOf('colour', design.color);
        const skin = design.skin === 'match' ? body : colourOf('skin', design.skin);
        const hand = design.handcolor === 'skin' ? skin : colourOf('colour', design.handcolor);
        const hair = colourOf('hair', design.haircolor);
        const followed = {body, skin, hair, greying: hair};
        root.querySelectorAll('[data-region="handpic"]').forEach((pic) => {
            pic.style.backgroundColor = hand;
        });
        root.querySelectorAll('.local-vravatar-swatch-follows').forEach((swatch) => {
            const colour = followed[swatch.dataset.follows];
            swatch.style.backgroundColor = colour === undefined ? skin : colour;
        });
    };

    // Only the pictures in view: a part or costume category hidden behind its button draws when shown.
    const paintPanel = (panel, first) => {
        if (tilepainter && panel) {
            // Hidden behind a part's or a costume category's button: drawn when shown.
            const holders = Array.from(panel.querySelectorAll('[data-render]')).filter((holder) => {
                for (let node = holder.parentElement; node && node !== panel; node = node.parentElement) {
                    if (node.hidden) {
                        return false;
                    }
                }
                return true;
            });
            tilepainter.paint(readForm(form, config.defaults), holders, first);
        }
    };
    const panels = Array.from(form.querySelectorAll('[data-region="panel"]'));
    // The tabs with many choices show one part at a time (set up before the first tab draws its pictures).
    if (root.querySelector('[data-region="tablist"]')) {
        panels.forEach((panel) => setupParts(panel, str.partfilter, (shown) => paintPanel(shown, true)));
    }
    const tabs = setupTabs(root, (panel, tab) => {
        if (setZoom && tab.dataset.frame) {
            setZoom.tab(tab.dataset.frame);
        }
        paintPanel(panel, true);
    }, nodesign ? 0 : 1);

    // The coached start, for a first visit; "?" shows it again. The preview reframes when a tip
    // comes or goes, to keep the name tag clear of it.
    root.addEventListener('local-vravatar:coach', () => {
        if (preview) {
            preview.reframe();
        }
    });
    const coach = setupCoach(root, !!nodesign);
    const currentTab = () => {
        const panel = tabs ? tabs.active() : null;
        return panel ? panel.getAttribute('aria-labelledby') || '' : '';
    };
    const toolbar = root.querySelector('[data-region="tools"]');
    if (toolbar) {
        toolbar.hidden = false;
    }

    // The group headings stick under the tabs: the tabs' height, as it is now.
    const tablist = root.querySelector('[data-region="tablist"]');
    if (tablist && window.ResizeObserver) {
        new ResizeObserver(() => {
            root.style.setProperty('--local-vravatar-tabs', tablist.offsetHeight + 'px');
        }).observe(tablist);
    }

    /**
     * Name the chosen colour beside each colour group's heading (the swatches show no names).
     */
    const nameChosen = () => {
        root.querySelectorAll('[data-region="chosen"]').forEach((chosen) => {
            const group = chosen.closest('fieldset');
            const checked = group ? group.querySelector('input:checked + label .local-vravatar-tile-name') : null;
            chosen.textContent = checked ? checked.textContent.trim() : '';
        });
    };
    const costumes = root.querySelector('.local-vravatar-costumes');
    setupCostumeFilter(costumes, str.costumefilter, str.costumeall,
        () => paintPanel(costumes.closest('[data-region="panel"]'), true));

    /**
     * Draw the tile pictures that are out of date: those of the tab in view (after a change the
     * other tabs draw theirs when they are opened: with the long lists of faces, hair and hats a
     * change would otherwise redraw some 300 pictures). Without tabs, every section's.
     */
    const repaint = () => {
        const active = tabs ? tabs.active() : null;
        if (active) {
            paintPanel(active, true);
            return;
        }
        panels.forEach((panel) => paintPanel(panel, false));
    };

    // Ready-made looks, drawn on the user's own avatar.
    const presetbuttons = Array.from(root.querySelectorAll('[data-action="preset"]'));
    const presetOf = (button) => {
        try {
            const keys = JSON.parse(button.dataset.design || '{}');
            return keys && typeof keys === 'object' ? keys : {};
        } catch (e) {
            return {};
        }
    };

    // Undo and Redo: the designs the form has held, as JSON.
    let saved = JSON.stringify(readForm(form, config.defaults));
    const history = [saved];
    let at = 0;
    const dirtyflag = root.querySelector('[data-region="dirty"]');
    const surprisebuttons = Array.from(root.querySelectorAll('[data-action="surprise"]'));
    const undobutton = root.querySelector('[data-action="undo"]');
    const redobutton = root.querySelector('[data-action="redo"]');
    const isDirty = () => JSON.stringify(readForm(form, config.defaults)) !== saved;

    /**
     * After the form changed: the preview, the followers, the history, the save bar and the tiles.
     *
     * @param {boolean} record Whether this is a new step for Undo (not an Undo or Redo itself).
     */
    const changed = (record) => {
        const design = readForm(form, config.defaults);
        if (preview) {
            preview.update(design, readAssets(form));
        }
        paintFollowers(design);
        nameChosen();
        const json = JSON.stringify(design);
        if (record && json !== history[at]) {
            history.splice(at + 1);
            history.push(json);
            if (history.length > HISTORY_MAX) {
                history.shift();
            }
            at = history.length - 1;
        }
        undobutton.disabled = at === 0;
        redobutton.disabled = at >= history.length - 1;
        dirtyflag.hidden = !isDirty();
        repaint();
    };

    const go = (index, message) => {
        at = index;
        writeForm(form, JSON.parse(history[at]));
        changed(false);
        status(message);
    };
    const undo = () => {
        if (at > 0) {
            go(at - 1, str.undone);
        }
    };
    const redo = () => {
        if (at < history.length - 1) {
            go(at + 1, str.redone);
        }
    };
    undobutton.addEventListener('click', undo);
    redobutton.addEventListener('click', redo);
    root.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey) || e.altKey) {
            return;
        }
        const keyname = e.key.toLowerCase();
        if (keyname === 'z' && !e.shiftKey) {
            e.preventDefault();
            undo();
        } else if (keyname === 'y' || (keyname === 'z' && e.shiftKey)) {
            e.preventDefault();
            redo();
        }
    });

    // Surprise me: a random choice for every part but the skin tone, with fewer hats, beards,
    // glasses, marks and make-up (SURPRISE_PLAIN) and costumes than plain chance would give.
    const valuesOf = (name) => Array.from(form.querySelectorAll(`input[type="radio"][name="${name}"]`)).map((r) => r.value);
    const pick = (list) => list[Math.floor(Math.random() * list.length)];
    const surprise = () => {
        const design = readForm(form, config.defaults);
        CHOICES.forEach((key) => {
            const list = valuesOf(key);
            if (KEEP_ON_SURPRISE.indexOf(key) >= 0 || !list.length) {
                return;
            }
            if (key === 'outfit') {
                // A costume hides most of the other choices: it is chosen on purpose, not by chance.
                design[key] = 'none';
            } else if (SURPRISE_PLAIN[key] && list.indexOf(SURPRISE_PLAIN[key][0]) >= 0) {
                const [plain, chance] = SURPRISE_PLAIN[key];
                design[key] = Math.random() < chance ? plain : pick(list.filter((value) => value !== plain));
            } else {
                design[key] = pick(list);
            }
        });
        design.ears = Math.random() < 0.5;
        design.glasses = design.eyewear ? design.eyewear !== 'none' : Math.random() < 0.25;
        writeForm(form, design);
        changed(true);
        // A new look is seen whole.
        if (setZoom) {
            setZoom.whole();
        }
        status(str.surprisedone);
    };
    surprisebuttons.forEach((button) => button.addEventListener('click', () => {
        surprise();
        coach.changed(currentTab());
    }));

    presetbuttons.forEach((button) => {
        button.addEventListener('click', async() => {
            writeForm(form, Object.assign(readForm(form, config.defaults), presetOf(button)));
            changed(true);
            coach.changed(currentTab());
            if (setZoom) {
                setZoom.whole();
            }
            const name = button.querySelector('.local-vravatar-tile-name');
            status(await getString('presetdone', 'local_vravatar', name ? name.textContent.trim() : ''));
        });
    });

    form.addEventListener('change', () => {
        changed(true);
        coach.changed(currentTab());
    });

    resetbutton.hidden = false;
    // Reset is one step that Undo takes back, so a slip loses nothing.
    resetbutton.addEventListener('click', () => {
        writeForm(form, config.defaults);
        changed(true);
        status(str.resetundo);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const savepending = new Pending('local_vravatar/designer:save');
        savebutton.disabled = true;
        status(str.saving);
        // A native promise: core/ajax gives a jQuery one, which has no finally().
        Promise.resolve(Ajax.call([{
            methodname: 'local_vravatar_save_design',
            args: {design: JSON.stringify(readForm(form, config.defaults))},
        }])[0]).then((result) => {
            writeForm(form, result.design);
            saved = JSON.stringify(readForm(form, config.defaults));
            changed(true);
            removeform.hidden = false;
            if (nodesign) {
                nodesign.hidden = true;
            }
            status(str.saved);
            coach.saved();
            return result;
        }).catch((error) => {
            status('');
            Notification.exception(error);
        }).finally(() => {
            savebutton.disabled = false;
            savepending.resolve();
        });
    });

    // Ask before leaving with unsaved changes.
    window.addEventListener('beforeunload', (e) => {
        if (isDirty()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    surprisebuttons.concat([undobutton, redobutton]).forEach((button) => {
        button.hidden = false;
    });
    paintFollowers(readForm(form, config.defaults));
    nameChosen();
    repaint();
    if (tilepainter) {
        tilepainter.presets(readForm(form, config.defaults),
            presetbuttons.map((button) => [button.querySelector('[data-region="presetpic"]'), presetOf(button)]));
        // On the first load the other tabs get their pictures too, after the looks, one at a time.
        const active = tabs ? tabs.active() : null;
        if (active) {
            panels.filter((panel) => panel !== active).forEach((panel) => paintPanel(panel, false));
        }
    }

    pending.resolve();
};
