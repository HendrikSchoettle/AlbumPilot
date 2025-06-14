# Changelog

All notable changes to this project will be documented in this file.

## [0.3.3] – 2025-06-15

### Fixed
- Replaced all PHP short-opening tags (`<?`) with full tags (`<?php`) in include scripts (`include/images.php`, `include/checksum.php`, `include/metadata.php`, `include/videos.php`) to prevent raw code appearing when `short_open_tag` is disabled.

## [0.3.2] – 2025-06-15

### Changed
- **Removed** `maintain.class.php`: all table‐creation now occurs in `admin.php`.  
- **Uninstall cleanup**: the settings table now persists after uninstall (minimal footprint) and has to be dropped manually.  
- **Temporary hotfix**: fixes the installation blocker affecting v0.3.1 and v0.3.0; comprehensive solution to follow.

## [0.3.1] – 2025-06-13

### Fixed
- Resetting the Step 2 settings now correctly re-enables the “Import poster” and “Generate poster from frame” checkboxes instead of leaving them greyed-out.
- Added an extra safeguard so that video thumbnails themselves are never treated as poster-source files — no more thumbnails of thumbnails.
- If video thumbnail generation was disabled in the UI, it is no longer erroneously re-enabled when starting a batch run (`external_run=1`).

## [0.3.0] – 2025-06-11

### Added
- **Album selector dropdown** with text search: Allows searching albums by name directly in the UI.
- **Individual thumbnail options**: Specify granular settings for thumbnail generation, including one-click overwrite of existing thumbnails (eliminates manual deletion step).
- **Individual VideoJS options**: Specify granular settings for video posters and generated thumbnails.
- **"All Albums" root option** added to album selector: A new top-level entry allows applying sync steps to all albums at once. Previously, albums could only be selected individually.
- **Optional database reset logic during plugin (de)installation**: A `maintain.class.php` file is included (but disabled by default by renaming it to `maintain.class.php.disabled`) to drop the plugin's database tables during uninstall. To enable this, rename the file back to `maintain.class.php`.  
  **Important:** This only works if the plugin is installed under the exact folder name `AlbumPilot`. If the plugin folder includes version numbers (e.g., from GitHub zip archives), the installation will fail. This logic is currently disabled to prevent unintended data loss and will be activated once the plugin is available via the official Piwigo plugin store.

### Changed
- **Thumbnail cleanup for VideoJS**: Automatically deletes previously generated thumbnails when updating a video poster, preventing display of outdated thumbnails (improvement over official VideoJS behavior).
- **Step order swapped**: Step 2 is now **Generate Video Posters** and Step 3 **Generate Thumbnails**, so thumbnails are generated from the updated posters.
- **Backend refactoring**: Restructured and cleaned up code for improved readability and maintainability.
- **Batch-mode enhancements**: Optimized processing and handling of batch synchronizations.

### Fixed
- Several bug fixes across synchronization steps, UI interactions, and API handling.

## [0.2.1] – 2025-06-02

### Fixed
- **External URL parameters no longer overwritten by stored settings**: Fixed logic in `script.js` to prevent saved checkbox options from being applied during `external_run=1`.
- **No longer saves settings during external sync**: Calls to `saveSyncSettings()` now detect and skip saving when triggered via external execution.
- Centralized `external_run` check inside the `saveSyncSettings()` function for maintainability.

## [0.2.0] – 2025-06-01

### Changed
- **Plugin directory name is now dynamic**: The plugin no longer depends on a fixed folder name and works regardless of installation path.
- **URL-based sync triggering added**: Synchronization steps can now be run externally by appending `external_run=1` with additional parameters to the plugin URL.
- **Reordered sync steps**: Step 4 (Checksum Calculation) and Step 5 (Metadata Update) swapped positions to improve logical processing order.
- **Optimized language loading**: Frontend now loads only the currently active language and only strings relevant for JavaScript.
- **Improved visual log output**: Bullet symbol glitches in log output have been resolved for clearer display.

## [0.1.1] – 2025-05-28

### Added
- Video metadata writing for video files in Step 4 (duration, resolution, etc.).

### Changed
- Consistent use of visual log symbols across all steps.

### Fixed
- Logs now reliably appear at the start of each step.
- JSON-related UI errors at sync start resolved.
