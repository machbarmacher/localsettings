<?php
/**
 * @file project.php
 */

namespace clever_systems\mmm-builder;

use clever_systems\mmm-builder\InstallationType\Installation;
use clever_systems\mmm-builder\ServerType\Freistilbox;
use clever_systems\mmm-builder\ServerType\Uberspace;

$project = new Project();
$live = new Freistilbox('c145', 's1786');
$dev = new Uberspace('menkar', 'clsysdev');
$project->addInstallation(new Installation('live', $live, 'http://freiburg.wandelkalender.de'));
//$project->addInstallation(new Installations($dev, 'http://{{installation}}.clsysdev.menkar.uberspace.de'));
