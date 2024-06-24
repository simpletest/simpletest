# SimpleTest - Change Log

## [Unreleased]

**Currently working on: ...**

* renamed branch "master" to "main"
* added old changelog entries to this file
* enabled building/testing on Github Actions
* deleted `.travis.yml` (Travis CI)
* new folder structure
  - moved source files into "src/", adjusted file includes accordingly
  - renamed folder "test" to "tests"
  - moved static website data into "build/website/simpletest.org-static"
  - moved xml sources for en docs into "build/docs/source"
* dropped doc translations for IT and FR (only keeping EN as source)
* updated php-cs-fixer config file (`.php_cs`)
* incremental code-style refactorings
  - use short array syntax, "array() => []"
  - removed superfluous phpdoc tags
  - trim blank lines on phpdoc blocks
  - removed @access declarations from phpdoc blocks
  - space after exclamation mark, "! $value"
  - renamed phpdoc type name boolean to shorter bool, "boolean => bool"
* added `.editorconfig`

## [1.2.0] - 2019-09-17

* [PR#46] added import / export cookies functions
   - Browser: getCookies() & setCookies($cookies)
   - UserAgent: getCookies() & setCookies($cookies)
   - Cookies: getCookies()
* fix #44: made dumper->clipString() multibyte safe
* added configuration file for Phan (Static Analyzer)
* HtmlReporter: renamed $character_set to $charset and set "utf-8" as default charset
* cleanup of reference handling
  - removed explicit assign-new-object-by-reference ampersands
  - removed explicit assign-object-by-reference ampersands
  - removed unnecessary return-object-by-reference ampersands
* fixed several acceptance tests and enabled PHP server to serve the examples
* fix [SF#192](http://sourceforge.net/p/simpletest/bugs/192/) PUT request fails to encode body parameters
* added Changelog
* added .php_cs fixer configuration to keep coding style consistent
* fixed coding style and phpdoc issues through codebase
* BC break: CodeCoverage Extension uses PHP extension "sqlite3" now
  - dropped PEAR DB dependency
* fixed #17: Access to original constructor in mocks (@mal)
* fixed return "exit code" of true/1, when tests are passing
* fixed #14: Add support for mocking variadic methods (@36degrees)
* added support for inputs of type date and time
* changed minimum PHP requirement to 7.1+ (dropped support for PHP 5)

## [1.1.7] - 2015-09-21

* issue #12 - fix double constructor
* issue #11 - fix reference expectation
* removed PHP4 reflection support
* removed PHP4 compatibility layer

## [1.1.0] - 2012-01-23

* Changing the TODO to reflect the "maintenance mode"
* New architecture pour SimpleTest's packages
* SF-Bug 3385457: "invalid cookie host on redirect page" - submitted by transkontrol
* SF-Bug 3433847: "addTest() should be add()" - submitted by ZermattChris
* SF-Bug 3420857: "Restore working directory inside the shutdown function"
* SF-Bug 3312248: Erreur de traduction (fr) - Sessions -- by jonathan74
* Getting the translations back in shape (french and italian)

## [1.1-alpha3] - 2011-05-15

* Supressed error due to warning about mutating an array while in the sorting process
* Removing long out-of-date scripts that don't work anymore
* Adding PEAR2 package.xml for alpha deployment to simpletest.pearfarm.org channel
* SF-Patch 2896044: browser/submitFormById additional form parameters support - submitted by Vasyl Pasternak (vasylp)
* SF-Patch 3136975: Unset $context->test between calls to SimpleTestCase->run
* SF-Bug 2890622: Please remove trailing whitespace from code - submitted by daniel hahler (blueyed)
* SF-Bug 2896575: WebTestCase::assertTrue: empty $message - patch submitted by daniel hahler (blueyed)
* SF-Bug 2798170: Changing assertIsA to user is_*() instead of gettype()
* Removing arguments dependency
* Adding docblock for phpdoc api documentation

## [1.1-alpha2] - 	2010-11-02

* SF-Patch 2895861: SimpleUrl: keys in query string need to get urldecoded, too - submitted by daniel hahler (blueyed)
* SF-Patch 2914321: Fix action when submitting GET forms (remove request) - submitted by daniel hahler (blueyed)
* Adding a command line parser as part of making the command line much more flexible
* Moving recorder from extensions to core
* Fixes for PHP 5.3.1

## [1.1-alpha] - 2010-10-29

* Fixed textarea glitch with Tidy parser. Tidy parser now default
* SF-Bug 2881793: fixing typo in globals reference
* Adding support for PUT / DELETE requests
* SF-Bug 2849129: Correcting the "wrong style on simpletest.org"
* Adding italian translation
* Small performance optimisation to normalise() function. Its called a lot in the parser, so worth speeding up.
* Rename get/setPageBuilders() to get/setParsers()
* Put in setting switch on simpletest to allow parser selection.
* Start putting in switch to allow optional use of HTML tidy based parser
* Fix MemberExpectation for php5.2
* Switched IdenticalExpectation message to using reflection to list properties. Otherwies the member names get screwed.
* IdenticalExpectation now includes comparisons on private members
* SimplePage is now just a data holder after moving temporary parsing state to SimlePageBuilder. All part of adding a flex point to accept alternate parsers.
* Making sure we use "autorun.php" all the time
* Bug: autorun should set the exit status to 0 if tests pass.
* Added ability to ignore irrelevant exceptions left over at the end of a test
* Replacing "assertWantedPattern" by "assertPattern"
* Removing the "$test->addTestFile()" : it's been deprecated in favor of "$test->addFile()"
* Removing the "$test->addTestCase()" : it's been deprecated in favor of "$test->addTest()"
* Removing the "$mock->setReturnReference()" : it's been deprecated in favor of "$mock->returnsByReference()"
* Removing the "$mock->setReturnValue()" : it's been deprecated in favor of "$mock->returns()"

## [1.0.1] - 2008-04-08

* Whitespace clean up
* Some in line documentation fixes
* Adding the JUnitReporter as an extension to SimpleTest (work by Patrice Neff)
* New support page for Screencasts

## [1.0.1-rc1]

* Synchronizing the french translation.
* Unit tests working for PHP 5.3
* Fix segfault with Zend Optimizer v3.2.2 (probably)
* Adding some tags to help synchronise the docs
* Add support for E_DEPRECATED
* New tests for UTF8 inside the browser.
* Moving around the extensions : /ui is now deprecated, /extensions is holding all extensions, /test/extensions is holding all extensions' test suites
* Clearing fatal error when throwing in a tearDown
* remove call-time reference - its declared in the constructor, so that's enough
* Adding error throwing to mocks
* Added PHP4 patches for new mock code
* Added filter that rewrites paths to included files in tests... now just need to clean up all the hardcoded path references in the existing tests and we should be able to make a start on building an extension layout that's compatible with PEAR installer *and* manual tar/zip extraction
* Add in default wrap to catch all 'verify' methods and wrap them in assertTrue
* SimpleFileLoader::selectRunnableTests(..) not only marks abstract classes as ignored but filters them as well
* renaming SimpleReflection::_isAbstractMethodInParent() into _isAbstractMethodInParents() and making it check upwards if method was declared somewhere abstract not just in the immediate parent, this allows to avoid ugly 'must be compatible' error in PHP5
* switch to Subversion (SVN)
* SF-BUG 1853765: Fixing one of the incompatible interface errors
* SF-BUG 1377866: Let choose which field of the same name to set
* SF-BUG 1791217: CssSelector chokes on single-quotes
* SF-BUG 1699111: clickImageByID not working : just updating the documentation
* SF-BUG 1787087: html special chars in links
* SF-BUG 1790469: decodeHtml does not decode some entities (based on patch provided by Quandary - ai2097)
* SF-BUG 1642529: Radio buttons not working when set as integer
* SF-BUG 1436854: Missing return value
* SF-BUG 1782552: Inner links inside documentation for "simpletest.org" now work with *.html
* SF-BUG 1852413: Hostname extracted incorrectly from URIs containing @ sign
* SF-BUG 1671539: assertWantedText matches javascript source code
* SF-BUG 1688238: SimpleUrl doesn't appear to handle path after filename
* [bug] Recursive forms fails
* SF-Patch 1899780: SimpleFileLoader::load: fix for $test_file already included, by daniel hahler - blueyed
* SF-Patch 1892029: "Update FormTesting tutorial page for hidden fields" submitted by David Heath - dgheath
* Patch: Avoid a fatal error in assertLink (when the link with a label does not exists in a page) submitted by German Rumm (german.rumm gmail.com)

## [1.0.1-beta2]

- autorun
- browser base tag support

## [1.0.1-beta]

- expectException()
- proper skip facility
- greater formatting control in the reporters
- various mock object compatibility fixes

[Unreleased]: https://github.com/simpletest/simpletest/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/simpletest/simpletest/compare/v1.1.7...v1.2.0
[1.1.7]: https://github.com/simpletest/simpletest/compare/v1.1.6...v1.1.7

[PR#46]: https://github.com/simpletest/simpletest/pull/46
