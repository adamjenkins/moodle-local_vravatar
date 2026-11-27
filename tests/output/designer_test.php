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

namespace local_vravatar\output;

use local_vravatar\local\costumes;
use local_vravatar\local\design;

/**
 * Tests of the designer page's template data.
 *
 * @package    local_vravatar
 * @category   test
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(designer::class)]
final class designer_test extends \advanced_testcase {
    /**
     * Every choice but the costume is shown exactly once as a radio group or checkbox, on the six
     * tabs, every value with a lang string, and the current design is the one checked.
     */
    public function test_sections(): void {
        global $PAGE;
        $this->resetAfterTest();
        $current = design::clean(['skin' => 'dark', 'hair' => 'mohawk', 'ears' => true, 'beard' => 'stubble',
            'hands' => 'robot', 'handcolor' => 'teal']);
        $designer = new designer($current, true, true, false, new \core\url('/local/vravatar/index.php'));
        $data = $designer->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame(
            [
                get_string('section_head', 'local_vravatar'),
                get_string('section_hair', 'local_vravatar'),
                get_string('section_body', 'local_vravatar'),
                get_string('section_costume', 'local_vravatar'),
                get_string('section_hands', 'local_vravatar'),
                get_string('section_extras', 'local_vravatar'),
            ],
            array_column($data['sections'], 'heading')
        );
        // Each tab has a short name and an icon; a body tab frames the whole avatar, the hands tab the hands.
        foreach ($data['sections'] as $section) {
            $this->assertStringNotContainsString('[[', $section['tablabel']);
            $this->assertStringContainsString('tabs/', $section['icon']);
        }
        $this->assertSame([true, false, false, false, false, false], array_column($data['sections'], 'first'));
        $this->assertSame(['face', 'face', 'whole', 'whole', 'hands', 'face'], array_column($data['sections'], 'frame'));

        $radios = [];
        $checks = [];
        foreach ($data['sections'] as $section) {
            foreach ($section['items'] as $item) {
                if (isset($item['radio'])) {
                    $radios[$item['radio']['name']] = $item['radio'];
                } else if (isset($item['costume'])) {
                    continue;
                } else {
                    $checks[$item['check']['name']] = $item['check'];
                }
            }
        }
        $expected = array_diff(array_keys(design::choices()), ['outfit']);
        $this->assertEqualsCanonicalizing($expected, array_keys($radios));
        // The glasses flag has no box: it follows the eyewear.
        $this->assertSame(['ears'], array_keys($checks));
        $this->assertTrue($checks['ears']['checked']);
        $this->assertSame('face', $checks['ears']['render']);

        foreach ($radios as $key => $group) {
            $this->assertSame(design::choices()[$key], array_column($group['options'], 'value'), $key);
            // The groups hold every option once, in the same order.
            $ingroups = array_merge(...array_map(fn($g) => array_column($g['options'], 'value'), $group['groups']));
            $this->assertSame(design::choices()[$key], $ingroups, $key);
            foreach ($group['groups'] as $sub) {
                $this->assertStringNotContainsString('[[', $sub['legend'], $key);
            }
            $checked = array_column(array_filter($group['options'], fn($o) => $o['checked']), 'value');
            $this->assertSame([$current[$key]], $checked, $key);
            foreach ($group['options'] as $option) {
                $this->assertNotSame('', $option['label']);
                $this->assertStringNotContainsString('[[', $option['label'], "{$key}={$option['value']}");
            }
        }
        // The tabs hold the face, hair and headwear keys of the avatar builder's modules.
        $keysof = function (int $index) use ($data): array {
            $keys = [];
            foreach ($data['sections'][$index]['items'] as $item) {
                $keys[] = ($item['radio'] ?? $item['check'] ?? $item['costume'])['name'];
            }
            return $keys;
        };
        $this->assertSame(['face', 'head', 'skin', 'eyes', 'eyecolor', 'eyespace', 'brows', 'browcolor', 'nose', 'mouth',
            'lips', 'ears', 'earshape', 'cheeks', 'freckles', 'marks', 'lines', 'makeup'], $keysof(0));
        $this->assertSame(['hair', 'haircolor', 'hairgrey', 'beard', 'beardcolor'], $keysof(1));
        $this->assertSame(['hat', 'eyewear', 'eyewearcolor'], $keysof(5));
        // Face, Hair and Extras come in parts, one shown at a time; together they hold the tab's items in order.
        $partsof = fn(int $index) => array_map(
            fn($part) => array_map(fn($item) => ($item['radio'] ?? $item['check'] ?? $item['costume'])['name'], $part['items']),
            $data['sections'][$index]['parts']
        );
        $this->assertSame([['face', 'head', 'skin'], ['eyes', 'eyecolor', 'eyespace'], ['brows', 'browcolor'],
            ['nose', 'mouth', 'lips'], ['ears', 'earshape'], ['cheeks', 'freckles', 'marks', 'lines', 'makeup']], $partsof(0));
        $this->assertSame([['hair', 'haircolor', 'hairgrey'], ['beard', 'beardcolor']], $partsof(1));
        $this->assertSame([['hat'], ['eyewear', 'eyewearcolor']], $partsof(5));
        $this->assertSame(get_string('part_shape', 'local_vravatar'), $data['sections'][0]['parts'][0]['label']);
        $this->assertSame([true, true, false, false, false, true], array_column($data['sections'], 'hasparts'));
        foreach ([2, 3, 4] as $index) {
            $this->assertSame([$keysof($index)], $partsof($index));
            $this->assertSame('', $data['sections'][$index]['parts'][0]['label']);
        }

        // Colours are swatches, the rest tiles drawn on the user's avatar.
        foreach (['eyecolor', 'browcolor', 'lips', 'beardcolor', 'eyewearcolor'] as $key) {
            $this->assertTrue($radios[$key]['isswatch'], $key);
            $this->assertCount(1, $radios[$key]['groups'], $key);
        }
        foreach (
            ['eyes', 'eyespace', 'brows', 'nose', 'mouth', 'earshape', 'cheeks', 'freckles', 'marks', 'lines', 'makeup',
            'hairgrey', 'eyewear'] as $key
        ) {
            $this->assertFalse($radios[$key]['isswatch'], $key);
            $this->assertSame('face', $radios[$key]['options'][1]['render'], $key);
        }
        // Long lists come under the builder's group headings; the group of "none" alone has none.
        $legends = fn($key) => array_column($radios[$key]['groups'], 'legend');
        $this->assertSame(['Cartoon', 'Classic', 'Open', 'Lids and folds', 'Tilted'], $legends('eyes'));
        $this->assertSame('', $legends('hair')[0]);
        $this->assertCount(9, $legends('hair'));
        $this->assertSame(
            ['', 'Everyday', 'Smart', 'Faith and tradition', 'Work and uniform', 'Celebration', 'Dress-up'],
            $legends('hat')
        );
        $this->assertSame([''], $legends('freckles'));
        $this->assertSame([''], $legends('eyespace'));
        // What a swatch without its own colour follows, for the page to paint.
        $byvalue = fn($key) => array_column($radios[$key]['options'], null, 'value');
        $this->assertSame('body', $byvalue('skin')['match']['followed']);
        $this->assertSame('hair', $byvalue('browcolor')['hair']['followed']);
        $this->assertSame('greying', $byvalue('beardcolor')['greying']['followed']);
        $this->assertSame('skin', $byvalue('lips')['none']['followed']);
        $this->assertSame(design::EYESWATCHES['hazel'], $byvalue('eyecolor')['hazel']['swatch']);
        $this->assertStringContainsString('gradient', $byvalue('eyecolor')['odd']['swatch']);
        $this->assertSame(design::FRAMESWATCHES['gold'], $byvalue('eyewearcolor')['gold']['swatch']);

        // Skin tones are named swatches; "match" has no swatch of its own.
        $this->assertTrue($radios['skin']['isswatch']);
        $this->assertSame('', $radios['skin']['options'][0]['swatch']);
        $this->assertSame(get_string('skin_match', 'local_vravatar'), $radios['skin']['options'][0]['label']);
        $this->assertSame('skin', $radios['skin']['options'][1]['palette']);

        // Every hat is drawn by the page, with no model or credit; the retired pirate hat is not offered.
        $hats = array_column($radios['hat']['options'], null, 'value');
        $this->assertArrayNotHasKey('pirate', $hats);
        foreach (['cap', 'tricorne', 'hijab'] as $hat) {
            $this->assertSame('', $hats[$hat]['model'], $hat);
            $this->assertSame('', $hats[$hat]['credit'], $hat);
            $this->assertSame('face', $hats[$hat]['render'], $hat);
        }
        $this->assertSame('whole', $radios['body']['options'][0]['render']);

        // Hand styles are silhouettes; the hand colours are swatches, "skin" following the skin tone.
        foreach ($radios['hands']['options'] as $option) {
            $this->assertStringContainsString('hands/' . $option['value'], $option['mask']);
            $this->assertSame('', $option['render']);
        }
        $this->assertTrue($radios['handcolor']['isswatch']);
        $this->assertSame('skin', $radios['handcolor']['options'][0]['value']);
        $this->assertTrue($radios['handcolor']['options'][0]['follows']);
        $this->assertSame(get_string('handcolour_skin', 'local_vravatar'), $radios['handcolor']['options'][0]['label']);
        $this->assertFalse($radios['handcolor']['options'][1]['follows']);

        // The ready-made looks, each with a name and the keys it sets.
        $this->assertTrue($data['haspresets']);
        foreach ($data['presets'] as $preset) {
            $this->assertStringNotContainsString('[[', $preset['name']);
            $this->assertIsArray(json_decode($preset['json'], true));
        }
    }

    /**
     * The costume picker: "No costume", then every outfit once, grouped by category in page order,
     * each with its name, thumbnail, model and (only for CC BY models) credit; the current one checked.
     */
    public function test_costume_picker(): void {
        global $PAGE;
        $this->resetAfterTest();
        $current = design::clean(['outfit' => 'suit-tie']);
        $designer = new designer($current, true, true, false, new \core\url('/local/vravatar/index.php'));
        $data = $designer->export_for_template($PAGE->get_renderer('core'));

        $section = $data['sections'][3];
        $this->assertSame('local-vravatar-section-costume', $section['id']);
        $picker = $section['items'][0]['costume'];
        $this->assertSame('outfit', $picker['name']);
        $this->assertSame('none', $picker['none']['value']);
        $this->assertFalse($picker['none']['checked']);

        $categories = array_map(fn($c) => substr($c['id'], strlen('local-vravatar-costumes-')), $picker['categories']);
        // The groups of the costumes made in code first, then the models' categories, unchanged.
        $this->assertSame(array_merge(['travel', 'work', 'service', 'health', 'science', 'school', 'everyday', 'formal',
            'traditional', 'sport', 'fun'], costumes::CATEGORIES), $categories);
        $this->assertSame(get_string('costumecat_travel', 'local_vravatar'), $picker['categories'][0]['legend']);

        $options = [];
        foreach ($picker['categories'] as $category) {
            $this->assertStringNotContainsString('[[', $category['legend']);
            foreach ($category['options'] as $option) {
                $options[$option['value']] = $option;
            }
        }
        $this->assertSame(array_merge(design::wardrobe_ids(), costumes::outfit_ids()), array_keys($options));
        // A costume made in code is drawn on the user's avatar: no thumbnail, model or credit.
        foreach (design::wardrobe_ids() as $id) {
            $option = $options[$id];
            unset($options[$id]);
            $this->assertSame(get_string(costumes::name_string($id), 'local_vravatar'), $option['label']);
            $this->assertStringNotContainsString('[[', $option['label']);
            $drawn = [$option['thumb'], $option['model'], $option['credit'], $option['render']];
            $this->assertSame(['', '', '', 'whole'], $drawn, $id);
            $this->assertFalse($option['checked'], $id);
        }
        foreach ($options as $id => $option) {
            $this->assertSame('', $option['render'], $id);
            $this->assertSame(get_string(costumes::name_string($id), 'local_vravatar'), $option['label']);
            $this->assertStringContainsString('costumes/' . $id, $option['thumb']);
            $this->assertStringEndsWith('/local/vravatar/costumes/' . $id . '.glb', $option['model']);
            $this->assertSame($id === 'suit-tie', $option['checked'], $id);
            $this->assertSame(costumes::needs_attribution(costumes::get($id)), $option['credit'] !== '', $id);
        }
        $this->assertSame('by Ian MacGillivray, CC BY 3.0', $options['suit-tie']['credit']);
        $this->assertSame('device', $options['device-laptop']['kind']);

        // Every model, the hat too, is in the credits list.
        $this->assertTrue($data['hascredits']);
        $this->assertCount(count(costumes::all()), $data['credits']);
    }

    /**
     * The page renders without its template docblock leaking out, posts without JavaScript, and
     * has the zoom and camera buttons hidden until the preview starts.
     */
    public function test_template_renders(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url(new \core\url('/local/vravatar/index.php'));
        $output = $PAGE->get_renderer('core');
        $designer = new designer(design::clean([]), true, true, false, new \core\url('/local/vravatar/index.php'));
        $html = $output->render_from_template('local_vravatar/designer', $designer->export_for_template($output));

        $this->assertStringNotContainsString('Example context', $html);
        $this->assertStringNotContainsString('@template', $html);
        $this->assertMatchesRegularExpression('~<form method="post" action="[^"]*/local/vravatar/index\.php"~', $html);
        $this->assertMatchesRegularExpression('~data-action="zoom"[^>]*\shidden>~', $html);
        $this->assertMatchesRegularExpression('~data-action="zoom"[^>]*aria-pressed="false"~', $html);
        $this->assertStringContainsString(get_string('showwhole', 'local_vravatar'), $html);

        // The tab list is hidden until the page turns the sections into tabs; each tab names its panel.
        $this->assertMatchesRegularExpression('~role="tablist"[^>]*\shidden>~', $html);
        $this->assertMatchesRegularExpression(
            '~role="tab" id="local-vravatar-section-hands-tab" aria-controls="local-vravatar-section-hands"~',
            $html
        );
        // Tiles are real radio buttons with labels; the tools are hidden until the page starts them.
        $this->assertMatchesRegularExpression(
            '~<input class="local-vravatar-tile-input" type="radio" name="hands" value="mitten" id="local-vravatar-hands-mitten"~',
            $html
        );
        $this->assertStringContainsString('<label class="local-vravatar-tile-label" for="local-vravatar-hands-mitten">', $html);
        $this->assertMatchesRegularExpression('~data-action="surprise"[^>]*\shidden>~', $html);
        $this->assertMatchesRegularExpression('~data-action="undo"[^>]*\sdisabled hidden>~', $html);
        $this->assertStringContainsString('data-region="savebar"', $html);
        // The coached start's tips are on the page (hidden), one per step, and the face styles are tiles.
        foreach (['look', 'part', 'save', 'done'] as $step) {
            $this->assertStringContainsString('data-hint="' . $step . '"', $html);
        }
        $this->assertStringContainsString(get_string('coach_save', 'local_vravatar'), $html);
        $this->assertMatchesRegularExpression('~name="face" value="drawn" id="local-vravatar-face-drawn"~', $html);
        $this->assertStringContainsString(get_string('preset_explorer', 'local_vravatar'), $html);
        $this->assertStringNotContainsString('[[', $html);
    }

    /**
     * The costume picker renders as labelled radio buttons named outfit, each with a decorative
     * thumbnail and its model address, the CC BY credits are shown, and the credits list links the
     * sources.
     */
    public function test_template_renders_costumes(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url(new \core\url('/local/vravatar/index.php'));
        $output = $PAGE->get_renderer('core');
        $designer = new designer(
            design::clean(['outfit' => 'device-laptop', 'hat' => 'tricorne']),
            true,
            true,
            false,
            new \core\url('/local/vravatar/index.php')
        );
        $html = $output->render_from_template('local_vravatar/designer', $designer->export_for_template($output));

        $this->assertStringContainsString(get_string('section_costume', 'local_vravatar'), $html);
        $this->assertMatchesRegularExpression(
            '~<input class="local-vravatar-tile-input" type="radio" name="outfit" value="none"\s+id="local-vravatar-outfit-none"~',
            $html
        );
        foreach (costumes::outfit_ids() as $id) {
            $this->assertMatchesRegularExpression(
                '~<input class="local-vravatar-tile-input" type="radio" name="outfit"\s+value="' . preg_quote($id, '~') .
                    '" id="local-vravatar-outfit-' . preg_quote($id, '~') . '" data-model="[^"]*/local/vravatar/costumes/' .
                    preg_quote($id, '~') . '\.glb"~',
                $html,
                $id
            );
            $this->assertStringContainsString(
                '<label class="local-vravatar-tile-label" for="local-vravatar-outfit-' . $id . '">',
                $html
            );
        }
        $this->assertMatchesRegularExpression('~id="local-vravatar-outfit-device-laptop"[^>]*\schecked>~', $html);
        $this->assertMatchesRegularExpression('~<img class="local-vravatar-costume-thumb" src="[^"]+" alt=""~', $html);
        $this->assertMatchesRegularExpression('~id="local-vravatar-hat-tricorne"\s+checked>~', $html);
        $this->assertStringNotContainsString('hat-pirate', $html);
        $this->assertStringContainsString('by Ian MacGillivray, CC BY 3.0', $html);
        $this->assertStringContainsString('by Robert Schlyter, CC BY 3.0', $html);
        $this->assertStringContainsString(get_string('costumecredits', 'local_vravatar'), $html);
        $this->assertStringContainsString('href="https://kenney.nl/assets/mini-characters"', $html);
        $this->assertStringContainsString('aria-describedby="local-vravatar-outfit-help"', $html);
    }
}
