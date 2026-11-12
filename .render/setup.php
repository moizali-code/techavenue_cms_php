<?php
/** Creates the frontend API key and the admin user from Render env vars, if they do not exist yet. */
define('COCKPIT_CLI', true);
define('APP_CLI', true);
require dirname(__DIR__) . '/bootstrap.php';

$app = Cockpit::instance();
$now = time();

$key = getenv('COCKPIT_API_KEY');
if ($key && !$app->dataStorage->findOne('system/api_keys', ['key' => $key])) {
    $doc = ['key' => $key, 'name' => 'netlify-frontend', 'role' => 'admin', 'meta' => [], '_created' => $now, '_modified' => $now];
    $app->dataStorage->save('system/api_keys', $doc);
}
$app->helper('api')->cache();

$user = getenv('ADMIN_USER') ?: 'admin';
$password = getenv('ADMIN_PASSWORD');
if ($password && !$app->dataStorage->getCollection('system/users')->count()) {
    $doc = [
        'active' => true, 'user' => $user, 'name' => $user, 'email' => getenv('ADMIN_EMAIL') ?: '',
        'password' => $app->hash($password), 'i18n' => 'en', 'role' => 'admin', 'theme' => 'auto',
        '_created' => $now, '_modified' => $now,
    ];
    $app->dataStorage->save('system/users', $doc);
}

echo "Render setup done\n";
