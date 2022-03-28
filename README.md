# moodle-mod_observation

This plugin allows easy management of observational assessments. 

These assessment usually involve an 'observer' (such as a teacher, tutor, supervisor, etc) and an 'observee' (student, employee, etc). The observer watches the observee as they do a process (such as pour drinks at a bar or play piano), recording if they completed certain 'observation points' during the process. 

These 'observation points' could be key assessment criteria, such as health and safety requirements or specific processes the observee must do. Currently three different types are supported: Pass/fail, Text feedback, and File upload (e.g. images). Observers can record a response and assign a mark for each point.

After an observation session is complete, marks and feedback are stored in the Moodle gradebook for review.

This plugin also supported a timeslot management system, allowing timeslots to be created, assigned to observers and joined by observees - notifications included.

## Branches
| Moodle Version      | Branch |
| ----------- | ----------- |
| 3.5 - 3.9      | main       |
| 4.0 | MOODLE_400_STABLE |

This plugin has been tested on Moodle 3.9 and 3.5. Other versions are only assumed to be supported.

![example workflow](https://github.com/catalyst/moodle-mod_observation/actions/workflows/ci.yml/badge.svg)

## Installation

1. Clone the plugin into your moodle instance
```
git clone git@github.com:catalyst/moodle-mod_observation.git mod/observation
```
2. Run install / upgrade script
   
```
php admin/cli/upgrade.php
```

## Contributing and Support
Issues, and pull requests using github are welcome and encouraged!

https://github.com/catalyst/moodle-mod_observation/issues

If you would like commercial support or would like to sponsor additional improvements to this plugin please contact us:

https://www.catalyst-au.net/contact-us

# Credits

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](pix/catalyst.svg)

## License
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
