var gulp = require( 'gulp' ),

    sass = require( 'gulp-sass' ),
    autoprefixer = require( 'gulp-autoprefixer' ),

    stripCssComments = require( 'gulp-strip-css-comments' ),
    csscomb = require( 'gulp-csscomb' ),
    replace = require( 'gulp-replace' ),

    rename = require( 'gulp-rename' ),

    cssnano = require( 'gulp-cssnano' ),
    uglify = require( 'gulp-uglify' ),
    saveLicense = require('uglify-save-license'),

    gettext = require( 'gulp-gettext' ),

    // doesn't break pipe on error
    // so we don't need to restart gulp
    plumber = require( 'gulp-plumber' ),
    gutil = require( 'gulp-util' ),
    onError = function ( error ) {
        gutil.log( gutil.colors.red( 'ERROR from ' + error.plugin ) + ':', (error.messageOriginal||'').replace( /\.$/, '' ), 'in ' + error.relativePath + ' (line ' + error.line + ')' );
        this.emit( 'end' );
    };

/**
 * Compile scss to css.
 */
var scssFiles = ['./css/**/*.scss'];
gulp.task( 'css', function () {
    return gulp.src( scssFiles )
        .pipe( plumber( { errorHandler: onError } ) )

        .pipe( sass() )
        .pipe( stripCssComments() )
        .pipe( autoprefixer( {
            overridebrowserslist: ['last 3 versions']
        } ) )

        // beautify css
        .pipe( csscomb() )
        // in addition to csscomb (didn't found any options for this)
        // ... add a blank line between two instructions
        .pipe( replace( /(}|\*\/)\n*(\.|\[|#|@|\w|\s*\d)/g, "$1\n\n$2" ) )
        // ... remove blank lines in instruction
        .pipe( replace( /;\s*\n(\s*\n)+/g, ";\n" ) )

        .pipe( gulp.dest( function (file) {
            return file.base;
        } ) )

        // rename and minimize to FILENAME.min.css
        .pipe( rename( { suffix: '.min' } ) )
        .pipe( cssnano( { minifyFontValues: false, discardUnused: false, zindex: false, reduceIdents: false } ) )

        .pipe( gulp.dest( function (file) {
            return file.base;
        } ) );
} );

/**
 * Compress and uglify js files.
 */
var jsFiles = ['./js/**/*.js', '!./**/*.min.js'];
gulp.task( 'js', function () {
    return gulp.src( jsFiles.concat( '!./**/_*.js' ) )
        .pipe( plumber( { errorHandler: onError } ) )
        .pipe( rename( { suffix: '.min' } ) )
        .pipe( uglify( { output: { comments: saveLicense } } ) )
        .pipe( gulp.dest( function (file) {
            return file.base;
        } ) );
} );

/**
 * Compile .po files to .mo
 */
var poFiles = ['./languages/**/*.po'];
gulp.task( 'po2mo', function () {
    return gulp.src( poFiles )
        .pipe( gettext() )
        .pipe( gulp.dest( function (file) {
            return file.base;
        } ) );
} );

/**
 * Watch tasks.
 *
 * Init watches by calling 'gulp' in terminal.
 */
gulp.task( 'default', gulp.series( gulp.parallel( 'css', 'js', 'po2mo' ), watchers = ( done ) => {
    gulp.watch( scssFiles, gulp.series( 'css' ) );
    gulp.watch( jsFiles, gulp.series( 'js' ) );
    gulp.watch( poFiles, gulp.series( 'po2mo' ) );

    done();
} ) );

/**
 * Clear build/ folder.
 */
var del = require( 'del' ), // deletion
    concat = require( 'gulp-concat' ); // concat files

gulp.task( 'clear:build', function(done) {
    del.sync( 'build/**/*' );
    done();
} );

gulp.task( 'build', gulp.series( 'clear:build', gulp.parallel( 'css', 'js', 'po2mo' ), building = (done) => {
    // collect all needed files
    gulp.src( [
        '**/*',
        // ... but:
        '!wordpress-plugin-facebook-feed.php',
        '!**/*.scss',
        '!**/*.css', // will be collected see next function
        '!*.md',
        '!LICENSE',
        '!readme.txt',
        '!gulpfile.js',
        '!package.json',
        '!package-lock.json',
        '!.csscomb.json',
        '!.gitignore',
        '!node_modules{,/**}',
        '!build{,/**}',
        '!assets{,/**}'
    ] ).pipe( gulp.dest( 'build/' ) );

    // collect css files
    gulp.src( [ '**/*.css', '!node_modules{,/**}' ] )
        .pipe( gulp.dest( 'build/' ) );

    // concat files for WP's readme.txt
    // manually validate output with https://wordpress.org/plugins/about/validator/
    gulp.src( [ 'readme.txt', 'README.md', 'CHANGELOG.md' ] )
        .pipe( concat( 'readme.txt' ) )
        // remove screenshots
        // todo: scrennshot section for WP's readme.txt
        .pipe( replace( /\n\!\[image\]\([^)]+\)\n/g, '' ) )
        // WP markup
        .pipe( replace( /#\s*(Changelog)/g, "## $1" ) )
        .pipe( replace( /##\s*([^(\n)]+)/g, "== $1 ==" ) )
        .pipe( replace( /==\s(Unreleased|[0-9\s\.-]+)\s==/g, "= $1 =" ) )
        .pipe( replace( /#\s*[^\n]+/g, "== Description ==" ) )
        .pipe( gulp.dest( 'build/' ) );

    done();
} ) );
