var gulp          = require('gulp'),
    browserSync   = require('browser-sync'),
    sass          = require('gulp-sass'),
    prefix        = require('gulp-autoprefixer'),
    concat        = require('gulp-concat'),
    cp            = require('child_process'),
    cache         = require('gulp-cache'),
    imagemin      = require('gulp-imagemin'),
    gulpSettings  = require('./gulpsettings.json'),
    notify        = require('gulp-notify');
    plumber       = require('gulp-plumber'),
    gutil         = require('gulp-util'),
    runSequence   = require('run-sequence');

var onError = function (err) {
  gutil.beep();
  console.log(err);
};

/**
 * Launch the Server
 */
 gulp.task('browser-sync', ['sass', 'scripts'], function() {
    browserSync.init({
      // Change as required, also remember to set in theme settings
      proxy: gulpSettings.proxyUrl,
      port: gulpSettings.proxyPort
    });
});

/**
 * @task sass
 * Compile files from scss
 */
gulp.task('sass', function () {
  return gulp.src('assets/scss/*.scss')
  .pipe(plumber({ errorHandler: function(err) {
    notify.onError({
        title: "Gulp error in " + err.plugin,
        message:  err.toString()
    })(err);

    // play a sound once
    gutil.beep();
  }}))
  .pipe(sass())
  .pipe(prefix(['last 2 versions'], { cascade: true }))
  .pipe(gulp.dest('css'))
  .pipe(browserSync.reload({stream:true}))
});

/**
 * @task scripts
 * Compile files from js
 */
gulp.task('scripts', function() {
  return gulp.src(['assets/js/*.js'])
  .pipe(gulp.dest('js'))
  .pipe(browserSync.reload({stream:true}))
});

// Optimizing Images
gulp.task('images', function() {
  return gulp.src('assets/images/**/*.+(png|jpg|jpeg|gif|svg)')
    // Caching images that ran through imagemin
    .pipe(cache(imagemin({
      interlaced: true,
    })))
    .pipe(gulp.dest('images'))
});

/**
 * @task clearcache
 * Clear all caches
 */
gulp.task('clearcache', function(done) {
  return cp.spawn(gulpSettings.drushCommand, ['cache-rebuild'], {stdio: 'inherit'})
  .on('close', done);
});

/**
 * @task reload
 * Refresh the page after clearing cache
 */
gulp.task('reload', ['clearcache'], function () {
  browserSync.reload();
});

/**
 * @task watch
 * Watch scss files for changes & recompile
 * Clear cache when Drupal related files are changed
 */
gulp.task('watch', function () {
  gulp.watch(['assets/scss/*.scss', 'assets/scss/**/*.scss'], ['sass']);
  gulp.watch(['assets/js/*.js'], ['scripts']);
  gulp.watch(['templates/*.html.twig', 'templates/**/*.html.twig', '**/*.yml'], ['reload']);
});

/**
 * Default task, running just `gulp` will
 * compile Sass files, launch BrowserSync, watch files.
 */
gulp.task('default', ['browser-sync', 'watch']);

// Build
gulp.task('build', function(callback) {
  runSequence('sass',
              'scripts',
              'images',
              callback);
});
