# Houston #

Houston is a data transformation framework intended for use in middleware layers.

This branch is seriously incomplete and is intended to modernize and rething Houston
rearchitecting around deficiencies in the original codebase.  It is not an entire
rewrite but is a major refactor.

The goals:

- Provide an actual API, don't require you to create your own subclass with a big manually written and largely undocumented data structure
- PSR-0 compliance for integration with other modern PHP projects
- Remove the dependency on the Zend framework
- The ability to use Houston without a canonical database (any Connector can be treated as a canonical store)
- Testing.  BDD style testing for every feature.

## Testing ##

Testing is done using [BDD](http://dannorth.net/introducing-bdd) via [Behat](http://behat.org).  All testing dependencies can be installed using
composer by running composer from inside this directory.  If you are not running the unit tests, you don't need to run composer. While in the 
process of updating Houston from version 1.x to 2.x, there are many features that may be in some state of disrepair but tests should reflect the
code that has been implemented/ported.