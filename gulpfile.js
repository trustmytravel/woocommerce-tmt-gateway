"use strict";

var gulp = require( 'gulp' );
var uglify = require( 'gulp-uglify' );
var rename = require( 'gulp-rename' );

gulp.task( 'compile-scripts', function() {
  return gulp.src( 'js/tmt.js' )
    .pipe(uglify() )
    .pipe(rename( 'tmt.min.js' ) )
    .pipe(gulp.dest( 'js' ) );
});

gulp.task( 'watch', function() {
  gulp.watch( 'js/tmt.js', [ 'compile-scripts' ] );
});

gulp.task( 'default', function() {
  console.log( 'Use the following commands' );
  console.log( '--------------------------' );
  console.log( 'gulp compile-scripts to minify js/tmt.js to tmt.min.js.' );
  console.log( 'gulp watch to continue watching js for changes.' );
});
