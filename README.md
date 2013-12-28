# WP Framework

A framework for my personal Wordpress projects.

Because it is intended mainly for my personal use, it may not be obvious how to make use of these files. However, I have made an effort to provide inline documentation to allow anyone to try.

## Files

These files are meant to be located in a theme folder

* `functions.php` contains the one line of code that needs to be included in your own theme's corresponding file.
* `classes/`
    * `WPF_Theme.php` A class representing the theme itself and its settings.
    * `WPF_Post_Type.php` An abstract class to be extended with a new class for each custom post type.
    * `post-types/` A directory for classes representing custom post types, which will be loaded automatically.
        * `WPF_Example.php` An example child of the abstract class, used as a template for custom post types.