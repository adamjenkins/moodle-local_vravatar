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
 * Tests of the avatar design allowlist and its storage.
 *
 * @package    local_vravatar
 * @category   test
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(design::class)]
final class design_test extends \advanced_testcase {
    /** @var array A design with every key set to a value other than the default. */
    private const FULL = [
        'schema' => 'vrroom-avatar/1',
        'body' => 'broad',
        'head' => 'heart',
        'skin' => 'tan',
        'color' => 'teal',
        'accent' => 'pink',
        'hair' => 'braids',
        'haircolor' => 'purple',
        'eyes' => 'almond',
        'brows' => 'arched',
        'nose' => 'pointed',
        'ears' => true,
        'beard' => 'goatee',
        'glasses' => true,
        'hat' => 'sunhat',
        'outfit' => 'device-computer',
        'hands' => 'glove',
        'handcolor' => 'cyan',
        'face' => 'drawn',
        'eyecolor' => 'hazel',
        'eyespace' => 'wide',
        'browcolor' => 'black',
        'mouth' => 'grin',
        'lips' => 'coral',
        'earshape' => 'lobe',
        'freckles' => 'nose',
        'cheeks' => 'dimples',
        'marks' => 'mole',
        'lines' => 'crow',
        'makeup' => 'kohl',
        'facelayout' => 'compact',
        'beardcolor' => 'greying',
        'hairgrey' => 'temples',
        'eyewear' => 'aviator',
        'eyewearcolor' => 'gold',
        'neckwear' => 'pearls',
        'chest' => 'star',
        'earrings' => 'hoops',
        'hairflower' => 'rose',
        'wristleft' => 'watch',
        'wristright' => 'beads',
        'ankleleft' => 'chain',
        'ankleright' => 'bells',
        'metal' => 'silver',
        'fabric' => 'teal',
        'tattoo1' => 'anchor',
        'tattoo1at' => 'forearmleft',
        'tattoo1size' => 'large',
        'tattoo1ink' => 'navy',
        'tattoo2' => 'rose',
        'tattoo2at' => 'calfright',
        'tattoo2size' => 'small',
        'tattoo2ink' => 'red',
        'tattoo3' => 'none',
        'tattoo3at' => 'forearmright',
        'tattoo3size' => 'medium',
        'tattoo3ink' => 'black',
        'tattoo4' => 'none',
        'tattoo4at' => 'forearmright',
        'tattoo4size' => 'medium',
        'tattoo4ink' => 'black',
        'picture' => true,
    ];

    /** @var array The defaults of the keys the avatar builder's modules added: today's look. */
    private const MODULE_DEFAULTS = [
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
        'facelayout' => 'balanced',
        'beardcolor' => 'hair',
        'hairgrey' => 'none',
        'eyewear' => 'none',
        'eyewearcolor' => 'dark',
        'neckwear' => 'none',
        'chest' => 'none',
        'earrings' => 'none',
        'hairflower' => 'none',
        'wristleft' => 'none',
        'wristright' => 'none',
        'ankleleft' => 'none',
        'ankleright' => 'none',
        'metal' => 'gold',
        'fabric' => 'accent',
        'tattoo1' => 'none',
        'tattoo1at' => 'forearmright',
        'tattoo1size' => 'medium',
        'tattoo1ink' => 'black',
        'tattoo2' => 'none',
        'tattoo2at' => 'forearmright',
        'tattoo2size' => 'medium',
        'tattoo2ink' => 'black',
        'tattoo3' => 'none',
        'tattoo3at' => 'forearmright',
        'tattoo3size' => 'medium',
        'tattoo3ink' => 'black',
        'tattoo4' => 'none',
        'tattoo4at' => 'forearmright',
        'tattoo4size' => 'medium',
        'tattoo4ink' => 'black',
    ];

    /**
     * The defaults are the contract's defaults, and an empty document gives them.
     */
    public function test_defaults(): void {
        $expected = [
            'schema' => 'vrroom-avatar/1',
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
            'hands' => 'natural',
            'handcolor' => 'skin',
            'face' => 'plate',
        ] + self::MODULE_DEFAULTS + [
            'picture' => true,
        ];
        $this->assertSame($expected, design::defaults());
        $this->assertSame($expected, design::clean([]));
        $this->assertSame($expected, design::clean('{}'));
    }

