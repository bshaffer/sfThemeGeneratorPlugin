sfThemeGeneratorPlugin
======================

Installation
------------

### With git

    git submodule add git://github.com/bshaffer/sfThemeGeneratorPlugin.git plugins/sfThemeGeneratorPlugin
    git submodule init
    git submodule update

### With subversion

    svn propedit svn:externals plugins

In the editor that's displayed, add the following entry and then save

    sfThemeGeneratorPlugin https://svn.github.com/bshaffer/sfThemeGeneratorPlugin.git

Finally, update:

    svn update

### Setup

In your `config/ProjectConfiguration.class.php` file, make sure you have the plugin enabled.

    $this->enablePlugins('sfThemeGeneratorPlugin');

Why do we need a theme generator?
---------------------------------

The Theme Generator exists for _one reason_: to **provide an easy way to generate useful cached code**.  The module generator 
that comes bundled with symfony is very difficult to extend.  There is an impossible amount of hardcoded logic, a giant wad of 
mind-numbingly complex code, and too many obstacles standing in the way of you and a happy way to generate code.  The sfThemeGeneratorPlugin
provides a framework that is much more Object Oriented, flexible, and easy to understand.  It's very shy, and tries to stay out of your way.
It just wants to provide common hooks and methods you then use to provide your own theme to your users.


How do I use a theme generator?
-------------------------------

Almost everyone won't have to.  The themes will be created by other developers and you will just need this plugin to run the tasks.  This plugin
comes with a theme configuration for the `admin` and `default` themes bundled with symfony core.  

    # OLD AND BUSTED
    $ php symfony generate:module myapp my_model
    
    # NEW AND SEXY
    $ php symfony theme:generate default
    Application for this theme:
    $ frontend 
    Model for this theme:
    $ MyModel 
    Module for this theme [my_model]:
    $ my_model

Notice the user is prompted for options they used to pass in up front.  So why is this better?  Good question!  It's better because the task itself 
does not require any arguments other than the name of the theme. All the requirements are handled _in your theme_.  Well of course they are!  It 
makes so much sense.  Want to know what's also cool?  If you're a PRO and want to skip the prompt (this is also important when running the task 
outside the framework) you can pass the options in directly.  This works transparently for every theme, as the theme:generate task itself to allow any
number of options:

    # This works too!
    $ php symfony theme:generate default --application=frontend --model=MyModel --module=my_model

    # So does this!
    $ php symfony theme:generate default --application=frontend --model=MyModel --accept-defaults

The `accept-defaults` option will prevent the plugin from prompting you for default values.  Pretty cool, right?  So what else do you get with this plugin?  
Check out my [sfHadoriThemePlugin](http://github.com/bshaffer/sfHadoriThemePlugin) to really see these themes in action.  You won't be disappointed!

How do I create my own theme?
-----------------------------

Well aren't you ambitious? If you want to create your own theme, it's not very difficult.  Every theme must have a subclass of the sfThemeConfigration 
class.  This controls how your theme is set up.  Your configuration class is mainly responsible for the following things:

- Prompting users for the information needed to generate your theme. (`setup` method)
- Adding routes to the application's routing.yml (`routesToPrepend` method)
- Copying the files from one location (your plugin) to another location in the application (`filesToCopy` method)

Is there anything else really cool I should know about?
-------------------------------------------------------

In fact, there is exactly _one_ other cool thing you should know about.  This plugin comes with the `theme:copy-cache` task, which takes all your
generated code and sticks it *right in your module*!  Why is this fantastic?  If you hark back to the *main purpose of this plugin*, you may recall
that purpose is to **provide an easy way to generate useful cached code**.  Because this code is useful, we often will want to pull it from cache
and customize it from there.  It's great code, after all!  Use this task to copy over your files.  You will be prompted to overwrite existing files,
so don't worry about that.

    # Wow!  How Cool!
    $ php symfony theme:copy-cache frontend my_model

What *haven't* you done in this remarkable plugin?
--------------------------------------------------

I have tried my best to make this plugin the most usable, extendable, and properly coded plugin I possibly can.  There will still be roadblocks and
limitations, but I will work to remove them as soon as I identify them.  Please contact me with any questions, comments, or suggestions!
