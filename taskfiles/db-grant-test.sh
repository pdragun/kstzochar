#!/bin/sh
# Grants the database's app user access to the test databases (<database>_test, <database>_test1, ...) on an
# existing data volume. docker/<engine>/init/10-test-database.sh does the same, but the image runs init scripts
# only when it creates the volume. The root password is random, so this restarts the server once with an
# --init-file holding the GRANT; the data is kept. Works with the mysql and mariadb images.
#
# Used by task db:grant-test. Run from the host; needs jq.
set -eu

cd "$(dirname "$0")/.."

# A missing or empty volume would be initialized first, and the image's temporary init server also logs 'ready for
# connections', so the wait below would stop it halfway. Such a volume gets the grant from docker/<engine>/init.
if ! docker compose run --rm --no-deps -T --entrypoint test database -d /var/lib/mysql/mysql; then
	echo 'db-grant-test: the database volume is not initialized; task up creates it, with the grant' >&2
	exit 1
fi

# The service's own command: flags (character set, ...), shell-quoted, so the temporary server runs like the real one
flags=$(docker compose config --format json | jq -r '.services.database.command // [] | map(@sh) | join(" ")')

# From here on, any way out (an error, Ctrl+C) removes the temporary server and starts the database again. dash, the
# sh of Debian and Ubuntu, runs the EXIT trap on exit only, so the signals exit explicitly.
container=
restart=yes
cleanup() {
	[ -z "$container" ] || docker rm -f "$container" >/dev/null 2>&1 || true
	[ -z "$restart" ] || docker compose up -d database >/dev/null 2>&1 || true
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM HUP

docker compose stop database

# Not --rm: a server that exits early keeps its logs, which say why. cleanup removes the container.
# `_` is a wildcard in database-level grants, so it is escaped: the pattern matches <database>_test% only.
container=$(docker compose run -d --no-deps -e GRANT_FLAGS="$flags" database sh -c '
	database=${MARIADB_DATABASE:-$MYSQL_DATABASE}
	user=${MARIADB_USER:-$MYSQL_USER}
	pattern="$(printf "%s" "$database" | sed "s/_/\\\\_/g")\\_test%"
	printf "GRANT ALL PRIVILEGES ON \`%s\`.* TO '\''%s'\''@'\''%%'\'';\n" "$pattern" "$user" > /tmp/grant.sql
	echo "db-grant-test: $(cat /tmp/grant.sql)"
	eval "set -- $GRANT_FLAGS"
	# A command that names the server binary itself: keep only its flags
	case "${1:-}" in -*|"") ;; *) shift ;; esac
	# The bare name: the entrypoints drop to the mysql user only for "mariadbd"/"mysqld", not a full path
	exec docker-entrypoint.sh "$(basename "$(command -v mariadbd || command -v mysqld)")" "$@" --init-file=/tmp/grant.sql
')

fail() {
	docker logs "$container" >&2 || true
	echo "db-grant-test: $1" >&2
	exit 1
}

attempts=60
until docker logs "$container" 2>&1 | grep -q 'ready for connections'; do
	[ "$(docker inspect -f '{{.State.Running}}' "$container")" = true ] || fail 'the database server exited'
	attempts=$((attempts - 1))
	[ "$attempts" -gt 0 ] || fail 'the database server did not start'
	sleep 1
done

docker logs "$container" 2>&1 | grep '^db-grant-test:' || true
docker stop "$container" >/dev/null
docker compose up -d --wait database
restart=

# The server logs a failed GRANT in the init file and starts anyway, so check the app user's grants for exactly
# this database's pattern: exit 3 without it. Anything else that fails (the client can't connect yet, a service
# without a healthcheck that is still starting, the exec itself) gets a few more tries.
attempts=30
while :; do
	status=0
	docker compose exec -T database sh -c '
		database=${MARIADB_DATABASE:-$MYSQL_DATABASE}
		pattern="$(printf "%s" "$database" | sed "s/_/\\\\_/g")\\_test%"
		grants=$("$(command -v mariadb || command -v mysql)" -u"${MARIADB_USER:-$MYSQL_USER}" \
			-p"${MARIADB_PASSWORD:-$MYSQL_PASSWORD}" -N -r -e "SHOW GRANTS") || exit 2
		printf "%s\n" "$grants" | grep -qF "\`$pattern\`" || exit 3
	' || status=$?
	[ "$status" -ne 0 ] && [ "$status" -ne 3 ] || break
	attempts=$((attempts - 1))
	[ "$attempts" -gt 0 ] || { echo 'db-grant-test: could not connect as the app user to check its grants' >&2; exit 1; }
	sleep 1
done
if [ "$status" -ne 0 ]; then
	docker logs "$container" 2>&1 | grep -i 'error' >&2 || true
	echo 'db-grant-test: the app user has no grant on the test databases (server errors above)' >&2
	exit 1
fi
echo 'db-grant-test: done'
