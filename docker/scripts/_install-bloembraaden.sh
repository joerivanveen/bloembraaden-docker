#!/usr/bin/env bash

install-bloembraaden() {
  echo "Creating database"

  echo "SELECT 'CREATE DATABASE $POSTGRES_DB' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$POSTGRES_DB')\gexec" | psql -h postgres
}
