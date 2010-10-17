sfThemeGeneratorPlugin
======================

Add custom themes.  Use a theme configuration class to handle how your theme impacts the application

1. Prompt users for required fields
2. Provide default values for other fields

Why do we need a theme generator?
---------------------------------

The Theme Generator exists for _one reason_: to **provide an easy way to generate useful cached code**.  The module generator 
that comes bundled with symfony is impossible to extend.  There is an impossible amount of hardcoded logic, a giant wad of 
mind-numbingly complex code, and too many obstacles standing in the way of you and a happy way to generate code.  The sfThemeGeneratorPlugin
provides a framework that is much more Object Oriented, flexible, and easy to understand.  It's very shy, and tries to stay out of your way.
It just wants to provide common hooks and methods you then use to provide your own theme to your users.


How do I use a theme generator?
-------------------------------

Almost everyone won't have to.  The themes will be created by other developers and they'll just need this plugin to run the tasks.  This plugin
comes with a theme configuration for the "admin" and "default" themes bundled with symfony core.  

    # OLD AND BUSTED
    $ php symfony generate:module myapp my_model
    
    # NEW AND SEXY
    $ php symfony generate:theme default
    Application for this theme:
    $ frontend 
    Model for this theme:
    $ MyModel 
    Module for this theme [my_model]:
    $     
    >> dir+      /path/to/project/apps/myapp/modules/my_model
    >> dir+      /path/to/project/apps/myapp/modules/my_model/actions
    >> file+     /path/to/project/apps/myapp/modules/my_model/actions/actions.class.php
    >> dir+      /path/to/project/apps/myapp/modules/my_model/config
    >> file+     /path/to/project/apps/myapp/modules/my_model/config/generator.yml
    >> dir+      /path/to/project/apps/myapp/modules/my_model/templates
    >> tokens    /path/to/project/apps/myapp/modules/my_model/actions/actions.class.php
    >> tokens    /path/to/project/apps/myapp/modules/my_model/config/generator.yml
    >> generate  Task complete.

So why is this better?  Good question!  It's better because the task itself does not require any arguments other than the name of the theme.
All the requirements are handled _in your theme_.  Well of course they are!  It makes so much sense.  The user is prompted for the required
and optional arguments to create this theme.  Want to know what's also cool?  If you're a PRO and want to skip the prompt (this is also important
when running tests) you can pass the options in directly.  This works transparently for every theme, as I've forced the task itself to allow any
number of options:

    # NEW AND SEXY
    $ php symfony generate:theme default --application=frontend --model=MyModel --accept-defaults
    >> dir+      /path/to/project/apps/myapp/modules/my_model
    >> dir+      /path/to/project/apps/myapp/modules/my_model/actions
    >> file+     /path/to/project/apps/myapp/modules/my_model/actions/actions.class.php
    >> dir+      /path/to/project/apps/myapp/modules/my_model/config
    >> file+     /path/to/project/apps/myapp/modules/my_model/config/generator.yml
    >> dir+      /path/to/project/apps/myapp/modules/my_model/templates
    >> tokens    /path/to/project/apps/myapp/modules/my_model/actions/actions.class.php
    >> tokens    /path/to/project/apps/myapp/modules/my_model/config/generator.yml
    >> generate  Task complete.

Pretty cool, right?