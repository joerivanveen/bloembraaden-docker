#!/usr/bin/env bash

install-bloembraaden() {
  echo "Creating databases"

  echo "SELECT 'CREATE DATABASE $POSTGRES_DB' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$POSTGRES_DB')\gexec" | psql -h postgres
  echo "SELECT 'CREATE DATABASE $POSTGRES_DB_HISTORY' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$POSTGRES_DB_HISTORY')\gexec" | psql -h postgres

  echo "Generate config.json" #TODO fill in the vars from env, or make it better
  echo $(pwd)

  echo "Setup cron jobs"
	CRON_FILE="/var/spool/cron/crontabs/www-data"

	if [ ! -f $CRON_FILE ]; then
	   echo "creating cron file"
	   touch $CRON_FILE
	   /usr/bin/crontab $CRON_FILE
	fi

	grep -qi "bloembraaden/" $CRON_FILE
	if [ $? != 0 ]; then
	   echo "Updating cron job"
           /bin/echo "*/1 * * * * php usr/share/nginx/bloembraaden/Daemon.php 0 > /dev/null 2>&1" >> $CRON_FILE
           /bin/echo "*/1 * * * * php usr/share/nginx/bloembraaden/Job.php 1 > /dev/null 2>&1" >> $CRON_FILE
           /bin/echo "*/5 * * * * php usr/share/nginx/bloembraaden/Job.php 5 > /dev/null 2>&1" >> $CRON_FILE
           /bin/echo "4 * * * * php usr/share/nginx/bloembraaden/Job.php hourly > /dev/null 2>&1" >> $CRON_FILE
           /bin/echo "0 3 * * * php usr/share/nginx/bloembraaden/Job.php daily > /dev/null 2>&1" >> $CRON_FILE
	fi
}
