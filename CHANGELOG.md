# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-04-30

### Added
- `RetryMiddleware` — automatic retry on `ConnectException` (DNS failure, connection refused, timeout, SSL errors) and HTTP 503; no delay between attempts to avoid blocking the application
- `SeqDeliveryException` — thrown when delivery fails after all retries, exposing the root cause via `getPrevious()`
- `maxRetries` parameter on `SeqHandler` (default: `3`); set to `0` to disable retries
- Constructor validation: `url` must be a valid URL; `maxRetries` must be ≥ 0

### Changed
- `apiKey` is now optional (`?string`, default: `null`) to support unsecured Seq instances
- CI pipeline now runs the test suite against PHP 8.1, 8.2, 8.3, 8.4, and 8.5

## [1.1.0] - 2026-04-03

### Added
- Batch ingestion via `handleBatch()` — multiple records sent in a single CLEF-newline-delimited request
- Guzzle (`guzzlehttp/guzzle`) replaces the previous cURL implementation

### Changed
- Improved CLEF formatting: `@mt` (message template) is now used when the message contains named placeholders; `@x` is formatted as human-readable text instead of a raw object

## [1.0.1] - 2025-03-02

### Fixed
- `SeqJsonFormatter` now correctly includes `extra` context fields in the formatted event

## [1.0.0] - 2025-03-01

### Added
- `SeqHandler` — Monolog handler that sends log records to Seq via HTTP ingestion
- `SeqJsonFormatter` — formats records as CLEF, mapping Monolog levels to Seq severity names
- `CLEFProperty` enum covering standard CLEF fields (`@t`, `@m`, `@mt`, `@l`, `@x`, `@i`, `@tr`, `@sp`, and others)

[1.2.0]: https://github.com/Pablo1Gustavo/monolog-seq/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/Pablo1Gustavo/monolog-seq/compare/1.0.1...v1.1.0
[1.0.1]: https://github.com/Pablo1Gustavo/monolog-seq/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/Pablo1Gustavo/monolog-seq/releases/tag/1.0.0
