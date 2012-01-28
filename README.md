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