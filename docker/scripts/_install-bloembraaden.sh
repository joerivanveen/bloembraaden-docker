#!/usr/bin/env bash

install-bloembraaden() {
  echo "Creating databases"

  echo "SELECT 'CREATE DATABASE $POSTGRES_DB' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$POSTGRES_DB')\gexec" | psql -h postgres
  echo "SELECT 'CREATE DATABASE $POSTGRES_DB_HISTORY' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$POSTGRES_DB_HISTORY')\gexec" | psql -h postgres
}
