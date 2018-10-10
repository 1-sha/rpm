#!/usr/bin/make -f

.PHONY: init css styleguide lint-sass lint-js

ci-lint: lint-sass lint-js

init:
	npm install
	npm rebuild node-sass

css:
	node_modules/.bin/gulp styles:production

styleguide:
	node_modules/.bin/gulp styleguide

lint-sass:
	node_modules/.bin/gulp lint:sass-with-fail

lint-js:
	node_modules/.bin/gulp lint:js-with-fail
