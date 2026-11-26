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
 * Tests for the ready-made looks.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(presets::class)]
final class presets_test extends \advanced_testcase {
    /**
     * Every preset holds only allowed values (it survives cleaning unchanged), never sets the skin
     * tone or the profile picture, and has a name.
     */
    public function test_presets_are_clean(): void {
        $all = presets::all();
        $this->assertNotEmpty($all);
        foreach ($all as $id => $preset) {
            // Every key of the source list survived cleaning (a misspelt value would be dropped).
            $this->assertCount(15, $preset, $id);
            $this->assertArrayNotHasKey('skin', $preset, $id);
            $this->assertArrayNotHasKey('picture', $preset, $id);
            $clean = design::clean($preset);
            foreach ($preset as $key => $value) {
                $this->assertSame($value, $clean[$key], "{$id}.{$key}");
            }
            $this->assertStringNotContainsString('[[', get_string('preset_' . $id, 'local_vravatar'));
        }
        $page = presets::for_page();
        $this->assertSame(array_keys($all), array_column($page, 'id'));
    }
}
