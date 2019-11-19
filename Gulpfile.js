const gulp = require('gulp');
const $ = require('gulp-load-plugins')();
const ts = require('gulp-typescript');
const tsProject = ts.createProject('tsconfig.json');

function sass() {
    return gulp.src('Resources/Private/Scss/administration-*.scss')
        .pipe($.sass({
            outputStyle: 'compressed' // if css compressed **file size**
        })
            .on('error', $.sass.logError))
        .pipe(gulp.dest('Resources/Public/Css'));
}

function typescript() {

    return tsProject.src()
        .pipe(tsProject())
        .js.pipe(gulp.dest('Resources/Public/JavaScript/'));

}

function watch() {
    gulp.watch("Resources/Private/Scss/**/*.scss", sass);
    gulp.watch("Resources/Private/Typescript/*.ts", typescript);
}

gulp.task('sass', sass);
gulp.task('ts', typescript);
gulp.task('default', gulp.series('sass', 'ts', watch));
