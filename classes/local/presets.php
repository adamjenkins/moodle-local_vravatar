<?php
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

namespace local_vravatar\local;

/**
 * Ready-made looks the designer offers as a starting point ("Start from a look").
 *
 * Each preset is a partial "vrroom-avatar/1" document: the keys it does not set keep the user's
 * current choice (their skin tone and profile picture choice are never changed by a preset). Every
 * preset is cleaned against the allowlist like any other design, so a preset can only hold values a
 * user could pick by hand.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class presets {
    /**
     * @var array id => the keys the preset sets (every key but the skin tone and the picture). Names
     *     are the lang strings preset_<id>. The looks span ages (a young teen with braces and freckles
     *     to an older scientist with white hair and gentle lines) and hair types, and none is tied to
     *     a gender. The people have the face drawn on the skin and cartoon hands in the user's own
     *     skin tone; the robot keeps the face screen and robot hands. Three wear a costume the avatar
     *     builder makes in code (explorer, lab coat, athlete's kit).
     */
    private const PRESETS = [
        'explorer' => [
            'body' => 'broad', 'head' => 'long', 'color' => 'green', 'accent' => 'brown',
            'hair' => 'locs', 'haircolor' => 'black', 'hairgrey' => 'temples',
            'eyes' => 'smiling', 'eyecolor' => 'darkbrown', 'eyespace' => 'normal', 'brows' => 'straight', 'browcolor' => 'hair',
            'nose' => 'wide', 'mouth' => 'grin', 'lips' => 'none', 'ears' => true, 'earshape' => 'classic',
            'freckles' => 'none', 'cheeks' => 'sunkissed', 'marks' => 'none', 'lines' => 'crow', 'makeup' => 'none',
            'beard' => 'stubble', 'beardcolor' => 'greying', 'hat' => 'sunhat', 'eyewear' => 'none', 'eyewearcolor' => 'dark',
            'glasses' => false, 'outfit' => 'explorer', 'hands' => 'cartoon', 'handcolor' => 'skin', 'face' => 'drawn',
        ],
        'scientist' => [
            'body' => 'slim', 'head' => 'oval', 'color' => 'white', 'accent' => 'teal',
            'hair' => 'lowbun', 'haircolor' => 'white', 'hairgrey' => 'none',
            'eyes' => 'hooded', 'eyecolor' => 'bluegrey', 'eyespace' => 'close', 'brows' => 'sparse', 'browcolor' => 'grey',
            'nose' => 'aquiline', 'mouth' => 'soft', 'lips' => 'none', 'ears' => true, 'earshape' => 'lobe',
            'freckles' => 'none', 'cheeks' => 'none', 'marks' => 'molecheek', 'lines' => 'gentle', 'makeup' => 'none',
            'beard' => 'none', 'beardcolor' => 'hair', 'hat' => 'none', 'eyewear' => 'round', 'eyewearcolor' => 'gold',
            'glasses' => true, 'outfit' => 'labcoat', 'hands' => 'cartoon', 'handcolor' => 'skin', 'face' => 'drawn',
        ],
        'artist' => [
            'body' => 'capsule', 'head' => 'heart', 'color' => 'purple', 'accent' => 'yellow',
            'hair' => 'undercut', 'haircolor' => 'pink', 'hairgrey' => 'none',
            'eyes' => 'large', 'eyecolor' => 'green', 'eyespace' => 'normal', 'brows' => 'slit', 'browcolor' => 'black',
            'nose' => 'button', 'mouth' => 'smirk', 'lips' => 'none', 'ears' => true, 'earshape' => 'classic',
            'freckles' => 'nose', 'cheeks' => 'none', 'marks' => 'star', 'lines' => 'none', 'makeup' => 'graphic',
            'beard' => 'none', 'beardcolor' => 'hair', 'hat' => 'beret', 'eyewear' => 'none', 'eyewearcolor' => 'dark',
            'glasses' => false, 'outfit' => 'none', 'hands' => 'cartoon', 'handcolor' => 'skin', 'face' => 'drawn',
        ],
        'athlete' => [
            'body' => 'broad', 'head' => 'angular', 'color' => 'red', 'accent' => 'white',
            'hair' => 'cornrows', 'haircolor' => 'black', 'hairgrey' => 'none',
            'eyes' => 'bright', 'eyecolor' => 'brown', 'eyespace' => 'normal', 'brows' => 'bold', 'browcolor' => 'hair',
            'nose' => 'straight', 'mouth' => 'teeth', 'lips' => 'none', 'ears' => true, 'earshape' => 'classic',
            'freckles' => 'none', 'cheeks' => 'none', 'marks' => 'none', 'lines' => 'none', 'makeup' => 'none',
            'beard' => 'none', 'beardcolor' => 'hair', 'hat' => 'cap', 'eyewear' => 'none', 'eyewearcolor' => 'dark',
            'glasses' => false, 'outfit' => 'athlete', 'hands' => 'cartoon', 'handcolor' => 'skin', 'face' => 'drawn',
        ],
        'dreamer' => [
            'body' => 'slim', 'head' => 'round', 'color' => 'indigo', 'accent' => 'pink',
            'hair' => 'spacebuns', 'haircolor' => 'blue', 'hairgrey' => 'none',
            'eyes' => 'doe', 'eyecolor' => 'violet', 'eyespace' => 'wide', 'brows' => 'rounded', 'browcolor' => 'hair',
            'nose' => 'snub', 'mouth' => 'braces', 'lips' => 'none', 'ears' => false, 'earshape' => 'classic',
            'freckles' => 'few', 'cheeks' => 'stars', 'marks' => 'none', 'lines' => 'none', 'makeup' => 'none',
            'beard' => 'none', 'beardcolor' => 'hair', 'hat' => 'none', 'eyewear' => 'none', 'eyewearcolor' => 'dark',
            'glasses' => false, 'outfit' => 'none', 'hands' => 'cartoon', 'handcolor' => 'skin', 'face' => 'drawn',
        ],
        'robot' => [
            'body' => 'capsule', 'head' => 'rectangle', 'color' => 'grey', 'accent' => 'cyan',
            'hair' => 'none', 'haircolor' => 'grey', 'hairgrey' => 'none',
            'eyes' => 'dots', 'eyecolor' => 'teal', 'eyespace' => 'normal', 'brows' => 'none', 'browcolor' => 'hair',
            'nose' => 'none', 'mouth' => 'neutral', 'lips' => 'none', 'ears' => false, 'earshape' => 'classic',
            'freckles' => 'none', 'cheeks' => 'none', 'marks' => 'none', 'lines' => 'none', 'makeup' => 'none',
            'beard' => 'none', 'beardcolor' => 'hair', 'hat' => 'none', 'eyewear' => 'none', 'eyewearcolor' => 'dark',
            'glasses' => false, 'outfit' => 'none', 'hands' => 'robot', 'handcolor' => 'grey', 'face' => 'plate',
        ],
    ];

    /**
     * The presets, each as the keys it sets, cleaned: only allowed values are kept.
     *
     * @return array id => partial document
     */
    public static function all(): array {
        $all = [];
        $choices = design::choices();
        foreach (self::PRESETS as $id => $keys) {
            $preset = [];
            foreach ($keys as $key => $value) {
                if (in_array($key, design::FLAGS, true)) {
                    $preset[$key] = (bool)$value;
                } else if (isset($choices[$key]) && in_array($value, $choices[$key], true)) {
                    $preset[$key] = $value;
                }
            }
            $all[$id] = $preset;
        }
        return $all;
    }

    /**
     * The presets for the designer page: id, name and the keys each sets.
     *
     * @return array list of {id, name, design}
     */
    public static function for_page(): array {
        $list = [];
        foreach (self::all() as $id => $preset) {
            $list[] = [
                'id' => $id,
                'name' => get_string('preset_' . $id, 'local_vravatar'),
                'design' => $preset,
            ];
        }
        return $list;
    }
}
