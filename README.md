# moodle-mod_observation

Create and manage observation assessments. This plugin is primarily designed 
for educational facilities as an additional method to conduct assessment tasks 
in courses requiring lots of practical work such as nursing and chemistry, among others.

## Development Version
Warning! Never use the development version in production, there are no guarantees for which state the development branches are in at a given time.

## Installation

1. Navigate to `moodle/siteroot/mod`

```
git clone git@github.com:catalyst/moodle-mod_observation.git observation
```

2. Enabling The Plugin
In Moodle, go to administrator -> plugin overview, and press 'Update database'.

## Settings
Settings can be found at: Site Administration -> Plugins -> Activity Modules -> observation

## Running Tests
1. To setup the testing environment run:
```
./control web
composer install
php admin/tool/phpunit/cli/init.php
```
2. To run Observation plugin tests:
```
./control web
./vendor/bin/phpunit --mod_observation
```

## License
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
