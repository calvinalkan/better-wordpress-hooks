# Release Notes

## [0.1.8](https://github.com/calvinalkan/better-wordpress-hooks/compare/0.1.7...0.1.8)

### Added 

- Support for custom event payloads. Object events can now define a `payload()` method which is used then instead of the object itself when dispatching the event. Fixes [issue #9.](https://github.com/calvinalkan/better-wordpress-hooks/issues/9) 

### Updated

- You now need to pass the vendor directory into the `setUpWp()` method when using the BetterWpHooksTestCase.

### Fixed

- BetterWpHooksTestCase looked for the bootstrap file in the wrong directory. 

## [0.1.7](https://github.com/calvinalkan/better-wordpress-hooks/compare/0.1.6...0.1.7)

### Added

- Added better-wordpress-hook-api-clone to dependencies instead of dev-dependencies.
- Added an optional parameter to pass a custom file path into the `setUpWp()` method when using the testing module

## [0.1.6](https://github.com/calvinalkan/better-wordpress-hooks/compare/0.1.5...0.1.6)

### Added

- Update dependency on calvinalkan/interfaces to 0.1.2. The `ContainerAdapter` Interface now required a method `implementation()` to get the underlying container implementation of the adapter.

## [0.1.5](https://github.com/calvinalkan/better-wordpress-hooks/compare/0.1.4...0.1.5)

### Added

- Added release notes. 
- Added new documentation for Release [0.1.4](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/CHANGELOG.md#014)

## [0.1.4](https://github.com/calvinalkan/better-wordpress-hooks/compare/0.1.3...0.1.4)

### Added

- Smart default values - You can now typehint the return value on your event's `default()` method. If the typehint does not match with the filtered value the default() method will get called with the original and filtered value. ([Usage](https://github.com/calvinalkan/better-wordpress-hooks/tree/0.1.5#return-values-for-invalid-callback))([Commit](https://github.com/calvinalkan/better-wordpress-hooks/commit/8d564babae2f448f607ceb1aea73edae487d2bfc#diff-6f76b222b1d42b154e0ca5f9cca9c766227cb56a75f7bff262e412a5f85a9378R182))
- It's now possible to resolve mapped events from the service container ([Commit](https://github.com/calvinalkan/better-wordpress-hooks/commit/3b48f0b7951c28e1f1c8ff7ce94ce7e842e89ef6)). See example under [Bootstrapping](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/README.md#bootstrapping).

### Changed
- It's now possible to dispatch event objects without having to pass an empty array for events without constructor arguments.([Commit](https://github.com/calvinalkan/better-wordpress-hooks/commit/6165c5b3b0c810839fa02c43ebec87e78d91c6f1))
- the default() method now receives the original payload, and the filtered value. ([Commit](https://github.com/calvinalkan/better-wordpress-hooks/commit/8d564babae2f448f607ceb1aea73edae487d2bfc))

### Fixed

- Some docblocks mistakes that led to false positive errors in code editors.
- Logic error. Removed code that attempted to map several custom events to a WordPress Hook.
This should have never been possible. Only one custom event should be mapped to a WordPress Hook.
