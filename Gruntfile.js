module.exports = function(grunt) {

  'use strict';

  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    connect: {
      server: {
        options: {
          port: 9000,
          base: 'dist',
          hostname: 'localhost',
          livereload: true,
          open: false
        }
      }
    },

    watch: {
      handlebars: {
        files: ['src/templates/**/*.hbs', 'src/templates/**/*.json', 'src/templates/layout.html '],
        tasks: 'handlebarslayouts'
      },
      sass: {
        files: ['src/sass/**/*.scss'],
        tasks: ['sass']
      },
      js: {
        files: ['src/js/**/*.js'],
        tasks: ['jshint', 'concat', 'uglify']
      },
      gruntfile: {
        files: ['Gruntfile.js'],
        tasks: ['handlebarslayouts', 'sass', 'jshint', 'concat', 'uglify']
      },
      options: {
        livereload: true,
      }
    },

    handlebarslayouts: {
      dist: {
        files: [{
          expand: true,
          cwd: 'src/templates/',
          src: ['**/*.hbs', '!partials/*'],
          dest: 'dist/',
          ext: '.html',
        }],
        options: {
          partials: ['src/templates/partials/*.hbs', 'src/templates/layout.html'],
          basePath: 'src/templates/',
          modules: ['src/templates/helpers/helpers-*.js'],
          context: {
            title: 'MOSHI MOSH <%= grunt.filename %>',
            projectName: 'Grunt handlebars layout',
            items: [
              'apple',
              'orange',
              'banana'
            ]
          }
        }
      }
    },

    sass: {
      dist: {
        options: {
          style: 'compressed',
          sourcemap: 'none',
          noCache: true
        },
        files: [{
          expand: true,
          cwd: 'src/styles/',
          src: ['**/**.scss'],
          dest: 'dist/assets/css/',
          ext: '.min.css'
        }]
      }
    },

    jshint: {
      files: ['src/js/*.js'],
      options: {
        globals: {
          jQuery: true,
          console: true,
          module: true,
          document: true
        }
      }
    },

    concat: {
      options: {
        separator: ';\n\n',
      },
      dist: {
        files: {
          'dist/assets/js/main.js': ['src/js/vendor/jquery.min.js', 'src/js/main.js']
        },
      },
    },

    uglify: {
      dist: {
        files: {
          'dist/assets/js/main.min.js': ['dist/assets/js/main.js']
        }
      }
    },

    clean: {
      dist: {
        src: ['dist/']
      }
    },

    copy: {
      main: {
        files: [{
          expand: true,
          cwd: 'src/public/',
          src: ['**'],
          dest: 'dist/'
        }]
      }
    },

    sitemap: {
      dist: {
        pattern: ['**/*.html'],
        siteRoot: 'dist/',
        homepage: 'http://www.praktijkcentrumlochristi.be/'
      }
    }

  });

  // load tasks
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks("grunt-handlebars-layouts");
  grunt.loadNpmTasks('grunt-html-prettyprinter');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-sitemap');

  // commands
  grunt.registerTask('default', ['clean', 'copy', 'handlebarslayouts', 'sass', 'jshint', 'concat', 'uglify', 'connect', 'watch']);
  grunt.registerTask('build', ['clean', 'copy', 'handlebarslayouts', 'sass', 'jshint', 'concat', 'uglify', 'sitemap']);
  grunt.registerTask('server', ['connect', 'watch']);
  grunt.registerTask('sitemap', ['sitemap']);

};