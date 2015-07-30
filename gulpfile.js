var gulp = require('gulp');

gulp.task('default', function() {
    console.log('Copying files');
    /*
    You can do this 'src' stuff nicer, but I'm using it this way for now so moo! :)
     */
    gulp.src(
        [
            'bower_components/materialize/bin/materialize.js',
        ]
    ).pipe(gulp.dest('web/js/'));

    console.log('Copied JS');

    gulp.src(
        [
            'bower_components/materialize/bin/materialize.css',
        ]
    ).pipe(gulp.dest('web/css/'));

    console.log('Copied CSS');


    gulp.src(
        [
            'bower_components/materialize/font/**/*',
        ]
    ).pipe(gulp.dest('web/font/'));

    console.log('Copied fonts');
});