    /**
     * A design of allowed values is kept as it is, from an array or from JSON.
     */
    public function test_allowed_values_kept(): void {
        $this->assertSame(self::FULL, design::clean(self::FULL));
        $this->assertSame(self::FULL, design::clean(json_encode(self::FULL)));
        $withoutschema = self::FULL;
        unset($withoutschema['schema']);
        $this->assertSame(self::FULL, design::clean($withoutschema));
    }

    /**
     * Every allowed name of every key is accepted.
     */
    public function test_every_choice_accepted(): void {
        foreach (design::choices() as $key => $values) {
            if (preg_match('/^tattoo[1-4]/', $key)) {
                // A tattoo slot's keys hold together: test_tattoos_cleaned.
                continue;
            }
            foreach ($values as $value) {
                $this->assertSame($value, design::clean([$key => $value])[$key], "{$key}={$value}");
            }
        }
        $this->assertCount(14, design::COLOURS);
        $this->assertCount(10, design::HAIRCOLOURS);
        $this->assertCount(9, design::SKINS);
        $this->assertSame(design::COLOURS, array_keys(design::SWATCHES));
        $this->assertSame(design::HAIRCOLOURS, array_keys(design::HAIRSWATCHES));
        // Every skin tone but "match" (the body colour) has a swatch.
        $this->assertSame(array_values(array_diff(design::SKINS, ['match'])), array_keys(design::SKINSWATCHES));
    }

