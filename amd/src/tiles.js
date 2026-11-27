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
 * Pictures for the designer's tiles, drawn by VR room's own avatar builder.
 *
 * One small offscreen renderer draws the user's own avatar with one choice changed (a hairstyle, a
 * nose, a body shape ...) and frames the part that choice changes: the head for a face choice, the
 * whole avatar for a body choice or a preset. Each picture becomes a plain image in its tile. A
 * picture is drawn again only when the rest of the avatar changed since (a new hair colour redraws
 * the hairstyles, not the noses), and the tab in view is drawn first, one picture at a time between
 * frames, so the page stays responsive.
 *
 * The pictures are decoration (their tiles are labelled by name), so when the builder or WebGL is
 * missing the tiles simply show their names.
 *
 * @module     local_vravatar/tiles
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as THREE from 'vrroomruntime_builtin/three';
import * as AvatarModel from 'vrroomruntime_builtin/live/avatarmodel';
import * as Wardrobe from 'vrroomruntime_builtin/live/avatarwardrobe';
import Log from 'core/log';

/** Size of a picture (px, before the device pixel ratio). */
const SIZE = 112;

/** Size of a ready-made look's picture (px). */
const LOOKSIZE = 144;

/** Field of view of the picture camera (degrees). */
const FOV = 26;

/** The keys a tile picture never shows: models need loading, the picture is the user's own. */
const NEUTRAL = {outfit: 'none', picture: false};

/**
 * What a picture shows of a design: a costume made in code is kept (it needs no model), a costume
 * model and the profile picture are not.
 *
 * @param {Object} design The design.
 * @returns {Object} A new design.
 */
const neutral = (design) => {
    const shown = Object.assign({}, design, NEUTRAL);
    if (Array.isArray(Wardrobe.COSTUMES) && Wardrobe.COSTUMES.indexOf(design.outfit) >= 0) {
        shown.outfit = design.outfit;
    }
    return shown;
};

/** The keys whose tiles are drawn without eyewear: what they change is round the eyes. */
const EYE_KEYS = ['eyes', 'eyecolor', 'eyespace', 'brows', 'browcolor', 'makeup', 'lines'];

/**
 * The design a tile shows: the user's design with the tile's own choice, without what would hide
 * that choice (a hat hides the hair and the brows, so only the hat tiles wear one).
 *
 * @param {Object} design The user's current design.
 * @param {string} key The tile's key.
 * @param {string} value The tile's value ("1" for a checkbox).
 * @returns {Object} The design to draw.
 */
const tileDesign = (design, key, value) => {
    const shown = neutral(design);
    if (key !== 'hat') {
        shown.hat = 'none';
    } else if (Array.isArray(AvatarModel.MODEL_HATS) && AvatarModel.MODEL_HATS.indexOf(value) >= 0) {
        shown.hat = 'none';
    }
    // Glasses (sunglasses above all) would hide the choices round the eyes.
    if (EYE_KEYS.indexOf(key) >= 0) {
        shown.eyewear = 'none';
    }
    // Ear shapes show only with the ears on, and not under hair that covers them.
    if (key === 'earshape') {
        shown.ears = true;
        if (typeof AvatarModel.hairCoversEars === 'function' && AvatarModel.hairCoversEars(shown.hair)) {
            shown.hair = 'short';
        }
    }
    shown[key] = value === '1' ? true : value;
    if (typeof shown.eyewear === 'string') {
        shown.glasses = shown.eyewear !== 'none';
    }
    return shown;
};

/**
 * The part of the design a tile depends on: everything but its own key (and what it never shows).
 *
 * @param {Object} design The user's current design.
 * @param {string} key The tile's key.
 * @returns {string} A signature: equal signatures give the same picture.
 */
const signature = (design, key) => {
    const rest = tileDesign(design, key, '');
    delete rest[key];
    return JSON.stringify(rest);
};

/**
 * A box round the parts of an object that are drawn (hidden parts and the name tag do not count).
 *
 * @param {Object} object THREE.Object3D, world matrices up to date.
 * @returns {Object} THREE.Box3
 */
const visibleBox = (object) => {
    const box = new THREE.Box3();
    const part = new THREE.Box3();
    const visit = (node) => {
        if (!node.visible || node.name === 'label' || node.name === 'ring') {
            return;
        }
        const geometry = node.geometry;
        if (geometry && (node.isMesh || node.isLine || node.isPoints)) {
            if (!geometry.boundingBox) {
                geometry.computeBoundingBox();
            }
            part.copy(geometry.boundingBox).applyMatrix4(node.matrixWorld);
            box.union(part);
        }
        node.children.forEach(visit);
    };
    visit(object);
    return box;
};

/**
 * Create the tile painter.
 *
 * @param {Object} config Page config ({seed}).
 * @returns {Object|null} {paint(design, elements), presets(design, buttons)} or null when it cannot draw.
 */
