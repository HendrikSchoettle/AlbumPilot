# Changelog for AlbumPilot Plugin for Piwigo

All notable changes to this project will be documented in this file.

## [1.0.0] - 2025-07-07

### Changed
- Extensive documentation updates covering recent feature changes.
- Inserted external link to documentation.
- Code cleaned up by removing obsolete functions and variables.

### Fixed
- Batch-mode controls no longer re-enable at the end of a sync.
- Hardcoded fallback for thumbnail labels in US-English locale where the plugin fails to load core translations (temporary workaround for en_US issue).

## [0.3.14] - 2025-07-02

### Changed
- Improved sync summary for Step 1: The synchronization log now clearly lists how many files were added and deleted, in addition to the total delta.
- Refined SQL checker: The built-in [images_checker/README.md](./images_checker/README.md) now uses a safer single-column search, making it easier to detect suspicious images even when filenames contain commas.
- Album sorting behavior updated: The automatic alphabetic sort order for albums and subalbums has been removed. AlbumPilot now fully respects the `global_rank` from Piwigo's album manager, so your manual drag and drop hierarchy remains untouched and displays exactly as configured.
- Improved thumbnail overwrite logic: existing thumbnails are now backed up and only deleted after successful creation of the new derivative, preventing data loss if generation fails.
- External trigger URL panel is now collapsible and is collapsed by default.

### Fixed
- Filenames with spaces are now properly handled when generating thumbnails. This prevents repeated regeneration loops for the same thumbnails when spaces or special characters are present.
- Fixed an issue where video posters were not overwritten even when the overwrite option was enabled. Poster derivatives now follow the same overwrite and backup logic as regular image thumbnails.

## [0.3.13] - 2025-07-01

### Fixed

- Critical bugfix: Fixed a severe issue in Step 4 (thumbnail generation) that could accidentally delete original source image files if the "Overwrite existing thumbnails" option was enabled.
  - This affected images whose dimensions exactly matched certain thumbnail sizes.
  - Now, multiple safeguards have been added to ensure that only actual derivative thumbnails can ever be deleted.

  Important: If you ran Step 4 with the overwrite option in any previous version, I strongly recommend double-checking your albums to ensure no original photos were unintentionally removed.

  Apologies for this oversight - despite careful testing, this edge case slipped through. Please excuse any inconvenience caused.

### Note

A simple SQL snippet and a ready-to-use shell script are provided to help you identify any potentially affected files.
For full instructions, see the [images_checker/README.md](./images_checker/README.md) included in this plugin.

## [0.3.12] - 2025-06-30

### Fixed
- Fully restored greying-out of nested options for "Select all"/"Unselect all" and "Reset settings," including text color.
- PNG posters are now properly detected and used as the source, resulting in thumbnails generated in the original PNG format (Step 4).

## [0.3.11] - 2025-06-29

### Changed
- Added a separate option to overwrite existing video poster thumbnails independently from the main poster overwrite setting.

### Fixed
- Fixed an issue where disabled plugins (e.g. VideoJS or SmartAlbums) were incorrectly re-enabled in the UI after a sync run.
- Improved the enable/disable logic for all dependent poster and thumbnail options: these now consistently follow the main step checkboxes for "Generate video posters" (step 3) and "Generate thumbnails" (step 4).
- The "Select all steps" toggle now correctly re-enables all nested sub-options for video and thumbnail generation.

### Known Limitations
- The text color for nested options under step 3 and step 4 may remain visually light gray after the workflow finishes, when selecting them via the select/unselect all option, even though the controls are re-enabled and clickable. This does not affect functionality but will be addressed in an upcoming patch.

### Miscellaneous
- Minor internal refactoring of dependency handlers to reduce duplicate calls.

## [0.3.10] - 2025-06-28

### Fixed
- Poster filenames now use the selected output format; old extensions are removed.
- pwg_representative folder is cleared before regenerating video posters.
- Apply film effect overlay works for both JPG and PNG output.
- Overwrite existing thumbnails functionality has been restored.

