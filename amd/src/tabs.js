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
 * The designer's tabs and the costume category filter.
 *
 * The page arrives as headed sections, one under the other (that is what a browser without
 * JavaScript shows). This turns them into WAI-ARIA tabs with automatic activation: the arrow keys,
 * Home and End move between the tabs, Tab goes into the panel. The section headings stay for screen
 * readers only, since the tab names the panel.
 *
 * @module     local_vravatar/tabs
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Turn the sections into tabs.
 *
 * @param {HTMLElement} root The designer.
 * @param {Function} onSelect Called with (panel, tab) whenever a tab is chosen (also the first one).
 * @param {number} [first] The tab shown first.
 * @returns {Object|null} {active() → the panel shown} or null without a tab list.
 */
export const setupTabs = (root, onSelect, first = 0) => {
    const list = root.querySelector('[data-region="tablist"]');
    if (!list) {
        return null;
    }
    const tabs = Array.from(list.querySelectorAll('[role="tab"]'));
    const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
    if (!tabs.length || panels.some((panel) => !panel)) {
        return null;
    }
    panels.forEach((panel, i) => {
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', tabs[i].id);
        panel.classList.add('local-vravatar-panel');
        const heading = panel.querySelector('.local-vravatar-section-heading');
        if (heading) {
            heading.classList.add('visually-hidden');
        }
    });

    let current = 0;
    const select = (index, focus) => {
        tabs.forEach((tab, i) => {
            const on = i === index;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            panels[i].hidden = !on;
        });
        current = index;
        if (focus) {
            tabs[index].focus();
        }
        onSelect(panels[index], tabs[index]);
    };

    tabs.forEach((tab, i) => {
        tab.addEventListener('click', () => select(i, false));
        tab.addEventListener('keydown', (e) => {
            let next = null;
            if (e.key === 'ArrowRight') {
                next = (i + 1) % tabs.length;
            } else if (e.key === 'ArrowLeft') {
                next = (i - 1 + tabs.length) % tabs.length;
            } else if (e.key === 'Home') {
                next = 0;
            } else if (e.key === 'End') {
                next = tabs.length - 1;
            }
            if (next !== null) {
                e.preventDefault();
                select(next, true);
            }
        });
    });
    list.hidden = false;
    select(Math.min(Math.max(first, 0), tabs.length - 1), false);
    return {
        active: () => panels[current],
    };
};

/**
 * Category buttons over the costumes: All, or one category at a time.
 *
 * @param {HTMLElement|null} fieldset The costume picker.
 * @param {string} label Accessible name of the group of buttons.
 * @param {string} all Name of the button that shows every category.
 */
export const setupCostumeFilter = (fieldset, label, all) => {
    if (!fieldset) {
        return;
    }
    const categories = Array.from(fieldset.querySelectorAll('.local-vravatar-costumecategory'));
    const help = fieldset.querySelector('#local-vravatar-outfit-help');
    if (categories.length < 2 || !help) {
        return;
    }
    const bar = document.createElement('div');
    bar.className = 'local-vravatar-chips';
    bar.setAttribute('role', 'group');
    bar.setAttribute('aria-label', label);
    const chips = [];
    const make = (text, target) => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'local-vravatar-chip';
        chip.textContent = text;
        chip.setAttribute('aria-pressed', target === null ? 'true' : 'false');
        chip.addEventListener('click', () => {
            chips.forEach((other) => other.setAttribute('aria-pressed', other === chip ? 'true' : 'false'));
            categories.forEach((category) => {
                category.hidden = target !== null && category !== target;
            });
        });
        chips.push(chip);
        bar.appendChild(chip);
    };
    make(all, null);
    categories.forEach((category) => {
        const legend = category.querySelector('legend');
        make(legend ? legend.textContent.trim() : '', category);
    });
    help.after(bar);
};

/**
 * Part buttons at the top of a tab with many choices: one part shown at a time (the first at the
 * start), as the costume categories. Each part's heading becomes its button's name and is hidden,
 * the button says which part is shown (aria-pressed).
 *
 * @param {HTMLElement} panel The tab's panel.
 * @param {string} label Accessible name of the group of buttons.
 * @param {Function} onShow Called with the panel after a part is shown.
 */
export const setupParts = (panel, label, onShow) => {
    const parts = Array.from(panel.querySelectorAll('[data-region="part"]'));
    if (parts.length < 2) {
        return;
    }
    const bar = document.createElement('div');
    bar.className = 'local-vravatar-chips local-vravatar-partchips';
    bar.setAttribute('role', 'group');
    bar.setAttribute('aria-label', label);
    const chips = [];
    const show = (target) => {
        parts.forEach((part, i) => {
            part.hidden = part !== target;
            chips[i].setAttribute('aria-pressed', part === target ? 'true' : 'false');
        });
    };
    parts.forEach((part) => {
        const heading = part.querySelector('.local-vravatar-partlabel');
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'local-vravatar-chip';
        chip.textContent = heading ? heading.textContent.trim() : '';
        chip.setAttribute('aria-controls', part.id);
        chip.addEventListener('click', () => {
            show(part);
            onShow(panel);
        });
        if (heading) {
            heading.classList.add('visually-hidden');
        }
        chips.push(chip);
        bar.appendChild(chip);
    });
    parts[0].before(bar);
    show(parts[0]);
};
