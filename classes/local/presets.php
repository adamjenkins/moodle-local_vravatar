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
    /** @var array id => the keys the preset sets. Names are the lang strings preset_<id>. */
    private const PRESETS = [
        'explorer' => [
            'body' => 'broad', 'head' => 'round', 'color' => 'green', 'accent' => 'brown', 'hair' => 'short',
            'haircolor' => 'brown', 'eyes' => 'round', 'brows' => 'thick', 'nose' => 'small', 'beard' => 'stubble',
            'hat' => 'sunhat', 'glasses' => false, 'outfit' => 'none', 'hands' => 'glove', 'handcolor' => 'brown',
        ],
        'scientist' => [
            'body' => 'slim', 'head' => 'oval', 'color' => 'white', 'accent' => 'teal', 'hair' => 'bun',
            'haircolor' => 'black', 'eyes' => 'almond', 'brows' => 'arched', 'nose' => 'small', 'beard' => 'none',
            'hat' => 'none', 'glasses' => true, 'outfit' => 'none', 'hands' => 'glove', 'handcolor' => 'cyan',
        ],
        'artist' => [
            'body' => 'capsule', 'head' => 'heart', 'color' => 'purple', 'accent' => 'yellow', 'hair' => 'curly',
            'haircolor' => 'auburn', 'eyes' => 'round', 'brows' => 'thin', 'nose' => 'round', 'beard' => 'none',
            'hat' => 'beanie', 'glasses' => false, 'outfit' => 'none', 'hands' => 'cartoon', 'handcolor' => 'skin',
        ],
        'athlete' => [
            'body' => 'broad', 'head' => 'square', 'color' => 'red', 'accent' => 'white', 'hair' => 'buzz',
            'haircolor' => 'black', 'eyes' => 'dots', 'brows' => 'thick', 'nose' => 'small', 'beard' => 'none',
            'hat' => 'cap', 'glasses' => false, 'outfit' => 'none', 'hands' => 'mitten', 'handcolor' => 'red',
        ],
        'dreamer' => [
            'body' => 'slim', 'head' => 'round', 'color' => 'indigo', 'accent' => 'pink', 'hair' => 'long',
            'haircolor' => 'pink', 'eyes' => 'sleepy', 'brows' => 'thin', 'nose' => 'none', 'beard' => 'none',
            'hat' => 'none', 'glasses' => false, 'outfit' => 'none', 'hands' => 'cartoon', 'handcolor' => 'skin',
        ],
        'robot' => [
            'body' => 'capsule', 'head' => 'square', 'color' => 'grey', 'accent' => 'cyan', 'hair' => 'none',
            'haircolor' => 'grey', 'eyes' => 'dots', 'brows' => 'none', 'nose' => 'none', 'beard' => 'none',
            'hat' => 'none', 'glasses' => false, 'outfit' => 'none', 'hands' => 'robot', 'handcolor' => 'grey',
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