### Changed
- When switching output format, thumbnails of the previous format are deleted after successful creation.
- Minor UI layout and label fixes.
- Checkbox enable/disable logic for poster and thumbnail controls now depends solely on the "Generate video posters" and "Generate thumbnails" steps.

### Miscellaneous
- Minor code cleanup and comment refinements.

## [0.3.9] - 2025-06-25

### Fixed
- Improved error handling during metadata scan: when a file (e.g. large video) cannot be processed, the metadata scan is now aborted safely without crashing the entire sync. All remaining steps continue as expected.

### Changed
- Albums and subalbums are now alphabetically sorted prior to video scan and processing. This ensures consistent order during batch processing and improves log traceability.
- Poster and thumbnail overwrite logic now safely preserves existing files: old poster or thumbnail images are only deleted if the new file has been successfully written.
- Update metadata has now become step 2 (initially being step 5), in order to enable a coherent workflow.
- Minor UI wording adjustments and improvements.

## [0.3.8] - 2025-06-18

### Fixed
- Prevent crash during metadata update when a file is missing or unreadable: such cases are now caught and logged.

## [0.3.7] - 2025-06-18

### Fixed
- Skip video posters or thumbnails when image dimensions cannot be read, with improved error messaging.

### Changed
- Minor UI improvements.

## [0.3.6] - 2025-06-17

### Fixed
- Improved CSS styling for disabled VideoJS options now correctly applies light-gray to all labels and inputs when video poster generation is turned off.
- Thumbnail generation for `XXLarge` (and similar) video posters no longer loops indefinitely: added a size-equality guard so that source dimensions equal to target dimensions are skipped.

## [0.3.5] - 2025-06-17

### Changed
- Log output for generated thumbnails now uses localized labels: instead of `Type: medium`, the log will show the localized term in the user's selected language.
- Improved visual styling for disabled UI elements: when video poster generation is turned off, the "Output format for poster" label and its JPG/PNG radio buttons now turn light gray.
- Fixed a bug where the "Output format for poster" text appeared light gray even when active.

### Known Issues
- Known Issues section in [README.md](./README.md): The section has been updated outlining currently known limitations and issues:
  - Persistent re-generation of large thumbnails (e.g., `XXLarge`) for certain video files.
  - Potential metadata sync problems with HEIC images (pending further investigation; likely a Piwigo core issue).
  - The new time-interval-based video thumbnail generation has only undergone basic testing and may be unstable; feedback is welcome.

## [0.3.4] - 2025-06-17

### Fixed
- Prevents sync from starting when the root album is selected but subalbums are explicitly disabled; appropriate error message shown.
- Synchronization no longer resumes after an aborted run; all states are cleanly reset. This also resolved faulty resume behavior which could cause e.g. live-mode sync to start despite simulation being selected.
- Fixed a bug where SmartAlbums and VideoJS-related checkboxes remained active but greyed-out after their respective plugins were disabled.
  In legacy installations, you need to manually drop the settings table once, or alternatively update to v0.3.4, then uninstall and reinstall this version again.
- A bug where the selected folder at sync start was not stored has been fixed.
- Metadata, poster, and thumbnail scans now correctly find files inside subfolders when the root album is selected.

### Changed
- During an active sync, all controls are now disabled and greyed-out to prevent further user interaction.
- Piwigo's uninstall logic now drops the database table `album_pilot_settings` even when the plugin is installed from GitHub (e.g., in folders like `AlbumPilot-main` or `AlbumPilot-0.3.4`).

## [0.3.3] - 2025-06-15

### Fixed
- Replaced all PHP short-opening tags (`<?`) with full tags (`<?php`) in include scripts (`include/images.php`, `include/checksum.php`, `include/metadata.php`, `include/videos.php`) to prevent raw code appearing when `short_open_tag` is disabled.

