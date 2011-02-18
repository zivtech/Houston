# Houston

From the website: Houston provides you with a central command center for your local and cloud-based web systems and gives you the ability to integrate best-of breed technologies like Drupal, Alfresco, and Salesforce.com, into a single cohesive and powerful application.

Houston provides a central command center for all of your cloud connected applications. Houston allows you to create your ideal data model and then map your objects, fields and files to remote systems. This ensures that your data is always up to date and that you are never caught with any vender lock in. Houston was designed from the ground up with robustness and reliability in mind.

For more general information, see [the website](http://www.houstoncommand.com/).

## For Developers

Houston is a PHP application that can be used to synchronize data.  A slightly modified version is already being used in production, but what is currently contained in this repository is a alpha release of a generalized version.

Houston is a data transformation and synchronization framework that has support baked in for pulling and pushing data to and from other services (to date we have only released Salesforce and Drupal integration code), a sophisticated job queue for fault tolerance, and a parent child model that make complex data structures easier to work with across systems.  Houston tries to take the repetitive tasks out of the equation while leaving all of the methods to you for further overriding.  It doesn't have an installer, just MySQL dumps for structure and it doesn't have an interface or documentation at all, yet.

Currently this release of Houston can manage updating nodes in one instance of Drupal 6 and objects in Salesforce, though any number of Drupal (or other) sites can read the data from a central location.  Plans for using Services module to accommodate updating multiple Drupal sites over web services are in the works. 

## Installation

1. Read all of the code.  This code is pre-alpha and there are lots of TODO's you should probably know about.
2. Import the `houston_variables` and `houston_queue` tables into the db you plan to use.
3. Add Houston's `/lib` folder to your php includes file.
4. Ensure that you have defined Houston's constants prior to loading Houston data objects.
5. If you plan to use Houston with Salesforce, download the Salesforce [phptoolkit](http://wiki.developerforce.com/index.php/PHP_Toolkit_11.0) and place it inside Controllers/Salesforce
6. Download the [Zend Framework](http://framework.zend.com/download/latest) and place it in the lib folder in a folder called 'Zend'
7. If you're using Drupal 6, create a symlink from sites/all/modules/houston to the `drupal_modules` folder


Code like this should be placed somewhere before you start trying to load houston objects (in Drupal, probably in settings.php) is required. 

    <?php
    define("HOUSTON_DB", 'your_db');
    // Note:  If you have a different base directory, then change this.
    define("HOUSTON_BASE_DIR", '/var/www/houstons_parent_dir');
    set_include_path(get_include_path() . PATH_SEPARATOR . HOUSTON_BASE_DIR . '/houston/lib');
    ?>

After that, you can start writing your data objects based on the WidgetCo example found in `/lib/Houston/Extensions/Wdigetco` to model your data and then use the helper function in the houston module (or copy it into your php app) as a factory function to load you data objects.
