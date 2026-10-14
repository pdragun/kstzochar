# Sourced by the MySQL image entrypoint on first initialization of an empty
# data directory (for an existing volume run `task db:grant-test`). Creates the
# test database Doctrine selects in APP_ENV=test (`dbname_suffix`, `<db>_test`)
# and grants the application user access to the test databases (`<db>_test`
# plus an optional TEST_TOKEN).
#
# The pattern escapes `_`, which is a wildcard in database-level grants, so it
# matches `<db>_test`, `<db>_test1`, ... but not `<db>` itself.

test_database_pattern="$(printf '%s' "${MYSQL_DATABASE}" | sed 's/_/\\_/g')\\_test%"

mysql_note "Creating ${MYSQL_DATABASE}_test, granting ${MYSQL_USER} access to databases matching ${test_database_pattern}"
docker_process_sql --database=mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${test_database_pattern}\`.* TO '${MYSQL_USER}'@'%';
SQL
