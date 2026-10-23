#!/bin/sh
# Grants the database's app user access to the test databases (<database>_test, <database>_test1, ...) on an
# existing data volume. docker/<engine>/init/10-test-database.sh does the same, but the image runs init scripts
# only when it creates the volume. The root password is random, so this restarts the server once with an
# --init-file holding the GRANT; the data is kept. Works with the mysql and mariadb images.
#
# Used by task db:grant-test. Run from the host.
set -eu

cd "$(dirname "$0")/.."

# A missing or empty volume would be initialized first, and the image's temporary init server also logs 'ready for
# connections', so the wait below would stop it halfway. Such a volume gets the grant from docker/<engine>/init.
if ! docker compose run --rm --no-deps -T --entrypoint test database -d /var/lib/mysql/mysql; then
	echo 'db-grant-test: the database volume is not initialized; task up creates it, with the grant' >&2
	exit 1
fi

docker compose stop database

# `_` is a wildcard in database-level grants, so it is escaped: the pattern matches <database>_test% only.
container=$(docker compose run -d --rm --no-deps database sh -c '
	database=${MARIADB_DATABASE:-$MYSQL_DATABASE}
	user=${MARIADB_USER:-$MYSQL_USER}
	pattern="$(printf "%s" "$database" | sed "s/_/\\\\_/g")\\_test%"
	printf "GRANT ALL PRIVILEGES ON \`%s\`.* TO '\''%s'\''@'\''%%'\'';\n" "$pattern" "$user" > /tmp/grant.sql
	echo "db-grant-test: $(cat /tmp/grant.sql)"
	# The bare name: the entrypoints drop to the mysql user only for "mariadbd"/"mysqld", not a full path
	exec docker-entrypoint.sh "$(basename "$(command -v mariadbd || command -v mysqld)")" --init-file=/tmp/grant.sql
')

attempts=60
until docker logs "$container" 2>&1 | grep -q 'ready for connections'; do
	attempts=$((attempts - 1))
	if [ "$attempts" -eq 0 ]; then
		docker logs "$container" >&2 || true
		docker stop "$container" >/dev/null || true
		echo 'db-grant-test: the database server did not start' >&2
		exit 1
	fi
	sleep 1
done

docker logs "$container" 2>&1 | grep '^db-grant-test:' || true
docker stop "$container" >/dev/null
docker compose up -d --wait database
echo 'db-grant-test: done'
