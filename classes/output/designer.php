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
 * body, costume, hands, extras.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer implements renderable, templatable {
    /**
     * The tabs of the designer, in page (and keyboard) order: lang string of the heading => keys.
     * A key in FLAGS is a checkbox (ears and glasses as picture tiles, picture as a plain box drawn
     * by the template in the Extras tab), "outfit" the costume picker (thumbnails by category), a
     * colour key a row of large swatches and any other key a row of picture tiles. Without
     * JavaScript every tab is shown, one under the other, as headed sections.
     *
     * @var array
     */
    private const SECTIONS = [
        'section_head' => ['head', 'skin', 'eyes', 'brows', 'nose', 'ears', 'beard'],
        'section_hair' => ['hair', 'haircolor'],
        'section_body' => ['body', 'color', 'accent'],
        'section_costume' => ['outfit'],
        'section_hands' => ['hands', 'handcolor'],
        'section_extras' => ['hat', 'glasses'],
    ];

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
    ];

    /**
     * How the picture on each tile of a key is drawn by the page (data-render): "face" the user's
     * avatar with that choice, seen close up; "whole" the whole avatar. Keys not listed have no
     * drawn picture (colours are swatches, hands are silhouettes).
     *
     * @var string[]
     */
    private const RENDERS = [
        'head' => 'face',
        'eyes' => 'face',
        'brows' => 'face',
        'nose' => 'face',
        'beard' => 'face',
        'ears' => 'face',
        'hair' => 'face',
        'hat' => 'face',
        'glasses' => 'face',
        'body' => 'whole',
    ];

    /** @var string[] Lang string of the legend of each group of choices. */
    private const LEGENDS = [
        'body' => 'body',
        'head' => 'head',
        'skin' => 'skin',
        'color' => 'color',
        'accent' => 'accent',
        'hair' => 'hair',
        'haircolor' => 'haircolor',
        'eyes' => 'eyes',
        'brows' => 'brows',
        'nose' => 'nose',
        'beard' => 'beard',
        'hat' => 'hat',
        'hands' => 'hands',
        'handcolor' => 'handcolor',
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
            $sections[] = [
                'id' => $id,
                'tabid' => $id . '-tab',
                'heading' => get_string($heading, 'local_vravatar'),
                'tablabel' => get_string($tabstring, 'local_vravatar'),
                'icon' => $output->image_url('tabs/' . $icon, 'local_vravatar')->out(false),
                'frame' => self::FRAMES[$heading],
                'first' => !$sections,
                'items' => $items,
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
     * The costume picker: "No costume", then one group of thumbnails per category.
     *
     * Each costume is a radio button whose label holds its thumbnail, its name and, when its licence
     * asks for it, the author's credit. data-model gives the designer's preview the model's address.
     *
     * @param renderer_base $output Renderer.
     * @param string[] $allowed Allowed values of "outfit": "none" and the costume ids.
     * @return array {name, id, legend, none: option, categories: [{id, legend, options}]}
     */
    protected function costume_group(renderer_base $output, array $allowed): array {
        $bycategory = array_fill_keys(costumes::CATEGORIES, []);
        foreach ($allowed as $id) {
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
     * One group of radio buttons for the template: large swatches for a colour, picture tiles for
     * anything else.
     *
     * A tile's picture is drawn by the page from the avatar builder (render), or is a fixed picture:
     * a hat that is a costume model shows its thumbnail, a hand style its silhouette (mask, painted
     * in the hand colour by the page).
     *
     * @param renderer_base $output Renderer.
     * @param string $key Document key.
     * @param string[] $values Allowed values, in order.
     * @return array {name, id, legend, isswatch, render, options: [{value, id, label, checked, swatch,
     *     palette, model, credit, pic, mask}]}
     */
    protected function radio_group(renderer_base $output, string $key, array $values): array {
        [$swatches, $palette] = match ($key) {
            'color', 'accent' => [design::SWATCHES, 'colour'],
            'haircolor' => [design::HAIRSWATCHES, 'hair'],
            'skin' => [design::SKINSWATCHES, 'skin'],
            'handcolor' => [design::SWATCHES, 'colour'],
            default => [[], ''],
        };
        $isswatch = (bool)$swatches;
        $options = [];
        foreach ($values as $value) {
            // A hat that is a costume model: the preview loads it, and its credit is shown.
            $hatcostume = $key === 'hat' && isset(costumes::HATS[$value]) ? costumes::get(costumes::HATS[$value]) : null;
            $pic = '';
            $mask = '';
            if ($hatcostume) {
                $pic = $output->image_url('costumes/' . $hatcostume['id'], 'local_vravatar')->out(false);
            } else if ($key === 'hands') {
                $mask = $output->image_url('hands/' . $value, 'local_vravatar')->out(false);
            }
            $options[] = [
                'value' => $value,
                'id' => 'local-vravatar-' . $key . '-' . $value,
                'label' => get_string(self::option_string($key, $value), 'local_vravatar'),
                'checked' => $this->current[$key] === $value,
                'swatch' => $swatches[$value] ?? '',
                // The values "match" and "skin" have no colour of their own: the page paints the one they follow.
                'follows' => $isswatch && !isset($swatches[$value]),
                'palette' => $palette,
                'model' => $hatcostume ? costumes::model_url($hatcostume['id'])->out(false) : '',
                'credit' => $hatcostume ? self::credit_line($hatcostume) : '',
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
            'options' => $options,
        ];
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
            'handcolor' => $value === 'skin' ? 'handcolour_skin' : 'colour_' . $value,
            'skin' => 'skin_' . $value,
            default => $key . '_' . $value,
        };
    }
}
