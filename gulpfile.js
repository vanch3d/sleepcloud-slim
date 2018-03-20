var gulp = require('gulp');
const debug = require('gulp-debug');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

const sass = require('gulp-sass');
const nano = require('gulp-cssnano');

const image = require('gulp-image');

// application paths
var config = {
    bowerDir: './bower_components',
    resDir:   './res/assets',
    destDir:  './public'
};

// list of core JS files to combine
var jsList = [
    config.bowerDir + '/jquery/dist/jquery.js',
    config.bowerDir + '/bootstrap/dist/js/bootstrap.js',
    config.bowerDir + '/font-awesome/svg-with-js/js/fontawesome-all.js',
    config.bowerDir + '/jquery.key.js/jquery.key.js'
];

// list of CSS files to combine
var cssList = [
    config.bowerDir + '/bootstrap/dist/css/bootstrap.css',
    config.resDir   + '/styles/app.scss'
];

// list of D3-v3 plugins to combine
var jsD3v3List = [
    config.bowerDir + '/d3-v3/d3.js',
    config.bowerDir + '/d3-process-map/dist/colorbrewer.js',
    config.bowerDir + '/d3-process-map/dist/geometry.js',
    config.bowerDir + '/cubism/cubism.v1.js'
];

// list of D3-v4 plugins to combine
var jsD3v4List = [
    config.bowerDir + '/d3/d3.js'
];

// list of font & font-icons to copy
var iconList = [
    config.bowerDir + '/font-awesome/fonts/**.*'
];

// list of images to process and copy
var imgList = [
    config.resDir + '/images/**/**/*.*'
];

// d3 bundle: d3 & all addons minified in a single package, separate from core
gulp.task('js:d3v3', function () {
    return gulp.src(jsD3v3List)
        .pipe(debug({title: 'd3 (v3):'}))
        .pipe(concat('d3.v3-bundle.js'))
        .pipe(gulp.dest('./public/js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./public/js'));
});

// d3 bundle: d3 & all addons minified in a single package, separate from core
gulp.task('js:d3v4', function () {
    return gulp.src(jsD3v4List)
        .pipe(debug({title: 'd3 (v4):'}))
        .pipe(concat('d3.v4-bundle.js'))
        .pipe(gulp.dest('./public/js'))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./public/js'));
});

// core application scripts
gulp.task('js:core', function () {
    return gulp.src(jsList)
        .pipe(debug({title: 'script:'}))
        .pipe(concat('app.js'))
        .pipe(gulp.dest('./public/js'))
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest('./public/js'));
});

// reprocess all scripts
gulp.task('scripts', ['js:d3v3','js:d3v4','js:core']);

// core application styles
gulp.task('css:core', function () {
    return gulp.src(cssList)
        .pipe(debug({title: 'stylesheet:'}))
        .pipe(sass())
        .pipe(nano())
        .pipe(concat('app.css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./public/css'))
});

// reprocess all scripts
gulp.task('styles', ['css:core']);

gulp.task('assets:icons', function() {
    return gulp.src(iconList)
        .pipe(debug({title: 'icons:'}))
        .pipe(gulp.dest('./public/fonts'));
});

gulp.task('assets:images', function () {
    return gulp.src(imgList)
        .pipe(image({concurrent: 10}))
        .pipe(gulp.dest('./public/images'));
});

gulp.task('assets', ['assets:icons','assets:images']);

gulp.task('default', ['styles', 'scripts', 'assets']);
