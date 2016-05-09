module.exports = function(grunt) { 

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out         = 'output';

    /**
     * Remove bundled and bundling files
     */
    clean.ltideliveryproviderbundle = [out];
    
    /**
     * Compile tao files into a bundle 
     */
    requirejs.ltideliveryproviderbundle = {
        options: {
            baseUrl : '../js',
            dir : out,
            mainConfigFile : './config/requirejs.build.js',
            paths : { 'ltiDeliveryProvider' : root + '/ltiDeliveryProvider/views/js' },
            modules : [{
                name: 'ltiDeliveryProvider/controller/routes',
                include : ext.getExtensionsControllers(['ltiDeliveryProvider']),
                exclude : ['mathJax'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.ltideliveryproviderbundle = {
        files: [
            { src: [out + '/ltiDeliveryProvider/controller/routes.js'],  dest: root + '/ltiDeliveryProvider/views/js/controllers.min.js' },
            { src: [out + '/ltiDeliveryProvider/controller/routes.js.map'],  dest: root + '/ltiDeliveryProvider/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('ltideliveryproviderbundle', ['clean:ltideliveryproviderbundle', 'requirejs:ltideliveryproviderbundle', 'copy:ltideliveryproviderbundle']);
};
