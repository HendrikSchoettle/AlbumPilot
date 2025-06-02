# Changelog

All notable changes to this project will be documented in this file.

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
