<?php
/**
 * @file project.php
 */

namespace clever_systems\mmm_builder;

use clever_systems\mmm_builder\InstallationType\Installation;
use clever_systems\mmm_builder\ServerType\Freistilbox;
use clever_systems\mmm_builder\ServerType\Uberspace;

$project = new Project();
$live = new Freistilbox('c145', 's1786');
$dev = new Uberspace('menkar', 'clsysdev');
$project->addInstallation(new Installation('live', $live, 'http://freiburg.wandelkalender.de'));
$project->addInstallation(new Installation('dev', $dev, 'http://dev.freiburg.wandelkalender.de'));
//$project->addInstallation(new Installations($dev, 'http://{{installation}}.clsysdev.menkar.uberspace.de'));

// Do not forget!
return $project;