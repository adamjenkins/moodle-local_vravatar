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
use mod_vrroom\hook\avatar_appearance;

/**
 * Tests of the listener that gives live rooms the users' avatar designs.
 *
 * @package    local_vravatar
 * @category   test
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(hook_listener::class)]
final class hook_listener_test extends \advanced_testcase {
    /** @var \stdClass User with a design and a profile picture, who chose to show it. */
    private \stdClass $withpicture;

    /** @var \stdClass User with a design who did not choose the picture. */
    private \stdClass $nopicturechosen;

    /** @var \stdClass User without a design. */
    private \stdClass $nodesign;

    /** @var \stdClass User with a design who chose the picture but has none. */
    private \stdClass $haspictureless;

    /** @var \context Context of the hook. */
    private \context $context;

    /**
     * Users and designs of the tests.
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        $this->resetAfterTest();
        $CFG->enablegravatar = 0;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->context = \context_course::instance($course->id);

        $this->withpicture = $generator->create_user();
        $DB->set_field('user', 'picture', 7, ['id' => $this->withpicture->id]);
        design::save((int)$this->withpicture->id, ['color' => 'red', 'picture' => true]);

        $this->nopicturechosen = $generator->create_user();
        $DB->set_field('user', 'picture', 8, ['id' => $this->nopicturechosen->id]);
        design::save((int)$this->nopicturechosen->id, ['color' => 'green', 'hat' => 'cap', 'picture' => false]);

        $this->nodesign = $generator->create_user();

        $this->haspictureless = $generator->create_user();
        design::save((int)$this->haspictureless->id, ['color' => 'yellow', 'picture' => true]);
    }

    /**
     * A hook for one peer per test user.
     *
     * @return avatar_appearance
     */
    private function hook(): avatar_appearance {
        return new avatar_appearance([
            ['peerid' => 'peera', 'userid' => (int)$this->withpicture->id],
            ['peerid' => 'peerb', 'userid' => (int)$this->nopicturechosen->id],
            ['peerid' => 'peerc', 'userid' => (int)$this->nodesign->id],
            ['peerid' => 'peerd', 'userid' => (int)$this->haspictureless->id],
        ], $this->context);
    }

    /**
     * Users with a design get it, the others the standard look; the picture only for the user who chose it and has one.
     */
    public function test_fills_designs_and_pictures(): void {
        // As in a live room: a logged-in user asks (with forced login, others get the default picture).
        $this->setUser($this->nodesign);
        $hook = $this->hook();
        hook_listener::avatar_appearance($hook);
        $appearances = $hook->get_appearances();

        $this->assertEqualsCanonicalizing(['peera', 'peerb', 'peerc', 'peerd'], array_keys($appearances));
        // No saved design: the standard look, drawn face, no picture.
        $this->assertSame(design::standard((int)$this->nodesign->id), $appearances['peerc']['appearance']);
        $this->assertSame('drawn', $appearances['peerc']['appearance']['face']);
        $this->assertFalse($appearances['peerc']['appearance']['picture']);
        $this->assertSame('', $appearances['peerc']['picture']);

        $this->assertSame('red', $appearances['peera']['appearance']['color']);
        $this->assertTrue($appearances['peera']['appearance']['picture']);
        $this->assertStringContainsString('/user/icon/', $appearances['peera']['picture']);
        $this->assertStringContainsString('rev=7', $appearances['peera']['picture']);

        $this->assertSame('green', $appearances['peerb']['appearance']['color']);
        $this->assertSame('cap', $appearances['peerb']['appearance']['hat']);
        $this->assertFalse($appearances['peerb']['appearance']['picture']);
        $this->assertSame('', $appearances['peerb']['picture']);

        // Chose the picture but has none: no badge.
        $this->assertSame('yellow', $appearances['peerd']['appearance']['color']);
        $this->assertFalse($appearances['peerd']['appearance']['picture']);
        $this->assertSame('', $appearances['peerd']['picture']);
    }

    /**
     * A costume model reaches the room as the address of its model; a design saved with a retired
     * pirate model and the pirate hat (stored before the retirements) arrives as the pirate captain
     * and the pirate tricorne made in code, with no assets; a design without models gives no assets.
     */
    public function test_costume_assets(): void {
        // Stored as 0.2.0 wrote it, not through save() (which would clean it now).
        set_user_preference(
            design::PREFERENCE,
            '{"schema":"vrroom-avatar/1","color":"red","picture":true,"outfit":"pirate-captain","hat":"pirate"}',
            (int)$this->withpicture->id
        );
        design::save((int)$this->haspictureless->id, ['outfit' => 'device-monitor']);
        $hook = $this->hook();
        hook_listener::avatar_appearance($hook);
        $appearances = $hook->get_appearances();

        $this->assertSame('captain', $appearances['peera']['appearance']['outfit']);
        $this->assertSame('piratehat', $appearances['peera']['appearance']['hat']);
        $this->assertSame('red', $appearances['peera']['appearance']['color']);
        $this->assertSame([], $appearances['peera']['assets']);

        $this->assertSame(
            ['outfit' => (new \moodle_url('/local/vravatar/costumes/device-monitor.glb'))->out(false)],
            $appearances['peerd']['assets']
        );
        // A drawn hat (cap) and no costume: no assets.
        $this->assertSame([], $appearances['peerb']['assets']);
    }

    /**
     * Designs saved with the other retired costumes reach the room with their replacements and no
     * assets: the pirate model "pirate-woman" as the deckhand, a Blocky character as the plain clothes.
     */
    public function test_retired_costumes_reach_the_room(): void {
        set_user_preference(
            design::PREFERENCE,
            '{"schema":"vrroom-avatar/1","outfit":"pirate-woman"}',
            (int)$this->withpicture->id
        );
        set_user_preference(
            design::PREFERENCE,
            '{"schema":"vrroom-avatar/1","outfit":"blocky-k","color":"green"}',
            (int)$this->haspictureless->id
        );
        $hook = $this->hook();
        hook_listener::avatar_appearance($hook);
        $appearances = $hook->get_appearances();

        $this->assertSame('deckhand', $appearances['peera']['appearance']['outfit']);
        $this->assertSame([], $appearances['peera']['assets']);
        $this->assertSame('none', $appearances['peerd']['appearance']['outfit']);
        $this->assertSame('green', $appearances['peerd']['appearance']['color']);
        $this->assertSame([], $appearances['peerd']['assets']);
    }

    /**
     * With the site setting off nobody gets a picture, but the designs still arrive.
     */
    public function test_no_pictures_when_not_allowed(): void {
        set_config('allowpicture', 0, 'local_vravatar');
        $hook = $this->hook();
        hook_listener::avatar_appearance($hook);
        $appearances = $hook->get_appearances();

        $this->assertSame('red', $appearances['peera']['appearance']['color']);
        $this->assertFalse($appearances['peera']['appearance']['picture']);
        $this->assertSame('', $appearances['peera']['picture']);
        foreach ($appearances as $entry) {
            $this->assertSame('', $entry['picture']);
        }
    }

    /**
     * A tampered preference reaches the room only as clean allowlisted values.
     */
    public function test_tampered_design_cleaned(): void {
        global $DB;
        $DB->set_field('user_preferences', 'value', '{"color":"<img src=x>","body":"slim","name":"Evil"}', [
            'userid' => $this->nopicturechosen->id,
            'name' => design::PREFERENCE,
        ]);
        $hook = $this->hook();
        hook_listener::avatar_appearance($hook);
        $appearance = $hook->get_appearances()['peerb']['appearance'];
        $this->assertSame('slim', $appearance['body']);
        $this->assertSame('blue', $appearance['color']);
        $this->assertArrayNotHasKey('name', $appearance);
    }

    /**
     * Nothing happens for an empty room; a room of people without designs gets their standard looks.
     */
    public function test_rooms_without_designs(): void {
        $hook = new avatar_appearance([], $this->context);
        hook_listener::avatar_appearance($hook);
        $this->assertSame([], $hook->get_appearances());

        $hook = new avatar_appearance([['peerid' => 'peerc', 'userid' => (int)$this->nodesign->id]], $this->context);
        hook_listener::avatar_appearance($hook);
        $appearances = $hook->get_appearances();
        $this->assertSame(['peerc'], array_keys($appearances));
        $this->assertSame(design::standard((int)$this->nodesign->id), $appearances['peerc']['appearance']);
    }

    /**
     * The listener is registered: dispatching the hook through the hook manager fills it.
     */
    public function test_registered_with_hook_manager(): void {
        $hook = $this->hook();
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertArrayHasKey('peera', $hook->get_appearances());
        // A person without a saved design gets the standard look through the hook manager too.
        $this->assertSame(design::standard((int)$this->nodesign->id), $hook->get_appearances()['peerc']['appearance']);
    }
}
