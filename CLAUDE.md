# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

An Alfred (macOS) workflow written in PHP that controls Spotify, backed by a local SQLite mirror of the user's library and the Spotify Web API. Two independent pieces live in this repo:

- `spotify-mini-player/` — the Alfred workflow itself (all the PHP).
- `docs/` — the Jekyll site published at alfred-spotify-mini-player.com.

## Development setup

There is no build step and no test suite. The workflow runs directly from the working copy: `~/Documents/Alfred.alfredpreferences/workflows/spotify-mini-player` is a **symlink** to `spotify-mini-player/`, so edits to `src/*.php` take effect on the next Alfred invocation. Reinstalling from a release replaces the symlink with a real directory — re-create the symlink after doing that.

```bash
# Dependencies (vendor/ is committed; only needed when changing composer.json)
cd spotify-mini-player && php composer.phar install

# Syntax-check a file after editing
php -l src/functions.php

# Run a script filter the way Alfred does (must be cwd = spotify-mini-player/,
# and the alfred_workflow_data / alfred_workflow_cache env vars must be set)
php -r 'require "./src/main.php"; main(array("", "Playlist▹"));'

# Tail the workflow log
tail -f ~/Library/Caches/com.runningwithcrayons.Alfred/Workflow\ Data/com.vdesabou.spotify.mini.player/action.log
```

PHP 5.5+ is required (macOS Monterey and later no longer ship PHP — `brew install php`).

Runtime state lives outside the repo:
- data: `~/Library/Application Support/Alfred/Workflow Data/com.vdesabou.spotify.mini.player/` (`library.db`, `fetch_artworks.db`, `artwork/`, `history.json`, `playqueue.json`, `*_in_progress` lock files)
- cache/log: `~/Library/Caches/com.runningwithcrayons.Alfred/Workflow Data/com.vdesabou.spotify.mini.player/`

## Releasing

Releases are automated by `.github/workflows/create-release.yml`, triggered by **closing a GitHub milestone**. The milestone title is the version number: the action rewrites `version` in `info.plist`, commits it to master, generates release notes from the milestone's issues, zips `spotify-mini-player/` into `spotifyminiplayer.alfredworkflow`, and opens a Gallery update issue. Don't bump the version by hand.

## Architecture

### Entry points

`info.plist` is the Alfred graph (~430 KB of XML). Its script nodes are thin shims into `src/`:

- **Script filters** (what you see while typing) → `src/main.php::main($argv)`; debug tools → `src/debug.php::main($argv)`.
- **Run Script actions** (what happens on Enter) → `src/action.php::main($query, $type, $add_to_option)` where `$type` is `TRACK`, `ALBUM`, `ONLINE`, `ADDTOQUEUE`, `PREVIEW`, `ALBUM_OR_PLAYLIST`, `ARTIST_OR_PLAYLIST_PRIVACY`, `DOWNLOAD_ARTWORKS`…
- **~70 external triggers** (`play`, `pause`, `refresh_library`, `spot_mini_debug`, …) plus matching **remote triggers** (`com.vdesabou.miniplayer.*`) for Alfred Remote. Code fires them with `osascript -e 'tell application id "…Alfred" to run trigger "X" in workflow "com.vdesabou.spotify.mini.player"'`.

When editing behavior that's wired in `info.plist`, parse it with `plistlib` rather than grepping raw XML.

### The `▹` navigation protocol

The whole UI is one script filter. Navigation depth is encoded by counting `▹` (U+25B9) characters in the query string, and `src/main.php` dispatches on that count plus the first segment:

| depth | file | example query |
|---|---|---|
| 0 | `src/menu.php` (`mainMenu`, `mainSearch`, `search*FastAccess`) | `daft punk` |
| 1 | `src/firstDelimiter.php` (`firstDelimiterPlaylists`, …) | `Playlist▹` |
| 2 | `src/secondDelimiter.php` (`secondDelimiterArtists`, …) | `Artist▹Daft Punk▹` |
| 3 | `src/thirdDelimiter.php` (`thirdDelimiterAdd`, `thirdDelimiterBrowse`) | `Add▹…▹…▹` |

Adding a new browsing level means adding a branch in `main.php` *and* a `<kind>Delimiter<Thing>` function in the matching file. Query history (for the `bb` "Go Back" item) is appended to `history.json` on every delimiter step.

### The 22-element serialized arg

