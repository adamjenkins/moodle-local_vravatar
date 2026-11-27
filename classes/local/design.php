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
 * A user's avatar design: the "vrroom-avatar/1" document, its allowlist and its storage.
 *
 * The design is stored as one user preference holding a small JSON document. Every document is
 * cleaned against the allowlist on save and again on read: unknown keys and values are dropped and
 * missing ones take the default, so only names from the fixed lists below ever leave the server.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class design {
    /** @var string Schema name of the document. */
    public const SCHEMA = 'vrroom-avatar/1';

    /** @var string The user preference holding the document. */
    public const PREFERENCE = 'local_vravatar_design';

    /** @var int Largest document accepted (bytes of JSON). */
    public const MAXBYTES = 2048;

    /** @var int Size of the profile picture given to live rooms (px). */
    public const PICTURESIZE = 100;

    /** @var string[] The body and accent colours, in palette order. */
    public const COLOURS = [
        'red', 'orange', 'yellow', 'green', 'teal', 'cyan',
        'blue', 'indigo', 'purple', 'pink', 'brown', 'grey', 'black', 'white',
    ];

    /** @var string[] The hair colours (beards use the hair colour too). */
    public const HAIRCOLOURS = ['black', 'brown', 'blonde', 'auburn', 'grey', 'white', 'red', 'pink', 'blue', 'purple'];

    /**
     * The skin tones, light to deep. "match" gives the head and hands the body colour (the look of
     * avatars before skin tones existed); the others are natural skin colours chosen by the avatar
     * builder.
     *
     * @var string[]
     */
    public const SKINS = ['match', 'porcelain', 'fair', 'light', 'medium', 'tan', 'brown', 'dark', 'deep'];

    /**
     * Swatch colours for the designer form, by name. Only a visual cue next to the colour's name:
     * the 3D colours are chosen by vrroomruntime_builtin/live/avatarmodel, and the designer page
     * repaints the swatches from it when that module lists them.
     *
     * @var string[]
     */
    public const SWATCHES = [
        'red' => '#d32f2f',
        'orange' => '#f57c00',
        'yellow' => '#fbc02d',
        'green' => '#388e3c',
        'teal' => '#00897b',
        'cyan' => '#0097a7',
        'blue' => '#1976d2',
        'indigo' => '#3949ab',
        'purple' => '#7b1fa2',
        'pink' => '#d81b60',
        'brown' => '#6d4c41',
        'grey' => '#757575',
        'black' => '#1b1b1d',
        'white' => '#f4f4f2',
    ];

    /** @var string[] Swatch colours of the hair colours. */
    public const HAIRSWATCHES = [
        'black' => '#1c1c1c',
        'brown' => '#5d3a1a',
        'blonde' => '#e0c068',
        'auburn' => '#8d3b1f',
        'grey' => '#9e9e9e',
        'white' => '#eeeeee',
        'red' => '#b8452a',
        'pink' => '#e78fb3',
        'blue' => '#3f6fd8',
        'purple' => '#7b4bb5',
    ];

    /**
     * Swatch colours of the skin tones, a visual cue only: the designer page repaints them from the
     * avatar builder's own list when that module gives one. "match" has no swatch (it is the body
     * colour).
     *
     * @var string[]
     */
    public const SKINSWATCHES = [
        'porcelain' => '#f9e4d8',
        'fair' => '#f1cfb6',
        'light' => '#e3b48f',
        'medium' => '#c99266',
        'tan' => '#b07646',
        'brown' => '#8a5636',
        'dark' => '#683f25',
        'deep' => '#472a19',
    ];

    /**
     * The hand styles, in the order the designer shows them. "sphere" is the look hands had before
     * hand styles existed (two balls), so it is the default. Live rooms draw them (VR room).
     *
     * @var string[]
     */
    public const HANDSTYLES = ['sphere', 'mitten', 'cartoon', 'glove', 'robot', 'paw'];

    /**
     * The hand colours: "skin" follows the skin tone (or the body colour when the skin is "match"),
     * the look before hand colours existed; any body colour makes the hands a pair of gloves.
     *
     * @return string[]
     */
    public static function handcolours(): array {
        return array_merge(['skin'], self::COLOURS);
    }

    /**
     * The keys drawn by the avatar builder's face, hair and headwear modules, and the head shapes: key => group =>
     * values, in the designer's order (the groups head the designer's tiles). Mirrors APPEARANCE of
     * vrroomruntime_builtin/live/avatarface, avatarhair and avatarheadwear and HEAD_GROUPS of avatarhead (VR room),
     * whose lists keep every value of the first versions. Printed by the wardrobe tools' print-choices.mjs (dev-docs);
     * print it again when a module's lists change.
     *
     * @var array
     */
    public const MODULE_KEYS = [
        // vrroomruntime_builtin/live/avatarhead.
        'head' => [
            'classic' => ['round', 'square', 'oval', 'heart'],
            'proportion' => ['long', 'wide', 'soft'],
            'jaw' => ['angular', 'rectangle', 'diamond', 'pear', 'chiselled'],
        ],
        // vrroomruntime_builtin/live/avatarface.
        'eyes' => [
            'cartoon' => ['dots', 'beads'],
            'classic' => ['round', 'almond', 'sleepy'],
            'open' => ['soft', 'wide', 'large', 'small', 'bright', 'doe', 'lashes'],
            'lids' => ['hooded', 'heavy', 'deepset', 'monolid', 'innerfold'],
            'tilt' => ['upturned', 'downturned', 'narrow', 'smiling'],
        ],
        'eyecolor' => [
            'classic' => ['classic'],
            'natural' => ['black', 'darkbrown', 'brown', 'lightbrown', 'amber', 'hazel', 'green', 'grey', 'bluegrey',
                'blue', 'lightblue'],
            'fun' => ['violet', 'teal', 'gold', 'red', 'odd'],
        ],
        'eyespace' => [
            'spacing' => ['closer', 'close', 'normal', 'wide', 'wider'],
        ],
        'brows' => [
            'classic' => ['none', 'thin', 'thick', 'arched'],
            'shape' => ['straight', 'soft', 'rounded', 'higharch', 'angular', 'tapered', 'slantup', 'slantdown'],
            'size' => ['short', 'long', 'bushy', 'bold', 'feathered', 'sparse'],
            'style' => ['slit', 'joined'],
        ],
        'browcolor' => [
            'hair' => ['hair'],
            'colour' => ['black', 'brown', 'blonde', 'auburn', 'grey', 'white', 'red', 'pink', 'blue', 'purple'],
        ],
        'nose' => [
            'classic' => ['none', 'small', 'round', 'pointed', 'button'],
            'profile' => ['snub', 'upturned', 'straight', 'aquiline', 'roman'],
            'size' => ['long', 'narrow', 'wide', 'broad', 'bulb'],
        ],
        'mouth' => [
            'smiles' => ['smile', 'soft', 'wide', 'grin', 'teeth', 'gap', 'braces'],
            'calm' => ['neutral', 'serious', 'smirk'],
            'lips' => ['full', 'thin', 'pout'],
            'fun' => ['cat', 'tongue', 'oh'],
        ],
        'lips' => [
            'none' => ['none'],
            'natural' => ['gloss', 'nude', 'peach', 'coral', 'pink', 'rose'],
            'bold' => ['red', 'berry', 'plum', 'brown', 'black'],
            'fun' => ['purple', 'blue', 'gold'],
        ],
        'earshape' => [
            'classic' => ['classic'],
            'size' => ['round', 'small', 'large', 'tall'],
            'shape' => ['flat', 'out', 'lobe', 'pointed'],
        ],
        'freckles' => [
            'none' => ['none'],
            'freckles' => ['few', 'light', 'nose', 'cheeks', 'tiny', 'many', 'dense', 'big'],
        ],
        'cheeks' => [
            'none' => ['none'],
            'blush' => ['pink', 'peach', 'coral', 'rose', 'berry', 'sunkissed', 'glow'],
            'shape' => ['dimples', 'hatch', 'hearts', 'stars'],
        ],
        'marks' => [
            'none' => ['none'],
            'moles' => ['mole', 'molecheek', 'beauty', 'chin', 'nosemole', 'cluster'],
            'skin' => ['birthmark', 'patches', 'scar', 'bindi'],
            'paint' => ['star', 'heart', 'whiskers', 'stripes'],
        ],
        'lines' => [
            'none' => ['none'],
            'single' => ['smile', 'crow', 'bags', 'forehead', 'frown', 'mouth'],
            'together' => ['laugh', 'crinkle', 'gentle', 'mature', 'deep'],
        ],
        'makeup' => [
            'none' => ['none'],
            'liner' => ['liner', 'wing', 'kohl', 'lashes', 'graphic'],
            'shadow' => ['smoky', 'bronze', 'gold', 'peach', 'pink', 'plum', 'blue', 'green', 'glitter'],
        ],
        // vrroomruntime_builtin/live/avatarhair.
        'hair' => [
            'none' => ['none'],
            'cropped' => ['buzz', 'crew', 'fade', 'curltop', 'mohawk'],
            'short' => ['short', 'sidepart', 'quiff', 'slicked', 'undercut', 'pixie'],
            'age' => ['receding', 'thinning', 'balding'],
            'medium' => ['bob', 'curlybob', 'bangs', 'shoulder', 'mullet', 'curly'],
            'long' => ['long', 'longwavy', 'longcurly', 'halfup'],
            'tied' => ['ponytail', 'pigtails', 'bun', 'lowbun', 'topknot', 'spacebuns'],
            'coils' => ['coily', 'afro', 'afropuff', 'highpuff', 'bantu'],
            'braids' => ['cornrows', 'braids', 'boxbraids', 'twists', 'locs'],
        ],
        'beard' => [
            'none' => ['none'],
            'stubble' => ['stubble', 'heavystubble'],
            'moustache' => ['moustache', 'pencil', 'chevron', 'handlebar', 'walrus', 'horseshoe'],
            'chin' => ['soulpatch', 'goatee', 'vandyke', 'circle'],
            'sides' => ['sideburns', 'chinstrap', 'muttonchops'],
            'beard' => ['boxed', 'full', 'long'],
        ],
        'beardcolor' => [
            'hair' => ['hair', 'greying'],
            'colour' => ['black', 'brown', 'blonde', 'auburn', 'grey', 'white', 'red', 'pink', 'blue', 'purple'],
        ],
        'hairgrey' => [
            'grey' => ['none', 'temples', 'salt'],
        ],
        // vrroomruntime_builtin/live/avatarheadwear.
        'hat' => [
            'none' => ['none'],
            'everyday' => ['cap', 'beanie', 'sunhat', 'bucket', 'flatcap', 'beret', 'bandana', 'headphones'],
            'smart' => ['fedora', 'bowler', 'tophat', 'cowboy'],
            'tradition' => ['hijab', 'turban', 'kufi', 'kippah', 'headwrap'],
            'work' => ['hardhat', 'firehelmet', 'pilotcap', 'policecap', 'security', 'chef', 'scrubcap', 'porter'],
            'celebration' => ['graduation', 'party', 'crown', 'flowercrown'],
            'costume' => ['wizard', 'tricorne', 'pirate'],
        ],
        'eyewear' => [
            'none' => ['none'],
            'glasses' => ['classic', 'round', 'rectangle', 'cateye', 'halfrim', 'thick', 'halfmoon'],
            'sun' => ['aviator', 'sunsquare', 'sunround', 'sunwrap', 'heart'],
            'sport' => ['safety', 'ski'],
            'costume' => ['monocle', 'eyepatch'],
        ],
        'eyewearcolor' => [
            'plain' => ['dark', 'black', 'white', 'clear'],
            'pattern' => ['tortoise'],
            'metal' => ['gold', 'silver', 'gunmetal'],
            'colour' => ['red', 'blue', 'green', 'pink', 'purple'],
        ],
    ];

    /**
     * The defaults of the keys the modules added: today's look (mirrors the modules' APPEARANCE defaults, as
     * avatarmodel's DEFAULT_APPEARANCE).
     *
     * @var string[]
     */
    public const MODULE_DEFAULTS = [
        'eyecolor' => 'classic',
        'eyespace' => 'normal',
        'browcolor' => 'hair',
        'mouth' => 'smile',
        'lips' => 'none',
        'earshape' => 'classic',
        'freckles' => 'none',
        'cheeks' => 'none',
        'marks' => 'none',
        'lines' => 'none',
        'makeup' => 'none',
        'beardcolor' => 'hair',
        'hairgrey' => 'none',
        'eyewear' => 'none',
        'eyewearcolor' => 'dark',
    ];

    /**
     * Swatch colours of the eye colours (mirrors EYE_COLOURS of vrroomruntime_builtin/live/avatarface). "classic" (a
     * plain dark pupil) and "odd" (two colours) are painted by the designer.
     *
     * @var string[]
     */
    public const EYESWATCHES = [
        'black' => '#2a1d17',
        'darkbrown' => '#432716',
        'brown' => '#6b4226',
        'lightbrown' => '#94663a',
        'amber' => '#b8862e',
        'hazel' => '#8a7f3e',
        'green' => '#4f8549',
        'grey' => '#8d989f',
        'bluegrey' => '#6a8aa3',
        'blue' => '#3d7bc4',
        'lightblue' => '#7db3e6',
        'violet' => '#8d5fcc',
        'teal' => '#27a59b',
        'gold' => '#dcaa22',
        'red' => '#c43b3d',
    ];

    /**
     * Swatch colours of the lip colours (mirrors LIP_COLOURS of vrroomruntime_builtin/live/avatarface). "none" and
     * "gloss" follow the skin.
     *
     * @var string[]
     */
    public const LIPSWATCHES = [
        'nude' => '#c48a78',
        'peach' => '#e5946f',
        'coral' => '#e8665a',
        'pink' => '#e2779d',
        'rose' => '#c2566e',
        'red' => '#c4232f',
        'berry' => '#8e2346',
        'plum' => '#6b2a4c',
        'brown' => '#7a4030',
        'black' => '#24191c',
        'purple' => '#7a3fb0',
        'blue' => '#3a68c8',
        'gold' => '#d2a43a',
    ];

    /**
     * Swatch colours of the eyewear frames (mirrors FRAME_COLOURS of vrroomruntime_builtin/live/avatarheadwear).
     *
     * @var string[]
     */
    public const FRAMESWATCHES = [
        'dark' => '#202326',
        'black' => '#0b0b0d',
        'white' => '#f1f1ee',
        'clear' => '#d6e2ea',
        'tortoise' => '#5a3418',
        'gold' => '#d6ad48',
        'silver' => '#c3c8cf',
        'gunmetal' => '#4a4f57',
        'red' => '#c62828',
        'blue' => '#1f5fbf',
        'green' => '#2e7d4f',
        'pink' => '#e77fa8',
        'purple' => '#7b52c0',
    ];


    /**
     * The costumes made in code by the avatar builder (no model file, so no asset): group => ids, in
     * the designer's order. Mirrors COSTUMES and GROUPS of vrroomruntime_builtin/live/avatarwardrobe
     * (VR room). Printed by the wardrobe tools' print-choices.mjs (dev-docs); print it again when the
     * module's list changes.
     *
     * @var array
     */
    public const WARDROBE = [
        'travel' => ['pilot', 'cabincrew', 'groundstaff'],
        'work' => ['security', 'police', 'firefighter', 'builder', 'farmer'],
        'service' => ['waiter', 'chef', 'barista', 'receptionist', 'porter', 'postal', 'shop', 'market', 'guide'],
        'health' => ['doctor', 'nurse', 'scrubs'],
        'science' => ['labcoat'],
        'school' => ['teacher', 'librarian', 'gown'],
        'everyday' => ['summer', 'hoodie', 'cardigan', 'wintercoat', 'dress', 'skirt'],
        'formal' => ['office', 'suit', 'tailcoat'],
        'traditional' => ['kilt', 'yukata', 'hanbok', 'sari', 'kurta', 'dashiki', 'qipao'],
        'sport' => ['athlete', 'tracksuit'],
        'fun' => ['astronaut', 'knight', 'wizard', 'detective', 'explorer', 'superhero', 'captain', 'deckhand'],
    ];

    /** @var string[] Keys of the document that are true or false. */
    public const FLAGS = ['ears', 'glasses', 'picture'];

    /**
     * The values of the first versions of the keys the modules now list (MODULE_KEYS): each stays allowed
     * even if a module's list leaves it out, as in avatarmodel's CHOICES.
     *
     * @var array
     */
    private const FIRST_CHOICES = [
        'head' => ['round', 'square', 'oval', 'heart'],
        'hair' => ['none', 'short', 'long', 'bun', 'curly', 'afro', 'ponytail', 'braids', 'buzz', 'bob', 'mohawk',
            'sidepart', 'pigtails', 'topknot'],
        'eyes' => ['dots', 'round', 'almond', 'sleepy'],
        'brows' => ['none', 'thin', 'thick', 'arched'],
        'nose' => ['none', 'small', 'round', 'pointed'],
        'beard' => ['none', 'stubble', 'moustache', 'goatee', 'full'],
        'hat' => ['none', 'cap', 'beanie', 'sunhat', 'pirate'],
    ];

    /**
     * The allowed values of every key that takes a name.
     *
     * "outfit" is "none", one of the costumes the avatar builder makes in code (WARDROBE), or the id
     * of one of the costume models this plugin ships whose kind is body or device
     * (costumes/costumes.json, see costumes::outfit_ids()). The hat "pirate" is a costume
     * model too (costumes::HATS). The face, hair and headwear keys and the head shapes are the
     * avatar builder's lists (MODULE_KEYS), with every value of the first versions.
     *
     * @return array key => list of names
     */
    public static function choices(): array {
        $choices = [
            'body' => ['capsule', 'slim', 'broad'],
            'skin' => self::SKINS,
            'color' => self::COLOURS,
            'accent' => self::COLOURS,
            'haircolor' => self::HAIRCOLOURS,
            'outfit' => array_merge(['none'], self::wardrobe_ids(), costumes::outfit_ids()),
            'hands' => self::HANDSTYLES,
            'handcolor' => self::handcolours(),
            // The value "plate" is the first version's face screen; "drawn" paints the face on the skin.
            'face' => ['plate', 'drawn'],
        ];
        foreach (self::MODULE_KEYS as $key => $groups) {
            $values = array_merge(...array_values($groups));
            $choices[$key] = array_values(array_unique(array_merge($values, self::FIRST_CHOICES[$key] ?? [])));
        }
        return $choices;
    }

    /**
     * The ids of the costumes made in code (WARDROBE), in the designer's order.
     *
     * @return string[]
     */
    public static function wardrobe_ids(): array {
        return array_merge(...array_values(self::WARDROBE));
    }

    /**
     * The group of each value of a key the modules list, for the designer's headings.
     *
     * @param string $key Document key.
     * @return array group => list of values, in the designer's order ([] for a key without groups)
     */
    public static function groups(string $key): array {
        return self::MODULE_KEYS[$key] ?? [];
    }

    /**
     * The default document. Every key added after the first version defaults to the look the avatar
     * had before it, so designs saved earlier look the same. The keys the modules added come after
     * the first versions' keys, before "picture".
     *
     * @return array
     */
    public static function defaults(): array {
        return array_merge([
            'schema' => self::SCHEMA,
            'body' => 'capsule',
            'head' => 'round',
            'skin' => 'match',
            'color' => 'blue',
            'accent' => 'grey',
            'hair' => 'none',
            'haircolor' => 'brown',
            'eyes' => 'dots',
            'brows' => 'none',
            'nose' => 'none',
            'ears' => false,
            'beard' => 'none',
            'glasses' => false,
            'hat' => 'none',
            'outfit' => 'none',
            'hands' => 'sphere',
            'handcolor' => 'skin',
            'face' => 'plate',
        ], self::MODULE_DEFAULTS, [
            // On by default (owner, 2026-10-10); the site setting allowpicture can turn it off.
            'picture' => true,
        ]);
    }

    /** @var string[] The body colours a standard look is picked from (the bright ones). */
    public const STANDARD_COLOURS = ['red', 'orange', 'yellow', 'green', 'teal', 'cyan', 'blue', 'indigo', 'purple', 'pink'];

    /** @var string[] The natural hair colours a standard look is picked from. */
    public const STANDARD_HAIRCOLOURS = ['brown', 'black', 'auburn', 'blonde'];

    /** @var string[] The natural eye colours a standard look is picked from. */
    public const STANDARD_EYECOLOURS = ['brown', 'darkbrown', 'hazel', 'green', 'blue', 'grey'];

    /**
     * The standard look of a person who has not saved an avatar: a friendly figure with the face
     * drawn on the head (round eyes, soft brows, a small nose, a smile) and short hair, in a body
     * colour that is always the same for that person (picked from their user id), with a white
     * accent and cartoon hands in their skin tone. The skin is a real skin tone, the hair and eyes
     * natural colours, each always the same for that person but picked by a different step through
     * the user ids than the colour, so a class shows the whole range, and the bright colour is on the
     * clothes only. No profile picture: they did not choose one. Saved designs never use this: a
     * stored document is cleaned against defaults(), so it keeps the look it had.
     *
     * @param int $userid User id.
     * @return array A clean document.
     */
    public static function standard(int $userid): array {
        $id = abs($userid);
        $colours = self::STANDARD_COLOURS;
        // Every tone but "match" (the body colour).
        $tones = array_values(array_diff(self::SKINS, ['match']));
        $hair = self::STANDARD_HAIRCOLOURS;
        $eyes = self::STANDARD_EYECOLOURS;
        return self::clean(array_merge(self::defaults(), [
            'color' => $colours[$id % count($colours)],
            'skin' => $tones[($id * 5 + 3) % count($tones)],
            'accent' => 'white',
            'face' => 'drawn',
            'eyes' => 'round',
            'eyecolor' => $eyes[($id * 7 + 2) % count($eyes)],
            'brows' => 'soft',
            'nose' => 'small',
            'mouth' => 'smile',
            'hair' => 'short',
            'haircolor' => $hair[($id * 3 + 1) % count($hair)],
            'hands' => 'cartoon',
            'handcolor' => 'skin',
            'picture' => false,
        ]));
    }

    /**
     * Clean a document against the allowlist.
     *
     * Only the keys of the schema are kept, each only with an allowed value; anything else falls
     * back to the default. A document larger than MAXBYTES, a string that is not a JSON object or a
     * document of another schema gives the default document.
     *
     * @param array|string $input The document, as an array or as JSON.
     * @return array The clean document, every key set, in the schema's key order.
     */
    public static function clean(array|string $input): array {
        $clean = self::defaults();
        if (is_string($input)) {
            if (strlen($input) > self::MAXBYTES) {
                return $clean;
            }
            $input = json_decode($input, true, 4);
            if (!is_array($input)) {
                return $clean;
            }
        } else {
            $json = json_encode($input);
            if ($json === false || strlen($json) > self::MAXBYTES) {
                return $clean;
            }
        }
        if (array_key_exists('schema', $input) && $input['schema'] !== self::SCHEMA) {
            return $clean;
        }

        $choices = self::choices();
        foreach ($choices as $key => $allowed) {
            if (isset($input[$key]) && is_string($input[$key]) && in_array($input[$key], $allowed, true)) {
                $clean[$key] = $input[$key];
            }
        }
        foreach (self::FLAGS as $key) {
            if (array_key_exists($key, $input)) {
                $flag = self::flag($input[$key]);
                if ($flag !== null) {
                    $clean[$key] = $flag;
                }
            }
        }
        // A document from before eyewear styles: its glasses are the first version's ("classic"), as
        // avatarmodel reads it. Then "glasses" follows the eyewear, so viewers that know only the flag
        // still show glasses.
        $eyewear = $input['eyewear'] ?? null;
        if (!is_string($eyewear) || !in_array($eyewear, $choices['eyewear'], true)) {
            $clean['eyewear'] = $clean['glasses'] ? 'classic' : self::MODULE_DEFAULTS['eyewear'];
        }
        $clean['glasses'] = $clean['eyewear'] !== 'none';
        return $clean;
    }

    /**
     * A true or false value, or null for anything else.
     *
     * @param mixed $value Value from the document.
     * @return bool|null
     */
    private static function flag(mixed $value): ?bool {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }
        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }
        return null;
    }

    /**
     * The user's saved design, cleaned again, or null when the user has none.
     *
     * @param int $userid User id.
     * @return array|null
     */
    public static function load(int $userid): ?array {
        $stored = get_user_preferences(self::PREFERENCE, null, $userid);
        if ($stored === null || $stored === '') {
            return null;
        }
        return self::clean((string)$stored);
    }

    /**
     * The saved designs of several users in one query, each cleaned again.
     *
     * @param int[] $userids User ids.
     * @return array userid => clean document, only for users with a design
     */
    public static function load_many(array $userids): array {
        global $DB;
        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        if (!$userids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $params['name'] = self::PREFERENCE;
        $rows = $DB->get_records_select(
            'user_preferences',
            "name = :name AND userid {$insql}",
            $params,
            '',
            'id, userid, value'
        );
        $designs = [];
        foreach ($rows as $row) {
            if ((string)$row->value !== '') {
                $designs[(int)$row->userid] = self::clean((string)$row->value);
            }
        }
        return $designs;
    }

    /**
     * Clean and save a user's design.
     *
     * @param int $userid User id.
     * @param array $design The document.
     * @return array The clean document that was saved.
     */
    public static function save(int $userid, array $design): array {
        $clean = self::clean($design);
        set_user_preference(self::PREFERENCE, json_encode($clean), $userid);
        return $clean;
    }

    /**
     * Remove a user's design: the user has the standard look again.
     *
     * @param int $userid User id.
     * @return void
     */
    public static function remove(int $userid): void {
        unset_user_preference(self::PREFERENCE, $userid);
    }

    /**
     * Whether the site lets users show their profile picture on their avatar (on by default).
     *
     * @return bool
     */
    public static function picture_allowed(): bool {
        $setting = get_config('local_vravatar', 'allowpicture');
        return $setting === false ? true : !empty($setting);
    }

    /**
     * Whether a user has a profile picture worth showing: an uploaded picture, or a Gravatar when
     * the site uses them. The standard grey silhouette is not shown on an avatar.
     *
     * @param \stdClass $user User record with the user picture fields and deleted.
     * @return bool
     */
    public static function has_picture(\stdClass $user): bool {
        global $CFG;
        if (!empty($user->deleted)) {
            return false;
        }
        return (int)($user->picture ?? 0) > 0 || !empty($CFG->enablegravatar);
    }

    /**
     * The address of a user's normal profile picture, the one Moodle shows next to their name.
     *
     * @param \stdClass $user User record with the user picture fields and deleted.
     * @param \moodle_page $page Page whose theme and renderer to use.
     * @return string The URL, or '' when the user has no picture worth showing.
     */
    public static function picture_url(\stdClass $user, \moodle_page $page): string {
        if (!self::has_picture($user)) {
            return '';
        }
        $picture = new \core\output\user_picture($user);
        $picture->size = self::PICTURESIZE;
        return $picture->get_url($page)->out(false);
    }

    /**
     * The user fields that picture_url() needs.
     *
     * @return string Comma separated list for a $DB call.
     */
    public static function picture_fields(): string {
        $fields = \core_user\fields::get_picture_fields();
        $fields[] = 'deleted';
        return implode(',', array_unique($fields));
    }
}
