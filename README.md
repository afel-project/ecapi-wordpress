# Entity-Centric API integration for Wordpress 4.2

A set of utilities for handling and consuming data sources for ECApi from within a Wordpress installation

## Requirements

To __build__ the project you will need to have [Bower](http://bower.io) and [Composer](http://getcomposer.org) installed. At the risk of stating the obvious, this is because this package does ___not___ come with the PHP and JavaScript dependencies of the project bundled with it.

To __run__ the project after building it, you will need a Wordpress installation (so far only [version 4.2](https://codex.wordpress.org/Version_4.2) has been tested), preferably on a *AMP stack.

## Building
From within the root dir of the project:

    $ cd plugins/ecapi

Then, assuming Composer is installed either there or globally on your system:

    $ php composer.phar install

Finally, stage the whole `ecapi` directory into the `wp-content/plugins` of your Wordpress.
