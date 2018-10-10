'use strict';

var Promise = require('es6-promise').Promise;
var path = require('path');

var options = {};

// #############################
// Edit these paths and options.
// #############################

// The root paths are used to construct all the other paths in this
// configuration. The "project" root path is where this gulpfile.js is located.
// While Zen distributes this in the theme root folder, you can also put this
// (and the package.json) in your project's root folder and edit the paths
// accordingly.
options.rootPath = {
  project     : __dirname + '/',
  theme       : __dirname + '/',
  styleGuide  : __dirname + '/styleguide'
};

options.theme = {
  root  : options.rootPath.theme,
  css   : options.rootPath.theme + 'css/',
  sass  : options.rootPath.theme + 'sass/',
  js    : options.rootPath.theme + 'js/',
  sassjs: options.rootPath.theme + 'source/components/'
};

// Set the URL used to access the Drupal website under development. Without protocol.
options.drupalURL = 'agov.dev';

// Define the node-sass/eyeglass configuration.
options.sass = {
  includePaths: [
    __dirname + '/node_modules',
    options.theme.sass
  ],
  outputStyle: 'compressed'
};

options.sassFiles = [
  options.theme.sass + '**/*.scss',
  // Do not open Sass partials as they will be included as needed.
  '!' + options.theme.sass + '**/_*.scss'
];

// Define which browsers to add vendor prefixes for.
options.autoprefixer = {
  browsers: [
    'last 2 versions',
    'ie >= 9',
    'and_chr >= 2.3'
  ]
};

// Define the style guide paths and options.
options.styleGuide = {
  source: [
    options.theme.sass,
    options.theme.sass + 'style-guide/'
  ],
  destination: options.rootPath.styleGuide,
  builder: options.rootPath.project + 'node_modules/kss-node-offscreen-template',

  // The css and js paths are URLs, like '/misc/jquery.js'.
  // The following paths are relative to the generated style guide.
  css: [
    path.relative(options.rootPath.styleGuide, options.theme.css + 'style-guide/kss-only.css'),
    path.relative(options.rootPath.styleGuide, options.theme.css + 'styles.css')
  ],
  js: [
  ],

  homepage: 'homepage.md',
  title: 'aGov STARTERKIT styleguide'
};

// Define the paths to the JS files to lint.
options.eslint = {
  files  : [
    options.theme.js + '**/*.js',
    '!' + options.theme.js + '**/*.min.js'
  ]
};

// If your files are on a network share, you may want to turn on polling for
// Gulp watch. Since polling is less efficient, we disable polling by default.
options.gulpWatchOptions = {};
// options.gulpWatchOptions = {interval: 1000, mode: 'poll'};

// ################################
// Load Gulp and tools we will use.
// ################################
var gulp        = require('gulp'),
  $           = require('gulp-load-plugins')(),
  browserSync = require('browser-sync').create(),
  del         = require('del'),
// gulp-load-plugins will report "undefined" error unless you load gulp-sass manually.
  sass        = require('gulp-sass'),
  sassGlob    = require('gulp-sass-glob'),
  spawn       = require('child_process').spawn,
  eyeglass    = require('eyeglass'),
  sassLint    = require('gulp-sass-lint'),
  kss         = require('kss');

// The default task.
gulp.task('default', ['build']);

// #################
// Build everything.
// #################
gulp.task('build', ['styles:production', 'lint', 'styleguide']);

// ##########
// Build CSS.
// ##########
gulp.task('styles', ['clean:css'], function() {
  return gulp.src(options.sassFiles)
    .pipe(sassGlob())
    .pipe($.sourcemaps.init())
    .pipe(sass(eyeglass(options.sass)).on('error', sass.logError))
    .pipe($.autoprefixer(options.autoprefixer))
    .pipe($.size({showFiles: true}))
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest(options.theme.css))
    .pipe($.if(browserSync.active, browserSync.stream({match: '**/*.css'})));
});

gulp.task('styles:production', ['clean:css'], function() {
  return gulp.src(options.sassFiles)
    .pipe(sassGlob())
    .pipe(sass(eyeglass(options.sass)).on('error', sass.logError))
    .pipe($.autoprefixer(options.autoprefixer))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.css));
});

// ##################
// Build style guide.
// ##################
gulp.task('styleguide', ['clean:styleguide'], function(cb) {
  kss(options.styleGuide, cb);
});

// Debug the generation of the style guide with the --verbose flag.
gulp.task('styleguide:debug', ['clean:styleguide'], function(cb) {
  options.styleGuide.verbose = true;
  kss(options.styleGuide, cb);
});

// #########################
// Lint Sass and JavaScript.
// #########################
gulp.task('lint', ['lint:sass', 'lint:js']);

// Lint JavaScript.
gulp.task('lint:js', function() {
  return gulp.src(options.eslint.files)
    .pipe($.eslint())
    .pipe($.eslint.format());
});

// Lint JavaScript and throw an error for a CI to catch.
gulp.task('lint:js-with-fail', function() {
  return gulp.src(options.eslint.files)
    .pipe($.eslint())
    .pipe($.eslint.format())
    .pipe($.eslint.failOnError());
});

// Lint Sass.
gulp.task('lint:sass', function() {
  return gulp.src([
      options.theme.sass + '**/*.scss'
    ])
    .pipe(sassLint())
    .pipe(sassLint.format());
});

// Lint Sass and throw an error for a CI to catch.
gulp.task('lint:sass-with-fail', function() {
  return gulp.src(options.theme.sass + '**/*.scss')
    .pipe(sassLint())
    .pipe(sassLint.format())
    .pipe(sassLint.failOnError());
});

// ##############################
// Watch for changes and rebuild.
// ##############################
gulp.task('watch', ['browser-sync', 'watch:lint-and-styleguide', 'watch:js']);

gulp.task('browser-sync', ['watch:css'], function() {
  return browserSync.init({
    proxy: options.drupalURL,
    host: options.drupalURL,
    open: false
  });
});

gulp.task('watch:css', ['styles'], function() {
  return gulp.watch(options.theme.sass + '**/*.scss', options.gulpWatchOptions, ['styles']);
});

gulp.task('watch:lint-and-styleguide', ['styleguide', 'lint:sass'], function() {
  return gulp.watch([
    options.theme.sass + '**/*.scss',
    options.theme.sass + '**/*.hbs'
  ], options.gulpWatchOptions, ['styleguide', 'lint:sass']);
});

gulp.task('watch:js', ['lint:js'], function() {
  return gulp.watch(options.eslint.files, options.gulpWatchOptions, ['lint:js']);
});

// ######################
// Clean all directories.
// ######################
gulp.task('clean', ['clean:css', 'clean:styleguide']);

// Clean style guide files.
gulp.task('clean:styleguide', function() {
  // You can use multiple globbing patterns as you would with `gulp.src`
  return del([
    options.styleGuide.destination + '*.html',
    options.styleGuide.destination + 'public',
    options.theme.css + '**/*.hbs'
  ], {force: true});
});

// Clean CSS files.
gulp.task('clean:css', function() {
  return del([
    options.theme.css + '**/*.css',
    options.theme.css + '**/*.map'
  ], {force: true});
});

// Resources used to create this gulpfile.js:
// - https://github.com/google/web-starter-kit/blob/master/gulpfile.babel.js
// - https://github.com/dlmanning/gulp-sass/blob/master/README.md
// - http://www.browsersync.io/docs/gulp/
