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

namespace local_vravatar\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_vravatar\local\design;

/**
 * Save the current user's avatar design.
 *
 * There is no user id parameter: a user saves only their own design.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_design extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'design' => new external_value(PARAM_RAW, 'The vrroom-avatar/1 document as JSON; cleaned against the allowlist'),
        ]);
    }

    /**
     * Save the design.
     *
     * While the site does not allow profile pictures on avatars the picture choice is not changed:
     * the choice saved before is kept for when the site allows it again.
     *
     * @param string $design The document as JSON.
     * @return array
     */
    public static function execute(string $design): array {
        $params = self::validate_parameters(self::execute_parameters(), ['design' => $design]);
        $userid = self::require_own_avatar();

        $clean = design::clean((string)$params['design']);
        $allowed = design::picture_allowed();
        if (!$allowed) {
            $clean['picture'] = (bool)(design::load($userid)['picture'] ?? false);
        }
        $saved = design::save($userid, $clean);
        return ['design' => $saved, 'pictureallowed' => $allowed];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'design' => self::design_structure(),
            'pictureallowed' => new external_value(PARAM_BOOL, 'Whether the site lets users show their profile picture'),
        ]);
    }

    /**
     * Check that the current user may design their own avatar.
     *
     * @return int The current user's id.
     */
    public static function require_own_avatar(): int {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('noguest');
        }
        $context = \context_user::instance((int)$USER->id);
        self::validate_context($context);
        require_capability('local/vravatar:edit', $context);
        return (int)$USER->id;
    }

    /**
     * The vrroom-avatar/1 document.
     *
     * @return external_single_structure
     */
    public static function design_structure(): external_single_structure {
        return new external_single_structure([
            'schema' => new external_value(PARAM_RAW, 'Schema name, vrroom-avatar/1'),
            'body' => new external_value(PARAM_ALPHA, 'Body shape'),
            'head' => new external_value(PARAM_ALPHA, 'Head shape'),
            'skin' => new external_value(PARAM_ALPHA, 'Skin tone name, or match for the body colour'),
            'color' => new external_value(PARAM_ALPHA, 'Body colour name'),
            'accent' => new external_value(PARAM_ALPHA, 'Accent colour name'),
            'hair' => new external_value(PARAM_ALPHA, 'Hair style'),
            'haircolor' => new external_value(PARAM_ALPHA, 'Hair colour name'),
            'eyes' => new external_value(PARAM_ALPHA, 'Eyes'),
            'brows' => new external_value(PARAM_ALPHA, 'Eyebrows'),
            'nose' => new external_value(PARAM_ALPHA, 'Nose'),
            'ears' => new external_value(PARAM_BOOL, 'Ears'),
            'beard' => new external_value(PARAM_ALPHA, 'Beard'),
            'glasses' => new external_value(PARAM_BOOL, 'Glasses: true when the eyewear is not none'),
            'hat' => new external_value(PARAM_ALPHA, 'Hat'),
            'outfit' => new external_value(PARAM_ALPHANUMEXT, 'Costume id (lower case, digits, hyphens), or none'),
            'hands' => new external_value(PARAM_ALPHA, 'Hand style'),
            'handcolor' => new external_value(PARAM_ALPHA, 'Hand colour name, or skin for the skin tone'),
            'face' => new external_value(PARAM_ALPHA, 'Face style: plate (a face screen) or drawn (on the skin)'),
            'eyecolor' => new external_value(PARAM_ALPHA, 'Eye colour, or classic for a plain dark pupil'),
            'eyespace' => new external_value(PARAM_ALPHA, 'Eye spacing'),
            'browcolor' => new external_value(PARAM_ALPHA, 'Eyebrow colour, or hair for the hair colour'),
            'mouth' => new external_value(PARAM_ALPHA, 'Mouth'),
            'lips' => new external_value(PARAM_ALPHA, 'Lip colour, or none'),
            'earshape' => new external_value(PARAM_ALPHA, 'Ear shape'),
            'freckles' => new external_value(PARAM_ALPHA, 'Freckles'),
            'cheeks' => new external_value(PARAM_ALPHA, 'Cheeks'),
            'marks' => new external_value(PARAM_ALPHA, 'Marks on the face'),
            'lines' => new external_value(PARAM_ALPHA, 'Age and expression lines'),
            'makeup' => new external_value(PARAM_ALPHA, 'Make-up'),
            'facelayout' => new external_value(PARAM_ALPHA, 'Face layout: how high the eyes, nose and mouth sit'),
            'beardcolor' => new external_value(PARAM_ALPHA, 'Facial hair colour, or hair (or greying) for the hair colour'),
            'hairgrey' => new external_value(PARAM_ALPHA, 'Grey in the hair'),
            'eyewear' => new external_value(PARAM_ALPHA, 'Eyewear, or none'),
            'eyewearcolor' => new external_value(PARAM_ALPHA, 'Eyewear frame colour'),
            'neckwear' => new external_value(PARAM_ALPHA, 'Neckwear, or none'),
            'chest' => new external_value(PARAM_ALPHA, 'Pin, badge or flower on the chest, or none'),
            'earrings' => new external_value(PARAM_ALPHA, 'Earrings, or none'),
            'hairflower' => new external_value(PARAM_ALPHA, 'Flower behind the ear, or none'),
            'wristleft' => new external_value(PARAM_ALPHA, 'On the left wrist, or none'),
            'wristright' => new external_value(PARAM_ALPHA, 'On the right wrist, or none'),
            'ankleleft' => new external_value(PARAM_ALPHA, 'On the left ankle, or none'),
            'ankleright' => new external_value(PARAM_ALPHA, 'On the right ankle, or none'),
            'metal' => new external_value(PARAM_ALPHA, 'Metal of the jewellery'),
            'fabric' => new external_value(PARAM_ALPHA, 'Colour of fabric accessories, or accent for the accent colour'),
        ] + self::tattoo_structure() + [
            'picture' => new external_value(PARAM_BOOL, 'Show the profile picture on the avatar'),
        ]);
    }

    /**
     * The four tattoo slots of the document.
     *
     * @return external_value[]
     */
    private static function tattoo_structure(): array {
        $fields = [];
        for ($n = 1; $n <= design::TATTOO_SLOTS; $n++) {
            $fields['tattoo' . $n] = new external_value(PARAM_ALPHA, 'Tattoo ' . $n . ': design, or none');
            $fields['tattoo' . $n . 'at'] = new external_value(PARAM_ALPHA, 'Tattoo ' . $n . ': where it is');
            $fields['tattoo' . $n . 'size'] = new external_value(PARAM_ALPHA, 'Tattoo ' . $n . ': size');
            $fields['tattoo' . $n . 'ink'] = new external_value(PARAM_ALPHA, 'Tattoo ' . $n . ': ink colour');
        }
        return $fields;
    }
}
