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

/**
 * The designer's coached start: one short tip at a time over the preview, naming the next step,
 * with the control it names glowing.
 *
 * Pick a look → change a part → press Save → "That's it" (which hides itself after a while). The
 * tips show on a person's first visit (no saved avatar) until they finish or close them; "?" shows
 * them again. Nothing has to be dismissed before acting. That the tips were seen is kept in a
 * cookie: Moodle clears the browser's local storage at every login.
 *
 * The same pattern as VR Makerspace's coached start (mod_vrmakerspace, amd/src/guide.js).
 *
 * @module     local_vravatar/coach
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** The cookie that says the tips were finished or closed. */
const COOKIE = 'local_vravatar_coached';

/** How long the last tip stays (ms). */
const DONE_MS = 9000;

/** The tab whose changes are "a look", not "a part". */
const LOOKS_TAB = 'local-vravatar-section-looks-tab';

/**
 * Whether the tips were finished or closed before.
 *
 * @returns {boolean}
 */
const seen = () => document.cookie.split(';').some((part) => part.trim() === COOKIE + '=1');

/**
 * Remember that the tips were finished or closed (a year).
 */
const remember = () => {
    document.cookie = COOKIE + '=1; path=/; max-age=31536000; SameSite=Lax';
};

/**
 * Start the coached start.
 *
 * @param {HTMLElement} root The designer.
 * @param {boolean} firstrun Whether the person has no saved avatar.
 * @returns {Object} {changed(tabid): a choice was made on that tab, saved(): the avatar was saved}
 */
export const setupCoach = (root, firstrun) => {
    const box = root.querySelector('[data-region="coach"]');
    const text = root.querySelector('[data-region="coachtext"]');
    const close = root.querySelector('[data-action="coachclose"]');
    const again = root.querySelector('[data-action="coachagain"]');
    const none = {changed: () => null, saved: () => null};
    if (!box || !text) {
        return none;
    }
    const targets = {
        look: () => root.querySelector('[data-action="preset"]'),
        parttab: () => root.querySelector('#local-vravatar-section-hair-tab'),
        save: () => root.querySelector('[data-action="save"]'),
    };
    let step = null;
    let timer = null;

    const unglow = () => root.querySelectorAll('.local-vravatar-glow').forEach((el) => el.classList.remove('local-vravatar-glow'));

    /**
     * Show one tip (or none).
     *
     * @param {string|null} key The tip.
     */
    const show = (key) => {
        step = key;
        unglow();
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }
        const hint = key ? box.querySelector(`[data-hint="${key}"]`) : null;
        if (!hint) {
            step = null;
            box.hidden = true;
            return;
        }
        text.textContent = hint.textContent.trim();
        box.hidden = false;
        const find = targets[hint.dataset.glow];
        const target = find ? find() : null;
        if (target) {
            target.classList.add('local-vravatar-glow');
        }
        if (key === 'done') {
            timer = window.setTimeout(() => show(null), DONE_MS);
        }
    };

    if (close) {
        close.addEventListener('click', () => {
            remember();
            show(null);
        });
    }
    if (again) {
        again.hidden = false;
        again.addEventListener('click', () => show('look'));
    }
    if (firstrun && !seen()) {
        show('look');
    }

    return {
        /**
         * A choice was made: a look moves the tips on to the parts, a part on to Save.
         *
         * @param {string} tabid Id of the tab the choice was made on.
         */
        changed(tabid) {
            if (step === 'look' || step === 'part') {
                show(tabid === LOOKS_TAB ? 'part' : 'save');
            }
        },

        /**
         * The avatar was saved.
         */
        saved() {
            if (step && step !== 'done') {
                remember();
                show('done');
            }
        },
    };
};
