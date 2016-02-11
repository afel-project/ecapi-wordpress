# Entity-Centric API integration for WordPress 4

A set of utilities for handling and consuming data sources for ECApi from within a WordPress installation

## Requirements

To __build__ the project you will need to have [Bower](http://bower.io) and [Composer](http://getcomposer.org) installed. At the risk of stating the obvious, this is because this package does _not_ come with the PHP and JavaScript dependencies of the project bundled with it.

To __run__ the project after building it, you will need a WordPress 4 installation (so far only [version 4.3](http://codex.wordpress.org/Version_4.3) has been tested), and by extension a *AMP stack.

## Building
From within the root dir of the project:

    $ cd plugins/ecapi

Then, assuming Composer is installed either there or globally on your system:

    $ php composer.phar install

The above command will also run Bower automatically as a post-install hook.

Finally, stage the whole `ecapi` directory into the `wp-content/plugins` of your WordPress.
