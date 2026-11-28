# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- The face, hair and headwear keys of VR room's avatar modules (`avatarface`, `avatarhair`,
  `avatarheadwear`, `avatarhead`): `eyecolor`, `eyespace`, `browcolor`, `mouth`, `lips`,
  `earshape`, `freckles`, `cheeks`, `marks`, `lines`, `makeup`, `hairgrey`, `beardcolor`, `eyewear`,
  `eyewearcolor`, and many new values of `head`, `eyes`, `brows`, `nose`, `hair`, `beard` and
  `hat` (`design::MODULE_KEYS`, `MODULE_DEFAULTS`). New keys default to the look before them;
  `glasses` now follows `eyewear` (an old document with glasses wears `classic`).
- 50 costumes made in code by `avatarwardrobe` (`design::WARDROBE`): outfit values with no model
  and no asset, drawn live on the user's avatar in the designer, under 11 new costume categories.
  The traditional dress among them is drawn from general knowledge (wrap and closure directions
  included) and has not been reviewed by people who wear it.
- The designer's Face, Hair and Extras tabs are split into sections shown one at a time
  (`amd/src/tabs.js` `setupParts()`); costumes show one category at a time, opening on the
  category of the costume worn; tile pictures are drawn only for what is in view.
- The full-length preview with moving arms, walk and wave buttons; a "Bust a move" button
  (`data-action="demomove"`) that plays the avatar builder's moves (`avatar.move()`, VR room's
  `avatarmotion` `MOVES`), and moves played by themselves in the full-length view (not with
  `prefers-reduced-motion`). The wave in that view is the builder's wave move.
- `facelayout` (`balanced` by default; `higheyes`, `loweyes`, `longnose`, `compact`): the face's
  vertical layout, in the face tab's first section.
