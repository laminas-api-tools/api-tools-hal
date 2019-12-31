# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.3.1 - 2016-07-07

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zfcampus/zf-hal#111](https://github.com/zfcampus/zf-hal/pull/111) removes some code errantly
  left in a comment from a previous merge conflict.
- [zfcampus/zf-hal#112](https://github.com/zfcampus/zf-hal/pull/112) removes conditionals based
  on PHP 5.4, as the minimum version is now 5.5.
- [zfcampus/zf-hal#127](https://github.com/zfcampus/zf-hal/pull/127) fixes an issue in the
  `HalJsonStrategy` plugin whereby the wrong `Content-Type` header was being
  used when an `ApiProblem` response was handled; these now correctly return
  `application/problem+json` instead of `application/hal+json`.

## 1.3.0 - 2015-09-22

### Added

- [zfcampus/zf-hal#123](https://github.com/zfcampus/zf-hal/pull/123) updates the component
  to use laminas-hydrator for hydrator functionality; this provides forward
  compatibility with laminas-hydrator, and backwards compatibility with
  hydrators from older versions of laminas-stdlib.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2015-09-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zfcampus/zf-hal#122](https://github.com/zfcampus/zf-hal/pull/122) updates the
  laminas-stdlib dependency to reference `>=2.5.0,<2.7.0` to ensure hydrators
  will work as expected following extraction of hydrators to the laminas-hydrator
  repository.
