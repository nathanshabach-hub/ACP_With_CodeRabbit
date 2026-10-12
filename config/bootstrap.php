<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Utility\Security;

require dirname(__DIR__) . '/vendors/autoload.php';
require __DIR__ . '/paths.php';

if (!function_exists('__')) {
    function __($message, ...$args)
    {
        return $args ? vsprintf((string)$message, $args) : (string)$message;
    }
}

if (!function_exists('__d')) {
    function __d($domain, $message, ...$args)
    {
        return $args ? vsprintf((string)$message, $args) : (string)$message;
    }
}

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);

if (!Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+1 years');
    Configure::write('Cache._cake_core_.duration', '+1 years');
}

date_default_timezone_set('Australia/Brisbane');
mb_internal_encoding((string)Configure::read('App.encoding'));
ini_set('intl.default_locale', (string)Configure::read('App.defaultLocale'));

Cache::setConfig((array)Configure::consume('Cache'));
ConnectionManager::setConfig((array)Configure::consume('Datasources'));
TransportFactory::setConfig((array)Configure::consume('EmailTransport'));
Mailer::setConfig((array)Configure::consume('Email'));
Log::setConfig((array)Configure::consume('Log'));
Security::setSalt((string)Configure::consume('Security.salt'));

if (PHP_SAPI === 'cli') {
    require __DIR__ . '/bootstrap_cli.php';
}
