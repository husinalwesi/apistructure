<?php

define("DEFAULT_LANG", "en");

// DB CONFIG "LOCALHOST"
define("DB_HOST", "localhost");
define("DB_NAME", "test");
define("DB_USER", "root");
define("DB_PASS", "");
// DB CONFIG "sooq-media.com"
// define("DB_HOST","localhost");
// define("DB_NAME","yxlplomy_game");
// define("DB_USER","yxlplomy_game");
// define("DB_PASS","husinalwesi456*(hhH");
// DB CONFIG
// EMAIL SETTING
define("Email_TITLE", "SOOQ.COM");
define("Email_HOST", "skyfortravel.com");
define("Email_SEND_FROM", "support@skyfortravel.com");
define("Email_PASSWORD", "husinalwesi456*(hhH");
define("Email_SMTPSECURE", "ssl"); //tls
define("Email_PORT", "465"); //587
// EMAIL SETTING

class MConfig
{
  var $db_host = DB_HOST;
  var $db_name = DB_NAME;
  var $db_user = DB_USER;
  var $db_pass = DB_PASS;
}

?>