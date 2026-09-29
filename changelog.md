# Changelog

All notable changes to `AwsSesBounceComplaintHandler` will be documented in this file.

## Version 0.4.1

### Fixed
- `canSendToEmail()` threw `InvalidTypeException` for transient bounces; they are now blocked
  for `block_bounced_transient_for_minutes` after the last bounce, then allowed again

## Version 1.0

### Added
- Everything