- Hats `piratehat` (skull and crossbones, feather) and `fez`.
- Accessory keys `neckwear`, `chest`, `earrings`, `hairflower`, `wristleft`, `wristright`,
  `ankleleft`, `ankleright`, `metal` and `fabric` (VR room's `avataraccessory`), in a new
  Accessories tab.
- Tattoo keys `tattoo1`..`tattoo4` with `…at`, `…size` and `…ink` (VR room's `avatartattoo`);
  `design::clean()` mirrors its `cleanTattoos()` and drops the sets the site does not allow
  (`allowtattoos`, default on; `allowculturaltattoos`, default off; `design::refused_tattoos()`).
- Hand style `natural` (the default, also for `design::standard()` and the looks).
- `local_vravatar_save_design` and `local_vravatar_get_design` return the new keys.

### Changed

- The six looks and `design::standard()` use the new choices, with cartoon hands in the skin tone.
- Names: `hair_long` "Long straight", `hat_cap` "Baseball cap", `beard` "Facial hair".
- The glasses checkbox is replaced by the eyewear tiles.

### Removed

- Hand styles `sphere` and `mitten`: `design::clean()` reads them as `natural`
  (`design::RETIRED_HANDS`), as VR room's `avatarhands` `HAND_FALLBACK`.
- The costume models `blocky-a` to `blocky-r`, `pirate-captain`, `pirate-woman` and `hat-pirate`
  (models, thumbnails, manifest entries, strings, `thirdpartylibs.xml` entries, credits), the
  `pirate` costume category and `costumes::HATS`. `design::clean()` reads old designs as
  `pirate-captain` → `captain`, `pirate-woman` → `deckhand`, `blocky-*` → `none` and hat `pirate` →
  `piratehat` (`RETIRED_OUTFITS`, `RETIRED_HATS`, mirroring avatarmodel's `cleanAppearance()`), so
  the designer and live rooms show them alike; the hook sends no asset for them.
- The one-piece costume models whose arms could not move: `business-dude`, `suit-tie`,
  `mini-male-a` to `-f`, `mini-female-a` to `-f`, `sunflower`, `cactus`, `alien-green`, `alien-pink`,
  `lalobot`, `robot-screen` (models, thumbnails, manifest entries, strings, `thirdpartylibs.xml`
  entries, README credits) and the costume categories `suit`, `plant`, `alien`, `robot` and
  `characters`. `design::clean()` reads them through `RETIRED_OUTFITS` (as avatarmodel's
  `cleanAppearance()`): `business-dude`, `suit-tie`, `mini-male-d` → `suit`; `mini-male-a`, `-b`,
  `mini-female-a`, `-b` → `summer`; `mini-male-c` → `police`; `mini-male-e` → `tracksuit`;
  `mini-male-f`, `mini-female-f` → `explorer`; `mini-female-c` → `skirt`; `mini-female-d`, `-e` →
  `office`; `alien-green`, `alien-pink` → `astronaut`; `sunflower`, `cactus`, `lalobot`,
  `robot-screen` → `none`. The four screen devices stay.

### Fixed

- The designer page passed its settings through `js_call_amd()` (over 1024 characters, a
  debugging warning on sites with developer debugging); they are now in a data attribute.

### Dependencies

- Needs the matching mod_vrroom (after 0.2.0) with those modules and `RETIRED_OUTFITS`;
  `version.php` and `composer.json` still name 0.2.0 and are raised with the release.

## [0.2.0] - 2026-11-27

### Added

- Appearance keys `hands` (sphere, mitten, cartoon, glove, robot, paw) and `handcolor` (skin or a
  named colour), and `face` (`plate`, the face screen, or `drawn`, on the skin). Defaults keep the
  0.1.x look.
- Six ready-made looks (`classes/local/presets.php`), Surprise me, Undo and Redo on the designer.
- A coached start: tips over the preview on a first visit (`amd/src/coach.js`; a cookie records
  that they were seen).
- `db/uninstall.php`: uninstalling removes every user's `local_vravatar_design` preference.

### Changed

- The designer shows its choices on tabs as picture tiles and large swatches (`amd/src/tabs.js`,
  `amd/src/tiles.js`), with a save bar that stays in view, an unsaved-changes warning, and a
  preview that frames the part being chosen and stays in view on phones.
- Users with no saved design get `design::standard()` in live rooms and from
  `local_vravatar_get_design`: a drawn face, short brown
  hair, a white accent, and a body colour and skin tone picked from the user id.
- Requires mod_vrroom 2026112700 (0.2.0), whose avatar builder draws the hand styles and faces;
  `composer.json` requires `adamjenkins/moodle-mod_vrroom` `^0.2`.

## [0.1.3] - 2026-11-15

### Added

- `composer.json` (package `adamjenkins/moodle-local_vravatar`, type `moodle-local`, requiring `moodle/moodle` `>=5.2 <5.4` and `moodle/composer-installer` `^1.0`, plus `adamjenkins/moodle-mod_vrroom` `^0.1`) for installing from Packagist.

## [0.1.2] - 2026-11-10

### Added

- Plugin icon `pix/icon.svg` (24x24 line icon of a person, house style), shown on `/admin/plugins.php`.

### Changed

- `$plugin->version` 2026111000, `$plugin->release` 0.1.2. No code changes.

## [0.1.1] - 2026-10-11

### Added

- Camp listing screenshots with captions (`.camp/screenshots/`, `screenshots:` in `.camp/listing.yml`).
- `.gitattributes` with export-ignore rules (`.github`, `.gitattributes`, `.gitignore`, `.camp`,
  `tests`, `node_modules`), which camp's release workflow requires; v0.1.0 failed to publish to
  camp without it.

### Changed

- `$plugin->version` 2026101100, `$plugin->release` 0.1.1. No code changes.

## [0.1.0] - 2026-10-11

ALPHA. First release, Moodle 5.2 and 5.3, requires mod_vrroom 2026101001.

### Added

- camp registry listing (`.camp/listing.yml`) and release workflow
  (`.github/workflows/camp-release.yml`).
- `index.php` "My VR avatar" designer (template `local_vravatar/designer`, AMD `local_vravatar/designer`
  drawing the preview with `vrroomruntime_builtin/live/avatarmodel`), linked through
  `local_vravatar_myprofile_navigation()`.
- `\local_vravatar\local\design`: the `vrroom-avatar/1` allowlist (`clean()`, 2 KB cap), stored in the
  user preference `local_vravatar_design`.
- Web services `local_vravatar_save_design` and `local_vravatar_get_design` (current user only,
  capability `local/vravatar:edit`).
- Listener of `\mod_vrroom\hook\avatar_appearance` filling each live-room peer's design and profile
  picture URL.
- Setting `local_vravatar/allowpicture` (default on).
- Privacy provider (user preference, live-room participants as an external location).
- PHPUnit tests, Behat feature (`@local_vravatar`; the WebGL preview scenario is `@local_vravatar_webgl`)
  and a GitHub Actions workflow.
- Hair and heads in `vrroom-avatar/1` (all optional, each defaulting to the earlier look): `hair`
  14 styles, `haircolor` 10 colours, `head` round/square/oval/heart, new `skin` (match + 8 tones),
  `eyes`, `brows`, `nose`, `ears` (flag), `beard` (in the hair colour), and the reserved `outfit`
  (only `none`). The designer page shows them under four headings; `local_vravatar_save_design` and
  `local_vravatar_get_design` return the new keys.
- Costumes: `costumes/<id>.glb` (45 models: 44 outfits of kind body or device, and the accessory
  `hat-pirate`), the manifest `costumes/costumes.json`, thumbnails `pix/costumes/<id>.png`,
  `\local_vravatar\local\costumes`, credits in `thirdpartylibs.xml` and the README. `outfit` takes
  `none` or a manifest id of kind body/device; `hat` gains `pirate`. The hook listener passes the
  models' URLs as `set_appearance()`'s `$assets` (`outfit`, `hat`); the designer has a Costume
  section (thumbnail radio grid by category, CC BY credits, a credits list) and passes the assets to
  the avatar builder. `outfit` is `PARAM_ALPHANUMEXT` in the web service structure and the no-JS
  form (costume ids have hyphens).
- Colours: `black` and `white` added to the body and accent colours (14 in all). The suits keep a
  black suit and white shirt and tint only the tie with the accent colour. The aliens have long
  fingers, the Kenney Mini characters stand straight, and the robot's lamps are larger and glow.
- Designer preview: frames only the visible parts (the hidden head no longer crops device
  costumes), fits wide costumes in the whole view, and reframes once a costume has loaded.
- Designer: sticky preview offset by Boost's `--navbar-height`; the face-view framing includes the
  name label; the zoom button is a toggle with `aria-pressed` (string `showface` removed).
- `$plugin->version` 2026101001 (new strings, templates and the mod_vrroom dependency).