export const createTiles = (config) => {
    if (typeof AvatarModel.createAvatar !== 'function') {
        return null;
    }
    const canvas = document.createElement('canvas');
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({canvas, antialias: true, alpha: true, preserveDrawingBuffer: true});
    } catch (e) {
        Log.debug('local_vravatar: no WebGL for the tile pictures: ' + e);
        return null;
    }
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    renderer.setPixelRatio(ratio);
    renderer.setSize(SIZE, SIZE, false);
    if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) {
        renderer.outputColorSpace = THREE.SRGBColorSpace;
    }
    const scene = new THREE.Scene();
    scene.add(new THREE.HemisphereLight(0xffffff, 0xe6dccf, 1.15));
    const key = new THREE.DirectionalLight(0xfff8f0, 1.6);
    key.position.set(0.8, 3.4, -2.6);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.6);
    fill.position.set(-2.6, 1.8, -1.2);
    scene.add(fill);
    const camera = new THREE.PerspectiveCamera(FOV, 1, 0.05, 50);
    let avatar = null;

    let drawsize = SIZE;

    /**
     * Draw one design and give it back as an image address.
     *
     * @param {Object} design The design.
     * @param {string} framing "face" (the head), "bust" (head and shoulders) or "whole".
     * @param {number} size Picture size (px).
     * @returns {string} A data: URL.
     */
    const draw = (design, framing, size = SIZE) => {
        if (size !== drawsize) {
            renderer.setSize(size, size, false);
            drawsize = size;
        }
        if (!avatar) {
            avatar = AvatarModel.createAvatar(THREE, design, {name: '', pictureurl: '', seed: config.seed, assets: {}});
            scene.add(avatar.group);
        } else {
            avatar.setAppearance(design, '', {});
        }
        // Hands only in the whole view, by the hips, so the hand styles show on presets.
        const h = AvatarModel.STANDING_HEAD_HEIGHT || 1.6;
        if (typeof avatar.setPose === 'function') {
            avatar.setPose(framing === 'whole' ?
                {h: [0, h, 0, 0, 0, 0, 1], l: [0.3, h - 0.72, -0.05, 0, 0, 0, 1], r: [-0.3, h - 0.72, -0.05, 0, 0, 0, 1]} :
                {h: [0, h, 0, 0, 0, 0, 1], l: null, r: null}, framing === 'whole' ? 'vr' : '3d');
        }
        const label = avatar.group.getObjectByName('label');
        if (label) {
            label.visible = false;
        }
        const ring = avatar.group.getObjectByName('ring');
        if (ring) {
            ring.visible = false;
        }
        avatar.group.updateMatrixWorld(true);
        const head = avatar.group.getObjectByName('head');
        const closeup = framing !== 'whole' && head && head.visible;
        const box = closeup ? visibleBox(head) : visibleBox(avatar.group);
        if (framing === 'bust' && !box.isEmpty()) {
            // Down to the chest.
            box.min.y -= 0.42;
            box.max.y += 0.03;
        }
        if (!box.isEmpty()) {
            const middle = (box.min.y + box.max.y) / 2;
            const size = box.getSize(new THREE.Vector3());
            const extent = Math.max(size.y, 2 * Math.max(Math.abs(box.min.x), Math.abs(box.max.x))) * 1.12;
            const distance = (extent / 2) / Math.tan(THREE.MathUtils.degToRad(FOV / 2)) + size.z / 2;
            // A little from the side and above, so shapes read as 3D.
            camera.position.set(-distance * 0.28, middle + extent * 0.08, -distance * 0.96);
            camera.lookAt(0, middle, 0);
        }
        renderer.render(scene, camera);
        return canvas.toDataURL('image/png');
    };

    /**
     * Put a picture into a tile.
     *
     * @param {HTMLElement} holder The tile's picture holder.
     * @param {string} url The picture.
     * @param {number} size Its size (px).
     */
    const show = (holder, url, size = SIZE) => {
        let img = holder.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = '';
            img.width = size;
            img.height = size;
            holder.appendChild(img);
        }
        img.src = url;
    };

    let queue = [];
    let running = false;
    const run = () => {
        if (running) {
            return;
        }
        running = true;
        const step = () => {
            const job = queue.shift();
            if (!job) {
                running = false;
                return;
            }
            try {
                job();
            } catch (e) {
                Log.debug('local_vravatar: tile picture: ' + e);
            }
            window.setTimeout(step, 0);
        };
        window.requestAnimationFrame(step);
    };

    return {
        /**
         * Draw the pictures of some tiles that are out of date, in order.
         *
         * @param {Object} design The user's current design.
         * @param {HTMLElement[]} holders Picture holders (data-render, data-key, data-value).
         * @param {boolean} first Whether these go before the pictures already waiting.
         */
        paint(design, holders, first) {
            const jobs = [];
            holders.forEach((holder) => {
                const sig = signature(design, holder.dataset.key);
                if (holder.dataset.sig === sig || holder.dataset.want === sig) {
                    return;
                }
                // A later change supersedes a picture still waiting.
                holder.dataset.want = sig;
                jobs.push(() => {
                    if (holder.dataset.want !== sig) {
                        return;
                    }
                    show(holder, draw(tileDesign(design, holder.dataset.key, holder.dataset.value), holder.dataset.render));
                    holder.dataset.sig = sig;
                });
            });
            queue = first ? jobs.concat(queue) : queue.concat(jobs);
            run();
        },

        /**
         * Draw the presets, each on the user's own avatar (their skin tone kept).
         *
         * @param {Object} design The user's current design.
         * @param {Array} presets List of [holder, preset keys].
         */
        presets(design, presets) {
            presets.forEach(([holder, keys]) => {
                queue.push(() => {
                    const shown = neutral(Object.assign({}, design, keys));
                    show(holder, draw(shown, 'bust', LOOKSIZE), LOOKSIZE);
                });
            });
            run();
        },
    };
};
