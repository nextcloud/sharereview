# Changelog

### 2.1.0 - 2025-12-08
### Changed
- Implement error handling for deleting shares when the user is not existing anymore #49
- perf: cache user folders, process files by owner #48 @[solracsf](https://github.com/solracsf)

## 2.0.1 - 2025-12-05
### Fixed
- Refactor permissions assignment in ShareService #43
- Sorting of time column #42
- UI fixes
- Improve reviewTimestamp condition check #45
- Normalize app name and update action format #44

## 2.0.0 - 2025-11-24
### Added
- indicate password protection and expiration date #20

### Changed
- the app is now provided via /nextcloud
- sort type column by type and not text
- released under the Nextcloud organization

## 1.6.0 - 2025-11-09
### Added
- Scheduling of report creation via daily/weekly/monthly background job

### Fixed
- Make permissions' tooltips translatable #31
- Center trash icon #32
- Item of shares from other users not visible in v1.5.0 #29
- selecting only valid team share

## 1.5.0 - 2025-11-04
### Added
- Export report as csv or pdf #25
- Show the full directory path for each share #26

### Fixed
- missing translations #27

## 1.4.0 - 2025-05-19
### Added
- Add bulk share deletion #15
- Add talk share filter and navigation option #19
- Add loading indicator during backend data fetch #17

### Fixed
- Error when no file shares are available #12
- UI improvements #14

## 1.3.3 - 2025-01-03
### Added
- Federation shares

## 1.3.2 - 2024-12-03
### Fixed
- Error when Talk is not installed #8

## 1.3.1 - 2024-11-28
### Fixed
- Event to register further sharing apps #8

## 1.3.0 - 2024-11-27
### Added
- Event to register further sharing apps #8
- Show any file share (talk, deck, team, ...) #9
- Show confirmation timestamp

### Changed
- Optimize table layout

## 1.2.0 - 2024-09-22
### Added
- stop table refresh on demand #4

### Fixed
- Helper\Exception error #6
- dark theme issue #7

## 1.1.0 - 2024-08-21
### Added
- show user names instead of ids

### Fixed
- Internal Server Error #5

## 1.0.2 - 2024-07-24
### Fixed
- handle obsolete shares

## 1.0.1 - 2024-07-22
### Fixed
- App description

## 1.0.0 - 2024-07-22
### Added
- Initial version