Every actionable result passes a PHP-`serialize`d **22-element positional array** as its Alfred `arg`, which `action.php` unpacks. The positions are fixed and commented inline at every call site (`track_uri`, `album_uri`, `artist_uri`, `playlist_uri`, `spotify_command` (base64), `query`, `other_settings`, `other_action`, `alfred_playlist_uri`, `artist_name`, `track_name`, `album_name`, artwork paths, `playlist_name`, `playlist_artwork_path`, `alfred_playlist_name`, `now_playing_notifications`, `is_alfred_playlist_active`, `country_code`, `userid`). `other_action` is the main verb switch in `action.php` (`play`, `pause`, `refresh_library`, `create_library`, `volume_up`, `guided_setup`, …).

Keep the array length and slot order intact — every result in `menu.php`, `*Delimiter.php`, `debug.php`, and the inline scripts in `info.plist` builds the same shape.

### Settings

Settings are **Alfred workflow configuration variables**, not a file. `getSetting($w, 'foo')` reads `getenv('__foo')` (Alfred injects them); `updateSetting($w, 'foo', $v)` writes via `osascript … to set configuration "__foo" …`. Consequence: a value written by `updateSetting` is **not** visible to `getenv` in the same process. `resetSettings()` in `src/functions.php` is the authoritative list of setting names and defaults — add new settings there. `getSetting` also contains a one-shot migration from the legacy `settings.json` / `settings.db`.

`prefs.plist` is the local user's real config (contains OAuth tokens) and is gitignored — never commit changes to it.

### Library database

`src/createLibrary.php` builds `library.db` from scratch; `src/refreshLibrary.php` (2 000+ lines) does incremental refresh using playlist `snapshot_id` comparison, optionally limited to `refresh_playlists` when `only_refresh_selected_playlists` is set. Tables: `tracks`, `playlists`, `followed_artists`, `shows`, `episodes`, `counters`. Each searchable name column has a `*_deburr` twin (accent-stripped) used for matching.

Concurrency is coordinated by sentinel files in the data dir: `update_library_in_progress`, `download_artworks_in_progress`, `change_theme_color_in_progress`. Each holds `label▹done▹total▹start_time▹phase`; `main.php` renders them as progress rows and mutating actions refuse to run while `update_library_in_progress` exists. During a refresh the old DB is kept as `library_old.db` and served read-only. Artwork download runs as a separate detached `php … DOWNLOAD_ARTWORKS` process tracked in `fetch_artworks.db`.

Periodic refresh is a launchd agent: `action.php` `sed`s `:INTERVAL:` in `src/com.vdesabou.spotify.mini.player-template.plist` into `~/Library/LaunchAgents/com.vdesabou.spotify.mini.player.plist` and `launchctl load`s it. The agent fires the `refresh_library` external trigger with argument `launch_agent`, which routes to `refresh_library_external` (silent).

### OAuth

Each user registers their own Spotify app. `start_php_server` launches `php -S 127.0.0.1:15298` in the workflow directory; `setup.php` is the UI, `client_submit.php` validates and stores the client ID/secret, `index.php` redirects to Spotify's authorize URL, `callback.php` exchanges the code and stores the tokens. `src/kill_php_server.ksh` tears the server down after 60 s. The scope list is duplicated in `index.php` and `client_submit.php` — change both together.

`getSpotifyWebAPI($w)` is the single accessor for a `SpotifyWebAPI` instance (jwilsson/spotify-web-api-php) with `auto_refresh`/`auto_retry` on; it caches the instance on `$w` and persists rotated tokens back into settings. Spotify Premium is required.

### Other pieces

- `src/functions.php` (~8 600 lines) — everything shared: Spotify Connect device control, playback, playlist/library mutation, notifications, artwork, theme switching, `logMsg`.
- `src/workflows.php` — vendored David Ferguson `Workflows` class. `$w->result(...)` accumulates items, `$w->tojson()` emits them; `$w->data()` / `$w->cache()` come from the `alfred_workflow_data` / `alfred_workflow_cache` env vars.
- `.ksh` helpers in `src/` — small shell shims called from `info.plist` (`is_spotify_running`, `track_info`, `spotify_mini_player_notifications`, `download_artworks`).
- Icons: the 56 files in `images/` are the *active* theme, plus 113 UUID-named PNGs at the workflow root referenced directly by `info.plist` nodes (`switchThemeColor()` maps UUID → logical name). Switching theme downloads replacements from `resources/images_<color>/` (black, blue, green, orange, pink, red) on GitHub raw — so a new icon must be added to every `resources/images_*` variant *and pushed to master* before the theme switcher can find it.

## Docs site

`docs/` is Jekyll 3 + Octopress with a Grunt asset pipeline, served by GitHub Pages from master.

```bash
cd docs && bundle install && bundle exec jekyll serve   # http://localhost:4000
```
