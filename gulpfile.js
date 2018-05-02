let gulp = require('gulp');
const debug  = require('gulp-debug');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify-es').default;
const rename = require('gulp-rename');
const sass   = require('gulp-sass');
const nano   = require('gulp-cssnano');

const image = require('gulp-image');
const merge = require('merge-stream');
const run = require('child_process').exec;

// application paths
let config = {
    nodeDir:  './node_modules',
    bowerDir: './bower_components',
    resDir: './res/assets',
    destDir: './public'
};

// gulp options
var options = {
    sass: {
        outputStyle: 'nested',
        precision: 3,
        errLogToConsole: true,
        includePaths: [
            config.bowerDir + '/bootstrap/scss/'
        ]
    },
    nano: {
        colormin: false
    }
};

// list of core JS files to combine
var jsList = [
    config.bowerDir + '/jquery/dist/jquery.js',
    config.bowerDir + '/popper.js/dist/umd/popper.js',
    config.bowerDir + '/bootstrap/dist/js/bootstrap.js',
    //config.bowerDir + '/bootstrap-select/dist/js/bootstrap-select.js',
    config.bowerDir + '/font-awesome/svg-with-js/js/fontawesome-all.js',
    config.bowerDir + '/jquery.key.js/jquery.key.js',
    config.bowerDir + '/json-forms/dist/js/brutusin-json-forms.js',
    //config.bowerDir + '/json-forms/dist/js/brutusin-json-forms-bootstrap.js'
    config.resDir   + '/js/brutusin-json-forms-bootstrap.js',
    config.nodeDir + '/progressbar.js/dist/progressbar.js',
    config.nodeDir + '/screenfull/dist/screenfull.js'
];

// list of CSS files to combine
var cssList = [
    //config.bowerDir + '/bootstrap/dist/css/bootstrap.css',
    config.resDir   + '/styles/app.scss',
    config.bowerDir + '/json-forms/dist/css/brutusin-json-forms.css',
    //config.bowerDir + '/bootstrap-select/dist/css/bootstrap-select.css'

];

// list of D3-v3 plugins to combine
var jsD3v3List = [
    config.bowerDir + '/d3-v3/d3.js',
    config.bowerDir + '/d3-queue/d3-queue.js',
    config.bowerDir + '/d3-process-map/dist/colorbrewer.js',
    config.bowerDir + '/d3-process-map/dist/geometry.js',
    config.bowerDir + '/cubism/cubism.v1.js',
    config.bowerDir + '/d3-interpolate/d3-interpolate.js'
];

// list of D3-v4 plugins to combine
var jsD3v4List = [
    config.nodeDir + '/d3v4/build/d3.js',
    config.nodeDir + '/d3-dependencyWheel/dist/d3-dependencyWheel.js',
    config.nodeDir + '/d3-calendar/dist/d3-calendar.js',
    //config.resDir   + '/js/d3/**.js'

];

// list of font & font-icons to copy
var iconList = [
    config.bowerDir + '/font-awesome/fonts/**.*'
];

// list of images to process and copy
var imgList = [
    config.resDir + '/images/**/**/*.*',
    config.bowerDir + '/swagger-ui/dist/*.png'
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

gulp.task('js:swagger', function () {
    // swagger-ui scripts
    var jsSwagger = gulp.src([
        config.bowerDir + '/swagger-ui/dist/swagger-ui-bundle.js',
        config.bowerDir + '/swagger-ui/dist/swagger-ui-standalone-preset.js',
        config.bowerDir + '/swagger-ui/dist/swagger-ui.js'])
        .pipe(debug({title: 'swagger:'}))
        .pipe(gulp.dest('./public/js'));
    // swagger-ui css
    var cssSwagger = gulp.src([
        config.bowerDir + '/swagger-ui/dist/swagger-ui.css'])
        .pipe(debug({title: 'swagger:'}))
        .pipe(gulp.dest('./public/css'));

    return merge(jsSwagger,cssSwagger);
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
gulp.task('scripts', ['js:d3v3','js:d3v4','js:swagger','js:core']);

// core application styles
gulp.task('css:core', function () {
    return gulp.src(cssList)
        .pipe(debug({title: 'stylesheet:'}))
        .pipe(sass(options.sass))
        .pipe(nano(options.nano))
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

gulp.task('tool:build-swagger', function (cb) {
    run('"./vendor/bin/swagger" --bootstrap ./sources/swagger.php -e vendor ./sources/', function (err, stdout, stderr) {
        console.log(stdout);
        console.log(stderr);
        cb(err);
    });
});
