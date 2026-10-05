#!/bin/bash
# Sourced by the MySQL image entrypoint once, on the first start of an empty data volume.
# Doctrine appends "_test" to the database name in APP_ENV=test (config/packages/doctrine.yaml).
docker_process_sql --database=mysql <<-EOSQL
	CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO '${MYSQL_USER}'@'%';
EOSQL
