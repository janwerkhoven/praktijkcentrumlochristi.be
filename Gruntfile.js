const sass = require('node-sass');

module.exports = function(grunt) {

  'use strict';

  require('time-grunt')(grunt);

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
        tasks: ['handlebarslayouts', 'sitemap']
      },
      sass: {
        files: ['src/styles/**/*.scss'],
        tasks: ['sass', 'postcss']
      },
      js: {
        files: ['src/js/**/*.js'],
        tasks: ['jshint', 'uglify', 'concat', 'clean:temp']
      },
      gruntfile: {
        files: ['Gruntfile.js'],
        tasks: ['build']
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
          modules: ['src/templates/helpers/helpers-*.js']
        }
      }
    },

    sass: {
      dist: {
        options: {
          implementation: sass,
          style: 'compressed',
          sourcemap: 'none',
          noCache: true
        },
        files: {
          'dist/assets/css/main.min.css': ['src/styles/main.scss']
        }
      }
    },

    postcss: {
      options: {
        map: false,
        remove: false,
        processors: [
          require('autoprefixer')({
            browsers: ['> 1%', 'Explorer > 9', 'Firefox >= 17', 'Chrome >= 10', 'Safari >= 6', 'iOS >= 6'],
            cascade: false
          }),
          require('cssnano')()
        ]
      },
      dist: {
        src: 'dist/assets/css/main.min.css'
      }
    },

    jshint: {
      files: ['src/js/main.js'],
      options: {
        globals: {
          jQuery: true,
          console: true,
          module: true,
          document: true
        }
      }
    },

    uglify: {
      dist: {
        files: {
          'temp/main.min.js': ['src/js/main.js']
        }
      }
    },

    concat: {
      options: {
        separator: ';\n\n',
      },
      dist: {
        files: {
          'dist/assets/js/main.min.js': ['src/js/vendor/jquery.min.js', 'temp/main.min.js']
        },
      },
    },

    clean: {
      dist: {
        src: ['dist/']
      },
      temp: {
        src: ['temp/']
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

    // BUG: Adds /dist to every URL...
    xml_sitemap: {
      custom_options: {
        options: {
          siteRoot: 'http://www.praktijkcentrumlochristi.be/',
          changefreq: 'monthly',
          priority: '0.5',
          dest: 'dist/'
        },
        files: [{
          expand: true,
          cwd: 'dist/',
          src: ['**/*.html', '!**/google*.html'],
        }]
      }
    },

    // Temporary until sitemap bug is fixed
    replace: {
      sitemap_dist: {
        src: 'dist/sitemap.xml',
        dest: 'dist/sitemap.xml',
        replacements: [{
          from: '/dist/',
          to: '/'
        }, {
          from: '<feed>',
          to: ''
        }, {
          from: '</feed>',
          to: ''
        }]
      }
    }

  });

  // load tasks
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-html-prettyprinter');
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-text-replace');
  grunt.loadNpmTasks('grunt-xml-sitemap');
  grunt.loadNpmTasks("grunt-handlebars-layouts");

  // commands
  grunt.registerTask('default', ['build', 'serve']);
  grunt.registerTask('build', ['clean:dist', 'copy', 'handlebarslayouts', 'sass', 'postcss', 'jshint', 'uglify', 'concat', 'sitemap', 'clean:temp']);
  grunt.registerTask('serve', ['connect', 'watch']);
  grunt.registerTask('sitemap', ['xml_sitemap', 'replace:sitemap_dist']);

};
