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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use local_vravatar\local\costumes;
use local_vravatar\local\design;
use local_vravatar\local\presets;

/**
 * The "My VR avatar" designer: the preview with its tools (presets, surprise me, undo), and the
 * choices as picture tiles and swatches (labelled radio groups) on six tabs: head and face, hair,
 * body, costume, hands, extras. Long lists of tiles come under the avatar builder's group headings.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer implements renderable, templatable {
    /**
     * The tabs of the designer, in page (and keyboard) order: lang string of the heading => keys.
     * A key in FLAGS is a checkbox (ears as a picture tile, picture as a plain box drawn by the
     * template in the Extras tab; glasses is not shown, it follows the eyewear), "outfit" the costume
     * picker (thumbnails by category), a colour key a row of large swatches and any other key a row
     * of picture tiles, under the avatar builder's group headings when the list is long. Without
     * JavaScript every tab is shown, one under the other, as headed sections.
     *
     * @var array
     */
    private const SECTIONS = [
        'section_head' => ['face', 'head', 'facelayout', 'skin', 'eyes', 'eyecolor', 'eyespace', 'brows', 'browcolor', 'nose',
            'mouth', 'lips', 'ears', 'earshape', 'cheeks', 'freckles', 'marks', 'lines', 'makeup'],
        'section_hair' => ['hair', 'haircolor', 'hairgrey', 'beard', 'beardcolor'],
        'section_body' => ['body', 'color', 'accent'],
        'section_costume' => ['outfit'],
        'section_hands' => ['hands', 'handcolor'],
        'section_extras' => ['hat', 'eyewear', 'eyewearcolor'],
        'section_accessories' => ['neckwear', 'chest', 'earrings', 'hairflower', 'wristleft', 'wristright', 'ankleleft',
            'ankleright', 'metal', 'fabric',
            'tattoo1', 'tattoo1at', 'tattoo1size', 'tattoo1ink',
            'tattoo2', 'tattoo2at', 'tattoo2size', 'tattoo2ink',
            'tattoo3', 'tattoo3at', 'tattoo3size', 'tattoo3ink',
            'tattoo4', 'tattoo4at', 'tattoo4size', 'tattoo4ink'],
    ];

    /**
     * The parts of a tab with many choices: lang string of the part (part_<id>) => its keys, in the
     * order of SECTIONS. The page shows one part at a time, picked with a row of buttons (as the
     * costume categories); without JavaScript every part is shown under its heading. A tab not
     * listed is one part with no heading.
     *
     * @var array
     */
    private const PARTS = [
        'section_head' => [
            'shape' => ['face', 'head', 'facelayout', 'skin'],
            'eyes' => ['eyes', 'eyecolor', 'eyespace'],
            'brows' => ['brows', 'browcolor'],
            'nosemouth' => ['nose', 'mouth', 'lips'],
            'ears' => ['ears', 'earshape'],
            'skindetails' => ['cheeks', 'freckles', 'marks', 'lines', 'makeup'],
        ],
        'section_hair' => [
            'hair' => ['hair', 'haircolor', 'hairgrey'],
            'facialhair' => ['beard', 'beardcolor'],
        ],
        'section_extras' => [
            'hats' => ['hat'],
            'glasses' => ['eyewear', 'eyewearcolor'],
        ],
        'section_accessories' => [
            'neck' => ['neckwear', 'chest'],
            'ears' => ['earrings', 'hairflower'],
            'wrists' => ['wristleft', 'wristright'],
            'ankles' => ['ankleleft', 'ankleright'],
            'metal' => ['metal', 'fabric'],
            'tattoo1' => ['tattoo1', 'tattoo1at', 'tattoo1size', 'tattoo1ink'],
            'tattoo2' => ['tattoo2', 'tattoo2at', 'tattoo2size', 'tattoo2ink'],
            'tattoo3' => ['tattoo3', 'tattoo3at', 'tattoo3size', 'tattoo3ink'],
            'tattoo4' => ['tattoo4', 'tattoo4at', 'tattoo4size', 'tattoo4ink'],
        ],
    ];

    /** @var string[] Parts with a line of help under their heading (lang string part_<id>_help). */
    private const PART_HELP = ['ankles', 'tattoo1'];

    /** @var int A list of tiles this long or longer is shown under its group headings. */
    private const GROUPED_FROM = 9;

    /**
     * How the preview frames each tab (data-frame): "face" a close-up of the head, "whole" the whole
     * avatar, "hands" the head, shoulders and raised hands.
     *
     * @var string[]
     */
    private const FRAMES = [
        'section_head' => 'face',
        'section_hair' => 'face',
        'section_body' => 'whole',
        'section_costume' => 'whole',
        'section_hands' => 'hands',
        'section_extras' => 'face',
        'section_accessories' => 'whole',
    ];

    /**
     * The short name (lang string) and icon (pix/tabs/) of each tab.
     *
     * @var array
     */
    private const TABS = [
        'section_head' => ['tab_head', 'face'],
        'section_hair' => ['tab_hair', 'hair'],
        'section_body' => ['tab_body', 'body'],
        'section_costume' => ['tab_costume', 'costume'],
        'section_hands' => ['tab_hands', 'hands'],
        'section_extras' => ['tab_extras', 'extras'],
        'section_accessories' => ['tab_accessories', 'accessories'],
    ];

    /**
     * How the picture on each tile of a key is drawn by the page (data-render): "face" the user's
     * avatar with that choice, seen close up; "whole" the whole avatar. Keys not listed have no
     * drawn picture (colours are swatches, hands are silhouettes).
     *
     * @var string[]
     */
    private const RENDERS = [
        'face' => 'face',
        'head' => 'face',
        'facelayout' => 'face',
        'eyes' => 'face',
        'eyespace' => 'face',
        'brows' => 'face',
        'nose' => 'face',
        'mouth' => 'face',
        'earshape' => 'face',
        'cheeks' => 'face',
        'freckles' => 'face',
        'marks' => 'face',
        'lines' => 'face',
        'makeup' => 'face',
        'beard' => 'face',
        'ears' => 'face',
        'hair' => 'face',
        'hairgrey' => 'face',
        'hat' => 'face',
        'eyewear' => 'face',
        'body' => 'whole',
        'neckwear' => 'bust',
        'chest' => 'bust',
        'earrings' => 'earring',
        'hairflower' => 'flower',
        'wristleft' => 'wristleft',
        'wristright' => 'wristright',
        'ankleleft' => 'ankleleft',
        'ankleright' => 'ankleright',
        'tattoo1' => 'tattoo',
        'tattoo2' => 'tattoo',
        'tattoo3' => 'tattoo',
        'tattoo4' => 'tattoo',
    ];

    /** @var string[] Lang string of the legend of each group of choices. */
    private const LEGENDS = [
        'body' => 'body',
        'head' => 'head',
        'facelayout' => 'facelayout',
        'skin' => 'skin',
        'color' => 'color',
        'accent' => 'accent',
        'hair' => 'hair',
        'haircolor' => 'haircolor',
        'hairgrey' => 'hairgrey',
        'eyes' => 'eyes',
        'eyecolor' => 'eyecolor',
        'eyespace' => 'eyespace',
        'brows' => 'brows',
        'browcolor' => 'browcolor',
        'nose' => 'nose',
        'mouth' => 'mouth',
        'lips' => 'lips',
        'earshape' => 'earshape',
        'cheeks' => 'cheeks',
        'freckles' => 'freckles',
        'marks' => 'marks',
        'lines' => 'lines',
        'makeup' => 'makeup',
        'beard' => 'beard',
        'beardcolor' => 'beardcolor',
        'hat' => 'hat',
        'eyewear' => 'eyewear',
        'eyewearcolor' => 'eyewearcolor',
        'hands' => 'hands',
        'handcolor' => 'handcolor',
        'face' => 'face',
        'neckwear' => 'neckwear',
        'chest' => 'chest',
        'earrings' => 'earrings',
        'hairflower' => 'hairflower',
        'wristleft' => 'wristleft',
        'wristright' => 'wristright',
        'ankleleft' => 'ankleleft',
        'ankleright' => 'ankleright',
        'metal' => 'metal',
        'fabric' => 'fabric',
        'tattoo1' => 'tattoodesign',
        'tattoo1at' => 'tattooat',
        'tattoo1size' => 'tattoosize',
        'tattoo1ink' => 'tattooink',
        'tattoo2' => 'tattoodesign',
        'tattoo2at' => 'tattooat',
        'tattoo2size' => 'tattoosize',
        'tattoo2ink' => 'tattooink',
        'tattoo3' => 'tattoodesign',
        'tattoo3at' => 'tattooat',
        'tattoo3size' => 'tattoosize',
        'tattoo3ink' => 'tattooink',
        'tattoo4' => 'tattoodesign',
        'tattoo4at' => 'tattooat',
        'tattoo4size' => 'tattoosize',
        'tattoo4ink' => 'tattooink',
    ];

    /**
     * Constructor.
     *
     * @param array $current The clean design shown in the form.
     * @param bool $hasdesign Whether the user has saved a design.
     * @param bool $pictureallowed Whether the site allows profile pictures on avatars.
     * @param bool $haspicture Whether the user has a profile picture to show.
     * @param \core\url $actionurl Where the form posts without JavaScript.
     */
    public function __construct(
        /** @var array The clean design shown in the form. */
        protected array $current,
        /** @var bool Whether the user has saved a design. */
        protected bool $hasdesign,
        /** @var bool Whether the site allows profile pictures on avatars. */
        protected bool $pictureallowed,
        /** @var bool Whether the user has a profile picture to show. */
        protected bool $haspicture,
        /** @var \core\url Where the form posts without JavaScript. */
        protected \core\url $actionurl,
    ) {
    }

    /**
     * Data for the template.
     *
     * @param renderer_base $output Renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $choices = design::choices();
        $sections = [];
        foreach (self::SECTIONS as $heading => $keys) {
            $items = [];
            foreach ($keys as $key) {
                if ($key === 'outfit') {
                    $items[] = ['costume' => $this->costume_group($output, $choices['outfit'])];
                } else if (in_array($key, design::FLAGS, true)) {
                    $items[] = ['check' => [
                        'name' => $key,
                        'id' => 'local-vravatar-' . $key,
                        'groupid' => 'local-vravatar-group-' . $key,
                        'legend' => get_string($key, 'local_vravatar'),
                        'label' => get_string($key . '_show', 'local_vravatar'),
                        'checked' => !empty($this->current[$key]),
                        'render' => self::RENDERS[$key] ?? '',
                    ]];
                } else {
                    $items[] = ['radio' => $this->radio_group($output, $key, $choices[$key])];
                }
            }
            $id = 'local-vravatar-' . str_replace('_', '-', $heading);
            [$tabstring, $icon] = self::TABS[$heading];
            $byname = array_combine($keys, $items);
            $parts = [];
            foreach (self::PARTS[$heading] ?? ['' => $keys] as $part => $partkeys) {
                if (str_starts_with($part, 'tattoo') && count($choices[$partkeys[0]]) < 2) {
                    // The site allows no tattoos: no tattoo parts.
                    continue;
                }
                $parts[] = [
                    'id' => $id . '-part-' . ($part === '' ? 'all' : $part),
                    'label' => $part === '' ? '' : get_string('part_' . $part, 'local_vravatar'),
                    'help' => in_array($part, self::PART_HELP, true) ? get_string('part_' . $part . '_help', 'local_vravatar') : '',
                    'items' => array_values(array_intersect_key($byname, array_flip($partkeys))),
                ];
            }
            $sections[] = [
                'id' => $id,
                'tabid' => $id . '-tab',
                'heading' => get_string($heading, 'local_vravatar'),
                'tablabel' => get_string($tabstring, 'local_vravatar'),
                'icon' => $output->image_url('tabs/' . $icon, 'local_vravatar')->out(false),
                'frame' => self::FRAMES[$heading],
                'first' => !$sections,
                'items' => $items,
                'parts' => $parts,
                'hasparts' => count($parts) > 1,
                'isextras' => $heading === 'section_extras',
                'ishands' => $heading === 'section_hands',
            ];
        }

        $presets = [];
        foreach (presets::for_page() as $preset) {
            $presets[] = [
                'id' => $preset['id'],
                'name' => $preset['name'],
                // Read by the page (data-design): only allowlisted names, true and false.
                'json' => json_encode($preset['design']),
            ];
        }

        $credits = $this->credits();
        return [
            'actionurl' => $this->actionurl->out(false),
            'sesskey' => sesskey(),
            'sections' => $sections,
            'glasses' => !empty($this->current['glasses']),
            'picture' => !empty($this->current['picture']),
            'pictureallowed' => $this->pictureallowed,
            'haspicture' => $this->haspicture,
            'hasdesign' => $this->hasdesign,
            'credits' => $credits,
            'hascredits' => (bool)$credits,
            'presets' => $presets,
            'haspresets' => (bool)$presets,
            'looksicon' => $output->image_url('tabs/looks', 'local_vravatar')->out(false),
        ];
    }

    /**
     * The costume picker: "No costume", then one group of tiles per category: first the groups of
     * the costumes the avatar builder makes in code (drawn live on the user's avatar, render
     * "whole"), then the categories of the costume models (thumbnails).
     *
     * Each costume is a radio button whose label holds its picture, its name and, when its licence
     * asks for it, the author's credit. data-model gives the designer's preview a model's address.
     *
     * @param renderer_base $output Renderer.
     * @param string[] $allowed Allowed values of "outfit": "none" and the costume ids.
     * @return array {name, id, legend, none: option, categories: [{id, legend, options}]}
     */
    protected function costume_group(renderer_base $output, array $allowed): array {
        // The costumes made in code come first, by their groups; then the models' categories.
        $wardrobe = [];
        foreach (design::WARDROBE as $group => $ids) {
            foreach ($ids as $id) {
                $wardrobe[$id] = $group;
            }
        }
        $bycategory = array_fill_keys(array_merge(array_keys(design::WARDROBE), costumes::CATEGORIES), []);
        foreach ($allowed as $id) {
            if (isset($wardrobe[$id])) {
                // Drawn live on the user's own avatar: no thumbnail, no model, no credit.
                $bycategory[$wardrobe[$id]][] = [
                    'value' => $id,
                    'id' => 'local-vravatar-outfit-' . $id,
                    'label' => get_string(costumes::name_string($id), 'local_vravatar'),
                    'checked' => $this->current['outfit'] === $id,
                    'thumb' => '',
                    'model' => '',
                    'credit' => '',
                    'kind' => 'body',
                    'render' => 'whole',
                ];
                continue;
            }
            $entry = costumes::get($id);
            if ($entry === null) {
                continue;
            }
            $bycategory[$entry['category']][] = [
                'value' => $id,
                'id' => 'local-vravatar-outfit-' . $id,
                'label' => get_string(costumes::name_string($id), 'local_vravatar'),
                'checked' => $this->current['outfit'] === $id,
                'thumb' => $output->image_url('costumes/' . $id, 'local_vravatar')->out(false),
                'model' => costumes::model_url($id)->out(false),
                'credit' => self::credit_line($entry),
                'kind' => $entry['kind'],
                'render' => '',
            ];
        }
        $categories = [];
        foreach ($bycategory as $category => $options) {
            if ($options) {
                $categories[] = [
                    'id' => 'local-vravatar-costumes-' . $category,
                    'legend' => get_string('costumecat_' . $category, 'local_vravatar'),
                    'options' => $options,
                ];
            }
        }
        return [
            'name' => 'outfit',
            'id' => 'local-vravatar-group-outfit',
            'legend' => get_string('outfit', 'local_vravatar'),
            'none' => [
                'value' => 'none',
                'id' => 'local-vravatar-outfit-none',
                'label' => get_string('outfit_none', 'local_vravatar'),
                'checked' => $this->current['outfit'] === 'none',
            ],
            'categories' => $categories,
        ];
    }

    /**
     * The small credit line of a costume whose licence asks for one ("by <author>, CC BY 3.0").
     *
     * @param array $entry Manifest entry.
     * @return string The line, or '' when the licence does not ask for a credit.
     */
    protected static function credit_line(array $entry): string {
        if (!costumes::needs_attribution($entry)) {
            return '';
        }
        return get_string('costumecredit', 'local_vravatar', (object)[
            'author' => $entry['author'],
            'licence' => costumes::licence_label($entry['licence']),
        ]);
    }

    /**
     * The credits of every costume model, for the list under the costume picker.
     *
     * Every costume is listed, whatever its licence: CC0 asks for no credit, but the authors get it.
     *
     * @return array list of {name, title, credit, source}
     */
    protected function credits(): array {
        $credits = [];
        foreach (costumes::all() as $id => $entry) {
            $credits[] = [
                'name' => get_string(costumes::name_string($id), 'local_vravatar'),
                'title' => $entry['title'],
                'credit' => get_string('costumecredit', 'local_vravatar', (object)[
                    'author' => $entry['author'],
                    'licence' => costumes::licence_label($entry['licence']),
                ]),
                'source' => $entry['source'],
            ];
        }
        return $credits;
    }

    /**
     * Swatches the page paints itself, for values with no colour in the builder's tables: the plain
     * dark pupil and the two-coloured eyes (ODD_EYES of the face module).
     *
     * @var array
     */
    private const PAGE_SWATCHES = [
        'eyecolor' => [
            'classic' => '#1b1b1d',
            'odd' => 'linear-gradient(90deg, #3d7bc4 50%, #6b4226 50%)',
        ],
    ];

    /**
     * What the values without a colour of their own follow (data-follows, painted by the page):
     * "body" the body colour, "skin" the skin colour, "hair" the hair colour and "greying" the hair
     * colour with grey in it.
     *
     * @var array
     */
    private const FOLLOWS = [
        'skin' => ['match' => 'body'],
        'handcolor' => ['skin' => 'skin'],
        'browcolor' => ['hair' => 'hair'],
        'beardcolor' => ['hair' => 'hair', 'greying' => 'greying'],
        'lips' => ['none' => 'skin', 'gloss' => 'skin'],
        'fabric' => ['accent' => 'accent'],
    ];

    /** @var string[] Metal colours (vrroomruntime_builtin/live/avataraccessory METALS). */
    private const METALSWATCHES = ['gold' => '#e8bd52', 'silver' => '#d9dde2', 'rosegold' => '#e8a88e', 'black' => '#3a3b40'];

    /** @var string[] Tattoo ink colours (vrroomruntime_builtin/live/avatartattoo INKS). */
    private const INKSWATCHES = ['black' => '#23252b', 'navy' => '#1f3466', 'green' => '#1f5a3c', 'red' => '#a3262e',
        'purple' => '#56307c', 'henna' => '#6b2a0e'];

    /**
     * One group of radio buttons for the template: large swatches for a colour, picture tiles for
     * anything else.
     *
     * A tile's picture is drawn by the page from the avatar builder (render), or is a fixed picture:
     * a hand style shows its silhouette (mask, painted
     * in the hand colour by the page). A long list of tiles comes in the avatar builder's groups,
     * each with a heading (none for the group of the "none" value alone).
     *
     * @param renderer_base $output Renderer.
     * @param string $key Document key.
     * @param string[] $values Allowed values, in order.
     * @return array {name, id, legend, isswatch, options: [{value, id, label, checked, swatch, follows,
     *     followed, palette, model, credit, pic, mask, render}], groups: [{id, legend, options}]}
     */
    protected function radio_group(renderer_base $output, string $key, array $values): array {
        [$swatches, $palette] = match ($key) {
            'color', 'accent' => [design::SWATCHES, 'colour'],
            'haircolor', 'browcolor', 'beardcolor' => [design::HAIRSWATCHES, 'hair'],
            'skin' => [design::SKINSWATCHES, 'skin'],
            'handcolor' => [design::SWATCHES, 'colour'],
            'eyecolor' => [design::EYESWATCHES, 'eye'],
            'lips' => [design::LIPSWATCHES, 'lips'],
            'eyewearcolor' => [design::FRAMESWATCHES, 'frame'],
            'fabric' => [design::SWATCHES, 'colour'],
            'metal' => [self::METALSWATCHES, 'metal'],
            'tattoo1ink', 'tattoo2ink', 'tattoo3ink', 'tattoo4ink' => [self::INKSWATCHES, 'ink'],
            default => [[], ''],
        };
        $isswatch = (bool)$swatches;
        $swatches += self::PAGE_SWATCHES[$key] ?? [];
        $follows = self::FOLLOWS[$key] ?? [];
        $options = [];
        foreach ($values as $value) {
            $pic = '';
            $mask = '';
            if ($key === 'hands') {
                $mask = $output->image_url('hands/' . $value, 'local_vravatar')->out(false);
            }
            $options[$value] = [
                'value' => $value,
                'id' => 'local-vravatar-' . $key . '-' . $value,
                'label' => get_string(self::option_string($key, $value), 'local_vravatar'),
                'checked' => $this->current[$key] === $value,
                'swatch' => $swatches[$value] ?? '',
                // The values "match", "skin", "hair" ... have no colour of their own: the page paints the one they follow.
                'follows' => $isswatch && isset($follows[$value]),
                'followed' => $isswatch ? ($follows[$value] ?? '') : '',
                'palette' => $palette,
                'model' => '',
                'credit' => '',
                'pic' => $pic,
                'mask' => $mask,
                'render' => ($pic === '' && $mask === '') ? (self::RENDERS[$key] ?? '') : '',
            ];
        }
        return [
            'name' => $key,
            'id' => 'local-vravatar-group-' . $key,
            'legend' => get_string(self::LEGENDS[$key], 'local_vravatar'),
            'isswatch' => $isswatch,
            'options' => array_values($options),
            'groups' => $this->option_groups($key, $options, !$isswatch),
        ];
    }

    /**
     * The options of a radio group in their headed groups: the avatar builder's groups for a long
     * list of tiles, else one group without a heading.
     *
     * @param string $key Document key.
     * @param array $options value => option, in order.
     * @param bool $grouped Whether the list may be grouped (tiles, not swatches).
     * @return array list of {id, legend, options}
     */
    protected function option_groups(string $key, array $options, bool $grouped): array {
        $groups = design::groups($key);
        // Headings only where there are two to tell apart (the "none" group has none).
        $headed = array_diff(array_keys($groups), ['none']);
        if (!$grouped || count($headed) < 2 || count($options) < self::GROUPED_FROM) {
            return [['id' => 'local-vravatar-group-' . $key . '-all', 'legend' => '', 'options' => array_values($options)]];
        }
        $list = [];
        foreach ($groups as $group => $values) {
            $members = array_values(array_intersect_key($options, array_flip($values)));
            if ($members) {
                // Keys that share their values share the strings of their groups too.
                $stem = preg_replace(['/^tattoo[1-4]/', '/^(wrist|ankle)(left|right)$/'], ['tattoo', '$1'], $key);
                $list[] = [
                    'id' => 'local-vravatar-group-' . $key . '-' . $group,
                    // The group of "none" alone needs no heading.
                    'legend' => $group === 'none' ? '' : get_string($stem . 'group_' . $group, 'local_vravatar'),
                    // A tattoo tradition says what it is and where it is from.
                    'help' => $stem === 'tattoo' && !in_array($group, ['none', 'generic'], true) ?
                        get_string('tattoogroup_' . $group . '_help', 'local_vravatar') : '',
                    'options' => $members,
                ];
            }
            $options = array_diff_key($options, array_flip($values));
        }
        if ($options) {
            // Values of the first versions a module no longer lists: at the end, as in the allowlist.
            $list[] = ['id' => 'local-vravatar-group-' . $key . '-more', 'legend' => '', 'options' => array_values($options)];
        }
        return $list;
    }

    /**
     * The lang string naming one value of one key.
     *
     * @param string $key Document key.
     * @param string $value Allowed value.
     * @return string String identifier.
     */
    public static function option_string(string $key, string $value): string {
        return match ($key) {
            'color', 'accent' => 'colour_' . $value,
            'haircolor' => 'haircolour_' . $value,
            'browcolor' => $value === 'hair' ? 'browcolour_hair' : 'haircolour_' . $value,
            'beardcolor' => in_array($value, ['hair', 'greying'], true) ? 'beardcolour_' . $value : 'haircolour_' . $value,
            'eyecolor' => 'eyecolour_' . $value,
            'eyewearcolor' => 'framecolour_' . $value,
            'handcolor' => $value === 'skin' ? 'handcolour_skin' : 'colour_' . $value,
            'skin' => 'skin_' . $value,
            'wristleft', 'wristright' => 'wrist_' . $value,
            'ankleleft', 'ankleright' => 'ankle_' . $value,
            'fabric' => $value === 'accent' ? 'fabric_accent' : 'colour_' . $value,
            default => preg_match('/^tattoo[1-4](at|size|ink)?$/', $key, $m) ? 'tattoo' . ($m[1] ?? '') . '_' . $value :
                $key . '_' . $value,
        };
    }
}
