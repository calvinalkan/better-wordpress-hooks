# How to contribute

BetterWpHooks is completely open source and everybody is encouraged to participate by:

- ⭐ the project on GitHub ([https://github.com/calvinalkan/better-wordpress-hooks](https://github.com/calvinalkan/better-wordpress-hooks))
- Posting bug reports ([https://github.com/calvinalkan/better-wordpress-hooks/issues](https://github.com/calvinalkan/better-wordpress-hooks/issues))
- (Emailing security issues to [calvin@snicco.de](calvin@snicco.de) instead)
- Posting feature suggestions ([https://github.com/calvinalkan/better-wordpress-hooks/issues](https://github.com/calvinalkan/better-wordpress-hooks/issues))
- Posting and/or answering questions ([https://github.com/calvinalkan/better-wordpress-hooks/issues](https://github.com/calvinalkan/better-wordpress-hooks/issues))
- Submitting pull requests ([https://github.com/calvinalkan/better-wordpress-hooks/pulls](https://github.com/calvinalkan/better-wordpress-hooks/pulls))
- Sharing your excitement about BetterWpHooks with your community

## Development setup

1. Fork this repository.
2. Open up your plugin/theme directory in your terminal of choice.
3. Clone your fork locally e.g. `git clone git@github.com:your-username/better-wordpress-hooks.git`.
4. From your root directory run `composer install`.

## Running tests

As of 05/04/2021 BetterWpHooks almost has 99% code-coverage. We tried to include tests for as many edge cases as we could think of. 
In order to run the testsuite make sure you have PHPUnit installed as a dev dependencies. 

Run this command from the terminal in your project root (  where your composer.json file is located ) in order to run the tests. 

`vendor/bin/phpunit --testsuite unit --colors`

## Pull Requests

- Pull request branches MUST follow this format: `{issue-number}-{short-description}`.
  Example: `12345-fix-route-condition`
- As soon as you push a pull request to the master branch, circle ci will start an automated build. In order to merge pull requests, **the automated build shall not have any test errors.**
- Pull requests SHOULD include unit tests for new code/features