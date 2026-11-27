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

namespace local_vravatar;

use local_vravatar\local\design;

/**
 * Tests for the uninstall step.
 *
 * @package    local_vravatar
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('xmldb_local_vravatar_uninstall')]
final class uninstall_test extends \advanced_testcase {
    /**
     * Uninstalling removes every saved design and leaves other preferences alone.
     */
    public function test_uninstall_removes_designs(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/vravatar/db/uninstall.php');
        $this->resetAfterTest();
        $a = $this->getDataGenerator()->create_user();
        $b = $this->getDataGenerator()->create_user();
        design::save((int)$a->id, ['body' => 'slim']);
        design::save((int)$b->id, ['body' => 'broad']);
        set_user_preference('htmleditor', 'textarea', $a);

        $this->assertTrue(xmldb_local_vravatar_uninstall());

        $this->assertSame(0, $DB->count_records('user_preferences', ['name' => design::PREFERENCE]));
        $this->assertSame('textarea', get_user_preferences('htmleditor', null, $a));
    }
}
