localsettings Todo
==================

* write aliases and sites in our dir and include them

* Make file wrinting more foolproof: Include existing content, prompt overwrite for some files.

* scaffold Boxfile depending on installations and docroot
* symink docroot if needed
* remove installation / environment separation in base_url
* consider renaming installation => instance

DynamicInstallations
--------------------

// $project->addInstallation(new DynamicInstallations($dev, 'http://{{installation}}.clsysdev.menkar.uberspace.de'));
// @todo Figure out how to do the dynamic part.

* aliases: 
    * Remote aliases: only include configurable basic installations
    * Local aliases: include all installations found
* sites.php:
    * add and use runtime installation variable
    * only match relevant part
    * OR (if necessary) use smart array to implement wildcards
* baseurls
    * add and use runtime installation variable
* databases
    * add and use runtime installation variable
* Merge baseurls and databases to .compiled.php

// baseurls & databases use runtime-environment-select. 
$installation = Runtime::getInstallation();
$base_url = Runtime::getEnvironment()->select([
  'fsb' => 'http://foo.com',
  'uberspace' => "http://$installation.clsys.norma.uberspace.de"
]);


Runtime
=======

Tools
=====

* @sitesorself

Ops
===

* scaffold (compiler plugin)
* create user accounts
* policy-restrictions
* pull-content
* diagnose & correct
