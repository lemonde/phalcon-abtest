# CHANGELOG

## 4.0.0 (breaking change)
- get user device from service named `phalcon-abtest.device_provider` (breaking change)
- get tests config from service named `phalcon-abtest.tests` (breaking change)
- remove mobiledetect/mobiledetectlib package
- bump phpunit/phpunit to 11
- minimal PHP 8.2
- fix Redis hscan call to match real signature

## 3.0.1
- bump mobiledetect

## 3.0.0
- Update methods fix tests refacto for phalcon 5

## 2.0.0
- Update methods fix tests refacto for phalcon 4

## 1.2.1
- prevents count of default link display

## 1.1.1
- prefer not throwing exception when test not found

## 1.1.0
- add volt function ab_test_href
- prevents error to be thrown, prefers 404 with event
- Doc: add more usage examples
- Volt: add events on errors

## 1.0.2
- result is null only on errors

## 1.0.1
- allow forced winner to tests

## 1.0.0
- added a version helper for git-changelog