    /**
     * Black and white are body and accent colours (a black suit with a white or black tie), with a
     * swatch each, after the first twelve so the palette order of older designs is unchanged.
     */
    public function test_black_and_white_colours(): void {
        $choices = design::choices();
        foreach (['color', 'accent'] as $key) {
            $this->assertSame(['black', 'white'], array_slice($choices[$key], -2), $key);
        }
        $this->assertSame('black', design::clean(['color' => 'black', 'accent' => 'black'])['color']);
        $this->assertSame('black', design::clean(['color' => 'black', 'accent' => 'black'])['accent']);
        $this->assertSame('white', design::clean(['accent' => 'white'])['accent']);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', design::SWATCHES['black']);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', design::SWATCHES['white']);
        $this->assertSame('Black', get_string('colour_black', 'local_vravatar'));
        $this->assertSame('White', get_string('colour_white', 'local_vravatar'));
    }

    /**
     * The lists of the first versions are all still allowed, the avatar builder's modules give the
     * long lists the owner asked for, and every value of a module key is in exactly one of its groups.
     */
    public function test_lists(): void {
        $choices = design::choices();
        $first = [
            'hair' => ['none', 'short', 'long', 'bun', 'curly', 'afro', 'ponytail', 'braids', 'buzz', 'bob', 'mohawk',
                'sidepart', 'pigtails', 'topknot'],
            'head' => ['round', 'square', 'oval', 'heart'],
            'eyes' => ['dots', 'round', 'almond', 'sleepy'],
            'brows' => ['none', 'thin', 'thick', 'arched'],
            'nose' => ['none', 'small', 'round', 'pointed'],
            'beard' => ['none', 'stubble', 'moustache', 'goatee', 'full'],
            // The fifth first hat, "pirate", is retired (RETIRED_HATS).
            'hat' => ['none', 'cap', 'beanie', 'sunhat'],
        ];
        foreach ($first as $key => $values) {
            $this->assertSame([], array_values(array_diff($values, $choices[$key])), $key);
        }
        // The first head shapes come first, in their old order.
        $this->assertSame($first['head'], array_slice($choices['head'], 0, 4));
        $this->assertSame(
            ['black', 'brown', 'blonde', 'auburn', 'grey', 'white', 'red', 'pink', 'blue', 'purple'],
            $choices['haircolor']
        );
        $this->assertSame(
            ['match', 'porcelain', 'fair', 'light', 'medium', 'tan', 'brown', 'dark', 'deep'],
            $choices['skin']
        );
        $this->assertSame(array_merge(['none'], design::wardrobe_ids(), costumes::outfit_ids()), $choices['outfit']);
        $this->assertContains('ears', design::FLAGS);
        // At least 16 eyes and 16 eyebrows (owner, 2026-11-27), and plenty of the rest.
        $this->assertGreaterThanOrEqual(16, count($choices['eyes']));
        $this->assertGreaterThanOrEqual(16, count($choices['brows']));
        $this->assertGreaterThanOrEqual(12, count($choices['head']));
        $this->assertGreaterThanOrEqual(30, count($choices['hair']));
        $this->assertGreaterThanOrEqual(30, count($choices['hat']));
        foreach (design::MODULE_KEYS as $key => $groups) {
            $grouped = array_merge(...array_values($groups));
            $this->assertSame(array_unique($grouped), $grouped, $key);
            $offered = $key === 'hat' ? array_diff($grouped, array_keys(design::RETIRED_HATS)) : $grouped;
            // The traditional cultural tattoos are not offered by default (allowculturaltattoos).
            $offered = array_diff($offered, design::refused_tattoos());
            $this->assertSame([], array_values(array_diff($offered, $choices[$key])), $key);
            foreach ($grouped as $value) {
                // The services return every name as PARAM_ALPHA.
                $this->assertMatchesRegularExpression('/^[a-z]+$/', $value, "{$key}={$value}");
            }
        }
        // Every new key has a default among its values.
        foreach (design::MODULE_DEFAULTS as $key => $default) {
            $this->assertContains($default, $choices[$key], $key);
        }
        // The colour keys have a swatch for every value with a colour of its own.
        $this->assertSame([], array_diff($choices['eyecolor'], array_keys(design::EYESWATCHES), ['classic', 'odd']));
        $this->assertSame([], array_diff($choices['lips'], array_keys(design::LIPSWATCHES), ['none', 'gloss']));
        $this->assertSame($choices['eyewearcolor'], array_keys(design::FRAMESWATCHES));
        $this->assertSame([], array_diff($choices['browcolor'], design::HAIRCOLOURS, ['hair']));
        $this->assertSame([], array_diff($choices['beardcolor'], design::HAIRCOLOURS, ['hair', 'greying']));
    }

    /**
     * A design saved before the new keys existed reads back with today's look for each of them.
     */
    public function test_old_design_keeps_its_look(): void {
        $old = [
            'schema' => 'vrroom-avatar/1',
            'body' => 'slim',
            'head' => 'square',
            'color' => 'red',
            'accent' => 'yellow',
            'hair' => 'long',
            'haircolor' => 'blonde',
            'glasses' => true,
            'hat' => 'cap',
            'picture' => false,
        ];
        $clean = design::clean(json_encode($old));
        foreach ($old as $key => $value) {
            $this->assertSame($value, $clean[$key], $key);
        }
        $this->assertSame('match', $clean['skin']);
        $this->assertSame('dots', $clean['eyes']);
        $this->assertSame('none', $clean['brows']);
        $this->assertSame('none', $clean['nose']);
        $this->assertFalse($clean['ears']);
        $this->assertSame('none', $clean['beard']);
        $this->assertSame('none', $clean['outfit']);
        // Hands as before hand styles existed: since the round hands were retired (2026-11-28), natural ones.
        $this->assertSame('natural', $clean['hands']);
        $this->assertSame('skin', $clean['handcolor']);
        // The face screen, as before face styles existed.
        $this->assertSame('plate', $clean['face']);
        // The keys the avatar builder's modules added have today's look; the glasses are the first ones.
        foreach (self::MODULE_DEFAULTS as $key => $value) {
            if ($key !== 'eyewear') {
                $this->assertSame($value, $clean[$key], $key);
            }
        }
        $this->assertSame('classic', $clean['eyewear']);
        $this->assertTrue($clean['glasses']);
        // The same old document without glasses wears none.
        $old['glasses'] = false;
        $this->assertSame('none', design::clean(json_encode($old))['eyewear']);
    }

    /**
     * "glasses" follows the eyewear, so viewers that know only the flag still show glasses; an
     * unknown eyewear is read like a missing one.
     */
    public function test_glasses_follow_eyewear(): void {
        $this->assertTrue(design::clean(['eyewear' => 'cateye'])['glasses']);
        $this->assertTrue(design::clean(['eyewear' => 'monocle', 'glasses' => false])['glasses']);
        $clean = design::clean(['eyewear' => 'none', 'glasses' => true]);
        $this->assertSame('none', $clean['eyewear']);
        $this->assertFalse($clean['glasses']);
        $clean = design::clean(['eyewear' => 'laser', 'glasses' => true]);
        $this->assertSame('classic', $clean['eyewear']);
        $this->assertTrue($clean['glasses']);
        $clean = design::clean(['eyewear' => ['round'], 'glasses' => false]);
        $this->assertSame('none', $clean['eyewear']);
        $this->assertFalse($clean['glasses']);
        foreach (design::choices()['eyewear'] as $style) {
            $this->assertSame($style !== 'none', design::clean(['eyewear' => $style])['glasses'], $style);
        }
    }

    /**
     * The keys of the avatar builder's modules are cleaned like the others: unknown or mistyped
     * values take today's look.
     */
    public function test_new_keys_cleaned(): void {
        $input = [];
        foreach (array_keys(self::MODULE_DEFAULTS) as $key) {
            $input[$key] = 'Unknown';
        }
        $input['mouth'] = ['smile'];
        $input['lips'] = '#ff0000';
        $input['marks'] = '<b>scar</b>';
        $input['makeup'] = 3;
        $clean = design::clean($input);
        foreach (self::MODULE_DEFAULTS as $key => $value) {
            $this->assertSame($value, $clean[$key], $key);
        }
        $this->assertFalse($clean['glasses']);
    }

    /**
     * The face layout is one of the named layouts; a design saved before it, or with anything else
     * there, has the balanced layout.
     */
    public function test_facelayout_cleaned(): void {
        $this->assertSame(['balanced', 'higheyes', 'loweyes', 'longnose', 'compact'], design::choices()['facelayout']);
        $this->assertSame('balanced', design::defaults()['facelayout']);
        foreach (design::choices()['facelayout'] as $layout) {
            $this->assertSame($layout, design::clean(['facelayout' => $layout])['facelayout'], $layout);
        }
        $this->assertSame('balanced', design::clean(['schema' => design::SCHEMA, 'head' => 'oval'])['facelayout']);
        foreach (['Compact', 'sideways', '', ['higheyes'], 2, '<b>loweyes</b>'] as $wrong) {
            $this->assertSame('balanced', design::clean(['facelayout' => $wrong])['facelayout'], json_encode($wrong));
        }
    }

    /**
     * Accessories: kept when allowed, an old design without them has none.
     */
    public function test_accessories_cleaned(): void {
        $this->resetAfterTest();
        $clean = design::clean(['neckwear' => 'pearls', 'earrings' => 'onehoop', 'wristleft' => 'watch',
            'ankleright' => 'bells', 'metal' => 'silver', 'fabric' => 'teal', 'chest' => 'nonsense']);
        $this->assertSame('pearls', $clean['neckwear']);
        $this->assertSame('onehoop', $clean['earrings']);
        $this->assertSame('watch', $clean['wristleft']);
        $this->assertSame('bells', $clean['ankleright']);
        $this->assertSame('silver', $clean['metal']);
        $this->assertSame('teal', $clean['fabric']);
        $this->assertSame('none', $clean['chest']);
        $old = design::clean(['schema' => design::SCHEMA, 'hair' => 'bun']);
        foreach (['neckwear', 'chest', 'earrings', 'hairflower', 'wristleft', 'wristright', 'ankleleft', 'ankleright'] as $key) {
            $this->assertSame('none', $old[$key], $key);
        }
        $this->assertSame('gold', $old['metal']);
        $this->assertSame('accent', $old['fabric']);
    }

    /**
     * Tattoos as the avatar builder cleans them: a placement the design does not allow, a place
     * already taken (a full-face design covers the cheeks and chin) or an unknown design empties the
     * slot; henna designs are in henna; the settings refuse the sets they turn off.
     */
    public function test_tattoos_cleaned(): void {
        $this->resetAfterTest();
        $clean = design::clean(['tattoo1' => 'anchor', 'tattoo1at' => 'forearmleft', 'tattoo1ink' => 'navy',
            'tattoo2' => 'anchor', 'tattoo2at' => 'cheekleft', 'tattoo3' => 'rose', 'tattoo3at' => 'forearmleft',
            'tattoo4' => 'sun', 'tattoo4at' => 'calfright', 'tattoo4size' => 'large']);
        $this->assertSame(['anchor', 'forearmleft', 'navy'], [$clean['tattoo1'], $clean['tattoo1at'], $clean['tattoo1ink']]);
        // The anchor may not go on a cheek; the left forearm is taken.
        $this->assertSame(
            ['none', 'forearmright', 'medium', 'black'],
            [$clean['tattoo2'], $clean['tattoo2at'], $clean['tattoo2size'], $clean['tattoo2ink']]
        );
        $this->assertSame('none', $clean['tattoo3']);
        $this->assertSame(['sun', 'calfright', 'large'], [$clean['tattoo4'], $clean['tattoo4at'], $clean['tattoo4size']]);

        // Cultural designs: off by default, so dropped (a saved one draws without it).
        $cultural = ['tattoo1' => 'maoriface', 'tattoo1at' => 'fullface', 'tattoo2' => 'amazighcheek',
            'tattoo2at' => 'cheekleft', 'tattoo3' => 'mehndiarm', 'tattoo3at' => 'forearmleft', 'tattoo3ink' => 'red'];
        $this->assertSame('none', design::clean($cultural)['tattoo1']);
        $this->assertNotContains('maoriface', design::choices()['tattoo1']);
        set_config('allowculturaltattoos', 1, 'local_vravatar');
        $clean = design::clean($cultural);
        $this->assertSame('maoriface', $clean['tattoo1']);
        // The whole face covers the cheeks; henna is always henna.
        $this->assertSame('none', $clean['tattoo2']);
        $this->assertSame(['mehndiarm', 'henna'], [$clean['tattoo3'], $clean['tattoo3ink']]);
        // Tattoos off: none at all, whatever the cultural setting.
        set_config('allowtattoos', 0, 'local_vravatar');
        $this->assertSame('none', design::clean(['tattoo1' => 'anchor', 'tattoo1at' => 'forearmleft'])['tattoo1']);
        $this->assertSame(['none'], design::choices()['tattoo2']);
    }

    /**
     * The tattoo lists agree with each other: every design in exactly one set, with its places.
     */
    public function test_tattoo_lists(): void {
        $all = array_merge(...array_values(design::TATTOO_SETS));
        $this->assertSame(count($all), count(array_unique($all)));
        $this->assertEqualsCanonicalizing($all, array_keys(design::TATTOO_PLACES));
        $offered = array_diff(design::choices()['tattoo1'], ['none']);
        $this->assertEmpty(array_diff($offered, $all));
        $places = array_merge(...array_values(design::MODULE_KEYS['tattoo1at']));
        foreach (design::TATTOO_PLACES as $id => $at) {
            $this->assertEmpty(array_diff($at, $places), $id);
        }
    }

    /**
     * A document with every key at its longest value still fits the size cap, with room to spare.
     */
    public function test_longest_document_fits(): void {
        $longest = ['schema' => design::SCHEMA];
        foreach (design::choices() as $key => $values) {
            usort($values, fn($a, $b) => strlen($b) <=> strlen($a));
            $longest[$key] = $values[0];
        }
        // The longest costume id the avatar builder accepts (avatarmodel COSTUME_ID_MAX).
        $longest['outfit'] = str_repeat('x', 40);
        foreach (design::FLAGS as $flag) {
            $longest[$flag] = true;
        }
        $json = json_encode($longest);
        // With the accessory and tattoo keys (2026-11-28) it still leaves a third of the cap free.
        $this->assertLessThan(design::MAXBYTES * 2 / 3, strlen($json), $json);
        // A real document of the longest values (a costume id that exists) is kept whole.
        $real = $longest;
        $real['outfit'] = 'device-computer';
        $this->assertSame(array_keys(design::defaults()), array_keys(design::clean($real)));
        foreach ($real as $key => $value) {
            if (preg_match('/^tattoo[1-4]/', $key)) {
                // The longest tattoo values do not hold together (a design and a place it does not allow).
                continue;
            }
            $this->assertSame($value, design::clean(json_encode($real))[$key], $key);
        }
    }

    /**
     * Every hand style and hand colour is kept; anything else falls back to today's hands.
     */
    public function test_hands(): void {
        foreach (design::HANDSTYLES as $style) {
            $this->assertSame($style, design::clean(['hands' => $style])['hands']);
        }
        foreach (design::handcolours() as $colour) {
            $this->assertSame($colour, design::clean(['handcolor' => $colour])['handcolor']);
        }
        $this->assertSame('skin', design::handcolours()[0]);
        $clean = design::clean(['hands' => 'claw', 'handcolor' => 'gold']);
        $this->assertSame('natural', $clean['hands']);
        $this->assertSame('skin', $clean['handcolor']);
        $this->assertSame('natural', design::clean(['hands' => ['mitten']])['hands']);
        // The retired round and mitten hands are natural hands now, keeping their colour.
        $retired = design::clean(['hands' => 'mitten', 'handcolor' => 'teal']);
        $this->assertSame(['natural', 'teal'], [$retired['hands'], $retired['handcolor']]);
        $this->assertSame('natural', design::clean(['hands' => 'sphere'])['hands']);
        $this->assertNotContains('sphere', design::choices()['hands']);
    }

    /**
     * "outfit" keeps "none" and the ids of the shipped costumes of kind body or device; a hat model,
     * a category or anything else is dropped.
     */
    public function test_outfit_costumes(): void {
        $this->assertSame('none', design::clean(['outfit' => 'none'])['outfit']);
        foreach (['device-laptop', 'device-monitor', 'device-tv', 'device-computer'] as $id) {
            $this->assertSame($id, design::clean(['outfit' => $id])['outfit'], $id);
            $this->assertSame($id, design::clean(json_encode(['outfit' => $id]))['outfit'], $id);
        }
        foreach (
            ['pirate', 'sailor', 'Suit', 'hat-pirate', 'Pirate-Captain', 'pirate-captain ', '../pirate-captain',
                'blocky-z', '', 'None', 1, true, ['none']] as $value
        ) {
            $this->assertSame('none', design::clean(['outfit' => $value])['outfit'], var_export($value, true));
        }
        $this->assertSame('piratehat', design::clean(['hat' => 'pirate'])['hat']);
    }

    /**
     * The retired models (the pirates, and the one-piece models whose arms could not move, 2026-11-28)
     * become costumes made in code or the plain clothes, as in avatarmodel's cleanAppearance(), from
     * an array or from JSON.
     */
    public function test_retired_models(): void {
        $this->assertSame('captain', design::RETIRED_OUTFITS['pirate-captain']);
        $this->assertSame('deckhand', design::RETIRED_OUTFITS['pirate-woman']);
        $this->assertSame('suit', design::RETIRED_OUTFITS['business-dude']);
        $this->assertSame('astronaut', design::RETIRED_OUTFITS['alien-green']);
        $this->assertCount(22, design::RETIRED_OUTFITS);
        $minis = array_merge(...array_map(fn($c) => ['mini-male-' . $c, 'mini-female-' . $c], range('a', 'f')));
        foreach (array_merge($minis, ['suit-tie', 'sunflower', 'cactus', 'alien-pink', 'lalobot', 'robot-screen']) as $id) {
            $this->assertArrayHasKey($id, design::RETIRED_OUTFITS, $id);
        }
        foreach (design::RETIRED_OUTFITS as $old => $new) {
            $this->assertContains($new, array_merge(['none'], design::wardrobe_ids()), $new);
            $this->assertNull(costumes::get($old), $old);
            $this->assertSame($new, design::clean(['outfit' => $old])['outfit'], $old);
            $this->assertSame($new, design::clean(json_encode(['outfit' => $old]))['outfit'], $old);
            $this->assertNotContains($old, design::choices()['outfit'], $old);
        }
    }

    /**
     * Every retired Blocky character becomes "none": the plain clothes in the user's own colours.
     */
    public function test_retired_blocky(): void {
        foreach (range('a', 'r') as $letter) {
            $id = 'blocky-' . $letter;
            $this->assertSame('none', design::clean(['outfit' => $id, 'color' => 'teal'])['outfit'], $id);
            $this->assertSame('teal', design::clean(['outfit' => $id, 'color' => 'teal'])['color'], $id);
            $this->assertNotContains($id, design::choices()['outfit'], $id);
        }
    }

    /**
     * The retired pirate hat model becomes the pirate tricorne made in code, and is not offered.
     */
    public function test_retired_pirate_hat(): void {
        $this->assertSame('piratehat', design::clean(['hat' => 'pirate'])['hat']);
        $this->assertSame('piratehat', design::clean('{"hat":"pirate"}')['hat']);
        $this->assertNotContains('pirate', design::choices()['hat']);
        $this->assertContains('tricorne', design::choices()['hat']);
    }

    /**
     * A design saved before the retirements (stored JSON as 0.2.0 wrote it: a pirate model, the
     * pirate hat, no face, hair or eyewear keys) still loads, with the replacements and its own look.
     */
    public function test_design_saved_before_retirements(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $stored = '{"schema":"vrroom-avatar/1","body":"slim","head":"oval","skin":"tan","color":"purple",' .
            '"accent":"yellow","hair":"long","haircolor":"auburn","eyes":"almond","brows":"arched","nose":"round",' .
            '"ears":true,"beard":"none","glasses":true,"hat":"pirate","outfit":"pirate-woman","hands":"glove",' .
            '"handcolor":"teal","face":"drawn","picture":false}';
        set_user_preference(design::PREFERENCE, $stored, (int)$user->id);
        $loaded = design::load((int)$user->id);
        $this->assertSame('deckhand', $loaded['outfit']);
        $this->assertSame('piratehat', $loaded['hat']);
        foreach (json_decode($stored, true) as $key => $value) {
            if (!in_array($key, ['outfit', 'hat'], true)) {
                $this->assertSame($value, $loaded[$key], $key);
            }
        }
        $this->assertSame('classic', $loaded['eyewear']);
        $this->assertSame(array_keys(design::defaults()), array_keys($loaded));
        // A Blocky character saved then wears the plain clothes.
        set_user_preference(design::PREFERENCE, '{"schema":"vrroom-avatar/1","outfit":"blocky-c","hat":"cap"}', (int)$user->id);
        $this->assertSame('none', design::load((int)$user->id)['outfit']);
        $this->assertSame('cap', design::load((int)$user->id)['hat']);
    }

    /**
     * The costumes the avatar builder makes in code are kept, with no model to give; an id that is
     * neither one of them nor a shipped model becomes "none".
     */
    public function test_outfit_wardrobe(): void {
        $this->assertCount(50, design::wardrobe_ids());
        foreach (['labcoat', 'pilot', 'kilt', 'cabincrew', 'hanbok', 'captain'] as $id) {
            $this->assertSame($id, design::clean(['outfit' => $id])['outfit'], $id);
            $this->assertSame($id, design::clean(json_encode(['outfit' => $id]))['outfit'], $id);
            $this->assertSame([], costumes::assets_for(design::clean(['outfit' => $id])), $id);
        }
        foreach (['spacesuit', 'lab-coat', 'Labcoat', 'plain', 'travel'] as $id) {
            $this->assertSame('none', design::clean(['outfit' => $id])['outfit'], $id);
        }
        // No id is both a costume made in code and a model.
        $this->assertSame([], array_intersect(design::wardrobe_ids(), array_keys(costumes::all())));
    }

    /**
     * Unknown keys are dropped and unknown or mistyped values take the default.
     */
    public function test_unknown_dropped(): void {
        $clean = design::clean([
            'body' => 'giant',
            'head' => ['round'],
            'color' => 'Blue',
            'accent' => '#ff0000',
            'hair' => '<script>alert(1)</script>',
            'haircolor' => 3,
            'glasses' => 'yes',
            'hat' => null,
            'picture' => 'https://example.org/me.png',
            'skin' => '#c98e64',
            'eyes' => 'laser',
            'brows' => 'Thick',
            'nose' => ['small'],
            'ears' => 'pointy',
            'beard' => 'full ',
            'outfit' => 'pirate',
            'name' => 'Someone else',
            'html' => '<b>bold</b>',
        ]);
        $this->assertSame(design::defaults(), $clean);
        $this->assertSame(array_keys(design::defaults()), array_keys($clean));
    }

    /**
     * The flags take true or false in the usual spellings.
     */
    public function test_flags(): void {
        foreach ([true, 1, '1', 'true'] as $yes) {
            $this->assertTrue(design::clean(['glasses' => $yes])['glasses']);
            $this->assertTrue(design::clean(['ears' => $yes])['ears']);
        }
        foreach ([false, 0, '0', 'false'] as $no) {
            $this->assertFalse(design::clean(['glasses' => $no, 'picture' => $no])['glasses']);
            $this->assertFalse(design::clean(['picture' => $no])['picture']);
        }
        // Anything else is the default, which shows the picture.
        $this->assertTrue(design::clean(['picture' => 2])['picture']);
    }

    /**
     * Documents larger than the cap, invalid JSON, a JSON scalar and another schema give the default.
     */
    public function test_rejected_documents(): void {
        $big = self::FULL + ['padding' => str_repeat('x', design::MAXBYTES)];
        $this->assertSame(design::defaults(), design::clean($big));
        $this->assertSame(design::defaults(), design::clean(json_encode($big)));
        $this->assertSame(design::defaults(), design::clean('not json'));
        $this->assertSame(design::defaults(), design::clean('"capsule"'));
        $this->assertSame(design::defaults(), design::clean(['schema' => 'vrroom-avatar/2'] + self::FULL));
        // Just under the cap is fine.
        $fits = self::FULL + ['padding' => str_repeat('x', design::MAXBYTES - strlen(json_encode(self::FULL)) - 20)];
        $this->assertLessThanOrEqual(design::MAXBYTES, strlen(json_encode($fits)));
        $this->assertSame(self::FULL, design::clean($fits));
    }

    /**
     * Save cleans and stores one preference; load cleans again; load_many reads several users.
     */
    public function test_save_load(): void {
        global $DB;
        $this->resetAfterTest();
        $a = $this->getDataGenerator()->create_user();
        $b = $this->getDataGenerator()->create_user();
        $c = $this->getDataGenerator()->create_user();

        $this->assertNull(design::load((int)$a->id));
        $saved = design::save((int)$a->id, ['body' => 'slim', 'evil' => 'x']);
        $this->assertSame('slim', $saved['body']);
        $this->assertArrayNotHasKey('evil', $saved);
        $this->assertSame($saved, design::load((int)$a->id));
        $stored = $DB->get_field('user_preferences', 'value', ['userid' => $a->id, 'name' => design::PREFERENCE]);
        $this->assertSame($saved, json_decode($stored, true));

        // A tampered preference is cleaned on read.
        $DB->set_field('user_preferences', 'value', '{"body":"giant","color":"red","x":"<b>"}', [
            'userid' => $a->id,
            'name' => design::PREFERENCE,
        ]);
        // The same document, whatever the key order.
        $this->assertEquals(['color' => 'red'] + design::defaults(), design::load((int)$a->id));

        design::save((int)$b->id, self::FULL);
        $many = design::load_many([(int)$a->id, (int)$b->id, (int)$c->id, (int)$b->id, 0]);
        $this->assertEqualsCanonicalizing([(int)$a->id, (int)$b->id], array_keys($many));
        $this->assertSame(self::FULL, $many[(int)$b->id]);
        $this->assertSame('red', $many[(int)$a->id]['color']);
        $this->assertArrayNotHasKey((int)$c->id, $many);
        $this->assertSame([], design::load_many([]));

        design::remove((int)$a->id);
        $this->assertNull(design::load((int)$a->id));
    }

    /**
     * The profile picture setting is on by default and can be turned off.
     */
    public function test_picture_allowed(): void {
        $this->resetAfterTest();
        $this->assertTrue(design::picture_allowed());
        unset_config('allowpicture', 'local_vravatar');
        $this->assertTrue(design::picture_allowed());
        set_config('allowpicture', 0, 'local_vravatar');
        $this->assertFalse(design::picture_allowed());
    }

    /**
     * Only a real profile picture (or a Gravatar) gives a picture address.
     */
    public function test_picture_url(): void {
        global $CFG, $DB, $PAGE;
        $this->resetAfterTest();
        // As in a live room: a logged-in user asks (with forced login, others get the default picture).
        $this->setUser($this->getDataGenerator()->create_user());
        $CFG->enablegravatar = 0;
        $without = $this->getDataGenerator()->create_user();
        $with = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'picture', 42, ['id' => $with->id]);

        $fields = design::picture_fields();
        $userwithout = $DB->get_record('user', ['id' => $without->id], $fields);
        $userwith = $DB->get_record('user', ['id' => $with->id], $fields);
        $this->assertSame('', design::picture_url($userwithout, $PAGE));
        $url = design::picture_url($userwith, $PAGE);
        $this->assertStringContainsString('/user/icon/', $url);
        $this->assertStringContainsString('rev=42', $url);

        $userwith->deleted = 1;
        $this->assertSame('', design::picture_url($userwith, $PAGE));

        $CFG->enablegravatar = 1;
        $this->assertStringContainsString('gravatar.com/avatar/', design::picture_url($userwithout, $PAGE));
    }

    /**
     * The standard look: drawn face, a real skin tone and a bright body colour that are always the same
     * for a user, a white accent, no picture; and a saved design without the newer keys still reads as the screen face.
     */
    public function test_standard(): void {
        $a = design::standard(17);
        $this->assertSame($a, design::standard(17));
        $this->assertSame($a, design::clean($a));
        $this->assertSame('drawn', $a['face']);
        $this->assertSame('white', $a['accent']);
        // Natural hands in the skin tone (since 2026-11-28), not gloves; a natural hair and eye colour.
        $this->assertSame('natural', $a['hands']);
        $this->assertSame('skin', $a['handcolor']);
        $this->assertContains($a['haircolor'], design::STANDARD_HAIRCOLOURS);
        $this->assertContains($a['eyecolor'], design::STANDARD_EYECOLOURS);
        $this->assertSame('none', $a['eyewear']);
        $this->assertFalse($a['glasses']);
        $this->assertFalse($a['picture']);
        $this->assertContains($a['color'], design::STANDARD_COLOURS);
        // A real skin tone, not the body colour; across a class, every tone.
        $this->assertNotSame('match', $a['skin']);
        $tones = [];
        foreach (range(1, 8) as $id) {
            $tones[] = design::standard($id)['skin'];
        }
        $this->assertCount(8, array_unique($tones));
        $colours = [];
        foreach (range(1, 10) as $id) {
            $colours[] = design::standard($id)['color'];
        }
        $this->assertCount(10, array_unique($colours));
        $this->assertSame('plate', design::clean(['color' => 'red'])['face']);
    }
}
