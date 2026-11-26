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
 * Hand styles for the designer's preview, while VR room's avatar builder does not draw them yet.
 *
 * Each style is the same outline as its tile picture (pix/hands/<style>.svg, a 48 x 48 drawing,
 * y down), made into a thin 3D shape: palm towards the viewer, fingers up. A cuff, where the style
 * has one, takes the accent colour, and the robot hand is metal. When the builder knows hand styles
 * (its CHOICES list has "hands"), the designer leaves the hands to it.
 *
 * @module     local_vravatar/handmodels
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Metres per unit of the 48 x 48 drawing (a hand about 15 cm long). */
const UNIT = 0.0036;

/** Thickness of a hand (m). */
const DEPTH = 0.022;

/**
 * The outlines, in the units of the drawings: ['r', x, y, w, h, radius, rotation?, cx?, cy?] a
 * rounded rectangle (rotation in degrees about cx, cy, clockwise on screen), ['c', cx, cy, r] a
 * circle, ['e', cx, cy, rx, ry, rotation?] an ellipse. A part ending in true is the cuff.
 */
const OUTLINES = {
    sphere: [['c', 24, 25, 15]],
    mitten: [['r', 15, 6, 20, 32, 10], ['r', 5, 18, 10, 17, 5, -25, 10, 26], ['r', 15, 30, 20, 13, 3]],
    cartoon: [
        ['r', 13, 20, 24, 18, 8], ['r', 13.5, 6, 6, 20, 3], ['r', 20.5, 4, 6, 22, 3], ['r', 27.5, 7, 6, 19, 3],
        ['r', 5, 19, 6.5, 15, 3.25, -35, 8, 26], ['r', 15, 36, 20, 8, 4, 0, 0, 0, true],
    ],
    glove: [
        ['r', 14, 19, 21, 17, 5], ['r', 14, 6, 4.6, 17, 2.3], ['r', 19.4, 3.5, 4.6, 19, 2.3], ['r', 24.8, 4.5, 4.6, 18, 2.3],
        ['r', 30.2, 8, 4.4, 15, 2.2], ['r', 6, 18, 5, 14, 2.5, -38, 8.5, 25], ['r', 12, 35, 25, 9, 2, 0, 0, 0, true],
    ],
    robot: [
        ['r', 13, 20, 22, 16, 2], ['r', 14, 12, 5, 7, 0.5], ['r', 14, 5, 5, 6, 0.5], ['r', 21.5, 11, 5, 8, 0.5],
        ['r', 21.5, 3, 5, 7, 0.5], ['r', 29, 12, 5, 7, 0.5], ['r', 29, 5, 5, 6, 0.5], ['r', 5, 21, 7, 5, 0.5],
        ['r', 5, 27.5, 7, 5, 0.5], ['r', 16, 37, 16, 7, 1, 0, 0, 0, true],
    ],
    paw: [['e', 24, 31, 12, 10], ['e', 10, 19, 4.5, 6, -25], ['e', 19, 11, 4.5, 6.5], ['e', 29, 11, 4.5, 6.5],
        ['e', 38, 19, 4.5, 6, 25]],
};

/** The styles whose cuff takes the accent colour. */
const CUFFED = ['cartoon', 'glove', 'robot'];

/**
 * Turn a point of a drawing about a centre.
 *
 * @param {number} x X.
 * @param {number} y Y.
 * @param {number} deg Degrees, clockwise on screen.
 * @param {number} cx Centre x.
 * @param {number} cy Centre y.
 * @returns {number[]} [x, y]
 */
const turn = (x, y, deg, cx, cy) => {
    const a = deg * Math.PI / 180;
    const dx = x - cx;
    const dy = y - cy;
    return [cx + dx * Math.cos(a) - dy * Math.sin(a), cy + dx * Math.sin(a) + dy * Math.cos(a)];
};

/**
 * The points round one part, in the units of the drawing.
 *
 * @param {Array} part A part of an outline.
 * @returns {number[][]} Points.
 */
const partPoints = (part) => {
    const points = [];
    if (part[0] === 'c' || part[0] === 'e') {
        const [, cx, cy, rx] = part;
        const ry = part[0] === 'c' ? rx : part[4];
        const rot = part[0] === 'e' ? (part[5] || 0) : 0;
        for (let i = 0; i < 32; i++) {
            const t = i / 32 * Math.PI * 2;
            points.push(turn(cx + rx * Math.cos(t), cy + ry * Math.sin(t), rot, cx, cy));
        }
        return points;
    }
    const [, x, y, w, h] = part;
    const r = Math.min(part[5] || 0, w / 2, h / 2);
    const rot = part[6] || 0;
    const corners = [[x + w - r, y + r, -90], [x + w - r, y + h - r, 0], [x + r, y + h - r, 90], [x + r, y + r, 180]];
    corners.forEach(([cx, cy, start]) => {
        for (let i = 0; i <= 4; i++) {
            const t = (start + i * 22.5) * Math.PI / 180;
            points.push([cx + r * Math.cos(t), cy + r * Math.sin(t)]);
        }
    });
    return rot ? points.map(([px, py]) => turn(px, py, rot, part[7], part[8])) : points;
};

/**
 * A THREE.Shape from points of the drawing, in metres, y up, centred on the drawing's middle.
 *
 * @param {Object} THREE three.js.
 * @param {number[][]} points Points.
 * @returns {Object} THREE.Shape
 */
const shapeOf = (THREE, points) => new THREE.Shape(points.map(([x, y]) => new THREE.Vector2((x - 24) * UNIT, (24 - y) * UNIT)));

/**
 * Build one hand.
 *
 * @param {Object} THREE three.js.
 * @param {string} style Hand style.
 * @param {string} colour CSS colour of the hand.
 * @param {string} accent CSS colour of the cuff.
 * @returns {Object} {group (THREE.Group), dispose()}
 */
export const buildHand = (THREE, style, colour, accent) => {
    const outline = OUTLINES[style] || OUTLINES.sphere;
    const group = new THREE.Group();
    group.name = 'local-vravatar-hand';
    const made = [];
    const metal = style === 'robot';
    const add = (geometry, hex) => {
        const material = new THREE.MeshStandardMaterial({color: new THREE.Color(hex), roughness: metal ? 0.35 : 0.65,
            metalness: metal ? 0.6 : 0});
        const mesh = new THREE.Mesh(geometry, material);
        group.add(mesh);
        made.push(geometry, material);
    };
    if (style === 'sphere') {
        add(new THREE.SphereGeometry(0.045, 20, 14), colour);
    } else {
        const settings = {depth: DEPTH, bevelEnabled: true, bevelThickness: 0.005, bevelSize: 0.003, bevelSegments: 2,
            curveSegments: 6};
        const cuffed = CUFFED.indexOf(style) >= 0;
        const main = outline.filter((part) => !(cuffed && part[part.length - 1] === true));
        const cuff = outline.filter((part) => cuffed && part[part.length - 1] === true);
        const geometry = new THREE.ExtrudeGeometry(main.map((part) => shapeOf(THREE, partPoints(part))), settings);
        geometry.translate(0, 0, -DEPTH / 2);
        add(geometry, colour);
        if (cuff.length) {
            const cuffgeometry = new THREE.ExtrudeGeometry(cuff.map((part) => shapeOf(THREE, partPoints(part))),
                Object.assign({}, settings, {depth: DEPTH * 1.3}));
            cuffgeometry.translate(0, 0, -DEPTH * 0.65);
            add(cuffgeometry, accent);
        }
    }
    return {
        group,
        dispose: () => made.forEach((thing) => thing.dispose()),
    };
};
