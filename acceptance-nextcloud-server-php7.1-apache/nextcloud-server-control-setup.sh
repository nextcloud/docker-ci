#!/bin/bash

# @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
#
# @license GNU AGPL version 3 or any later version
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# Helper script to set up the system as expected by the Nextcloud server control
# and the acceptance tests.
#
# The Nextcloud server is copied to "/var/www/html" from the Git repository
# cloned by Drone. Once installed and configured as expected by the acceptance
# tests, a new Git repository is created in "/var/www/html" and a snapshot of
# the whole directory (no ".gitignore" file is used) is saved to the repository;
# thanks to this, the Nextcloud server control can reset the server to its
# default state when needed.
#
# When running this script the working directory must be the Git repository
# cloned by Drone; the user must be root.

# Exit immediately on errors.
set -o errexit

echo "Copy Nextcloud server to \"var/www/html\" from the Git repository cloned by Drone"
NEXTCLOUD_TAR="$(mktemp --tmpdir="${TMPDIR:-/tmp}" --suffix=.tar nextcloud-XXXXXXXXXX)"

tar --create --file="$NEXTCLOUD_TAR" --exclude=".git" --exclude=".gitignore" --exclude="./build" --exclude="./config/config.php" --exclude="./data" --exclude="./tests" .
tar --append --file="$NEXTCLOUD_TAR" build/acceptance/installAndConfigureServer.sh

cd /var/www/html
tar --extract --file="$NEXTCLOUD_TAR"
rm "$NEXTCLOUD_TAR"

chown -R www-data:www-data /var/www/html/

echo "Install and configure Nextcloud server"
su --shell "/bin/sh" --command "cd /var/www/html/ && build/acceptance/installAndConfigureServer.sh" - www-data

echo "Save the default state so Nextcloud server control can reset to it"
su --shell "/bin/sh" --command "cd /var/www/html/ && git init && git add --all && echo 'Default state' | git -c user.name='John Doe' -c user.email='john@doe.org' commit --quiet --file=-" - www-data
