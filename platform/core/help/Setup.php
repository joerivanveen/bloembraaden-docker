<?php

namespace Peat;

use PDO, PDOException, Exception, stdClass, DateTime;

if (class_exists('Setup')) {
    return new Setup();
}

class Setup
{
    public static bool $INSTALL, $VERBOSE;
    public static bool $NOT_IN_STOCK_CAN_BE_ORDERED;
    public static int $instance_id, $DECIMAL_DIGITS;
    public static string $DECIMAL_SEPARATOR, $RADIX, $timezone;
    public static string $VERSION, $UPLOADS, $INVOICE, $LOGFILE;
    public static string $PRESENTATION_INSTANCE, $PRESENTATION_ADMIN;
    public static array $translations;
    //public static DateTime $date_now;
    public static string $now_time_string;
    public static stdClass $MAIL, $INSTAGRAM, $POSTCODE, $PDFMAKER;
    private static ?PDO $DB_MAIN_CONN = null, $DB_HIST_CONN = null;
    private static stdClass $DB_MAIN, $DB_HIST;
    public const AVAILABLE_TIMEZONES = [
        'Europe/London',
        'Europe/Amsterdam',
        'Europe/Brussels',
        'Europe/Paris',
        'Europe/Berlin',
    ];

    public function __construct()
    {
        // you can execute functions here and require files etc. to your hearts content
        self::loadConfig();
        // hmmm for now
        try {
            //self::$date_now = new DateTime(self::getMainDatabaseConnection()->query('SELECT NOW();')->fetchAll()[0][0]);
            self::$now_time_string = strtotime(self::getMainDatabaseConnection()->query('SELECT NOW();')->fetchAll()[0][0]);
        } catch(Exception $e) {
            die('Could not determine ‘when’ it is, now');
        }
        // setup some cleaning for when execution ends
        register_shutdown_function(
            function () {
                // leave the connections in a good state when the script ends to be reused by postgres
                self::abandonDatabaseConnection(self::$DB_MAIN_CONN);
                self::abandonDatabaseConnection(self::$DB_HIST_CONN);
                // also log any serious errors
                Help::logErrorMessages();
            });
    }

    public static function getMainDatabaseConnection(): PDO
    {
        return self::$DB_MAIN_CONN ?? (self::$DB_MAIN_CONN = self::initializeDatabaseConnection(self::$DB_MAIN));
    }

    public static function getHistoryDatabaseConnection(): PDO
    {
        return self::$DB_HIST_CONN ?? (self::$DB_HIST_CONN = self::initializeDatabaseConnection(self::$DB_HIST));
    }

    /**
     * @param string $name_of_db_obj_in_config
     * @return string defaults to public
     */
    public static function getDatabaseSchema(string $name_of_db_obj_in_config): string
    {
        return Setup::${$name_of_db_obj_in_config}->schema ?? 'public';
    }

    private static function initializeDatabaseConnection(stdClass $db_properties): PDO
    {
        try {
            $conn = new PDO(sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $db_properties->host,
                $db_properties->port,
                $db_properties->name
            ), $db_properties->user, $db_properties->pass, array(PDO::ATTR_PERSISTENT => false));
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;
        } catch (PDOException $e) {
            $boo = new BaseLogic();
            $boo->handleErrorAndStop($e, sprintf(__('No connection to database ‘%s’', 'peatcms'), $db_properties->name));
        }
    }

    private static function abandonDatabaseConnection(?PDO $connection): void
    {
        if (isset($connection)) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $connection = null;
        }
    }

    private static function loadConfig(): void
    {
        $config = json_decode(file_get_contents(CORE . '../config.json'));
        self::$VERSION = $config->version;
        self::$UPLOADS = $config->uploads;
        self::$INVOICE = $config->invoice;
        self::$LOGFILE = $config->logfile . date('Y-m-d') . '.log';
        self::$VERBOSE = $config->VERBOSE;
        self::$INSTALL = $config->install;
        self::$DB_MAIN = $config->DB_MAIN;
        self::$DB_HIST = $config->DB_HISTORY;
        self::$MAIL = $config->MAIL;
        self::$INSTAGRAM = $config->integrations->instagram;
        self::$POSTCODE = $config->integrations->postcode;
        self::$PDFMAKER = $config->integrations->pdfmaker;
        $config = null;
    }

    static public function loadInstanceSettings(Instance $I): void
    {
        self::$instance_id = $I->getId(); // this is necessary for DB to output the correct pages and products etc.
        self::$DECIMAL_SEPARATOR = (string)$I->getSetting('decimal_separator');
        self::$RADIX = (self::$DECIMAL_SEPARATOR === '.') ? ',' : '.';
        self::$DECIMAL_DIGITS = (int)$I->getSetting('decimal_digits');
        self::$NOT_IN_STOCK_CAN_BE_ORDERED = (bool)$I->getSetting('not_in_stock_can_be_ordered');
        self::$PRESENTATION_INSTANCE = $I->getPresentationInstance();
        self::$PRESENTATION_ADMIN = $I->getPresentationAdmin();
        // set timezone for the session
        // PAY ATTENTION the strings must be a valid timezone in PHP as well as in Postgresql
        self::$timezone = $I->getSetting('timezone') ?? 'Europe/Amsterdam';
        if (!in_array(self::$timezone, self::AVAILABLE_TIMEZONES)) {
            Help::addError(new Exception(sprintf('Not a timezone ‘%s’', self::$timezone)));
            Help::addMessage('Config error, unrecognized timezone', 'warn');
            self::$timezone = 'Europe/Amsterdam'; // this is a correct timezone and for now the default
        }
        if (false === self::getMainDatabaseConnection()->exec(sprintf('SET timezone TO \'%s\';', self::$timezone))) {
            Help::addError(new \Exception('failed to set timezone'));
        } else {
            date_default_timezone_set(self::$timezone);
        }
        // load translations
        self::loadTranslations(new \MoParser());
    }

    static public function loadTranslations(\MoParser $mo_parser): void
    {
        self::$translations = $mo_parser->loadTranslationData(self::$PRESENTATION_INSTANCE . '.mo', 'XX')['XX'];
    }
}

return new Setup();