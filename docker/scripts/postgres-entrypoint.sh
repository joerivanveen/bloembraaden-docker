#!/usr/bin/env bash
set -e

# Source certificate generation functions
source /usr/local/bin/_postgres-certs.sh

echo "PostgreSQL SSL Certificate Setup"
echo "================================="

# Generate/validate SSL certificates
generate-postgres-certificates

echo ""
echo "Starting PostgreSQL..."
echo "====================="

# Execute the official PostgreSQL entrypoint with all arguments
exec docker-entrypoint.sh "$@"