## [0.3.2] - 2025-06-15

### Changed
- Removed `maintain.class.php`: all table-creation now occurs in `admin.php`.
- Uninstall cleanup: the settings table now persists after uninstall (minimal footprint) and has to be dropped manually.
- Temporary hotfix: fixes the installation blocker affecting v0.3.1 and v0.3.0; comprehensive solution to follow.

## [0.3.1] - 2025-06-13

### Fixed
- Resetting the Step 3 settings now correctly re-enables the "Import poster" and "Generate poster from frame" checkboxes instead of leaving them greyed-out.
- Added an extra safeguard so that video thumbnails themselves are never treated as poster-source files - no more thumbnails of thumbnails.
- If video thumbnail generation was disabled in the UI, it is no longer erroneously re-enabled when starting a batch run (`external_run=1`).

## [0.3.0] - 2025-06-11

### Added
- Album selector dropdown with text search: Allows searching albums by name directly in the UI.
- Individual thumbnail options: Specify granular settings for thumbnail generation, including one-click overwrite of existing thumbnails (eliminates manual deletion step).
- Individual VideoJS options: Specify granular settings for video posters and generated thumbnails.
- "All Albums" root option added to album selector: A new top-level entry allows applying sync steps to all albums at once. Previously, albums could only be selected individually.
- Optional database reset logic during plugin (de)installation: A `maintain.class.php` file is included (but disabled by default by renaming it to `maintain.class.php.disabled`) to drop the plugin's database tables during uninstall. To enable this, rename the file back to `maintain.class.php`.
  Important: This only works if the plugin is installed under the exact folder name `AlbumPilot`. If the plugin folder includes version numbers (e.g., from GitHub zip archives), the installation will fail. This logic is currently disabled to prevent unintended data loss and will be activated once the plugin is available via the official Piwigo plugin store.

### Changed
- Thumbnail cleanup for VideoJS: Automatically deletes previously generated thumbnails when updating a video poster, preventing display of outdated thumbnails (improvement over official VideoJS behavior).
- Step order swapped: Step 3 is now Generate Video Posters and Step 4 Generate Thumbnails, so thumbnails are generated from the updated posters.
- Backend refactoring: Restructured and cleaned up code for improved readability and maintainability.
- Batch-mode enhancements: Optimized processing and handling of batch synchronizations.

### Fixed
- Several bug fixes across synchronization steps, UI interactions, and API handling.

## [0.2.1] - 2025-06-02

### Fixed
- External URL parameters no longer overwritten by stored settings: Fixed logic in `script.js` to prevent saved checkbox options from being applied during `external_run=1`.
- No longer saves settings during external sync: Calls to `saveSyncSettings()` now detect and skip saving when triggered via external execution.
- Centralized `external_run` check inside the `saveSyncSettings()` function for maintainability.

## [0.2.0] - 2025-06-01

### Changed
- Plugin directory name is now dynamic: AlbumPilot is now independent of its installation folder name. The plugin no longer requires a specific directory name and will function correctly regardless of how the plugin folder is named. However, folder names must not contain hyphens (`-`), as this breaks plugin activation and internal references.
- URL-based sync triggering added: Synchronization steps can now be run externally by appending `external_run=1` with additional parameters to the plugin URL.
- Reordered sync steps: Step 5 (Checksum Calculation) and Step 2 (Metadata Update) swapped positions to improve logical processing order.
- Optimized language loading: Frontend now loads only the currently active language and only strings relevant for JavaScript.
- Improved visual log output: Bullet symbol glitches in log output have been resolved for clearer display.

## [0.1.1] - 2025-05-28

### Added
- Video metadata writing for video files in Step 5 (duration, resolution, etc.).

### Changed
- Consistent use of visual log symbols across all steps.

### Fixed
- Logs now reliably appear at the start of each step.
- JSON-related UI errors at sync start resolved.
