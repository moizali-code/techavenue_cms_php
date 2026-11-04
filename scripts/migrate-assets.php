<?php
/** Register seeded images and repair Asset fields without changing text paths. */
define('COCKPIT_CLI', true);
define('APP_CLI', true);
require dirname(__DIR__) . '/bootstrap.php';

$app = Cockpit::instance();
$check = in_array('--check', $argv, true);
$converted = 0;
$missing = [];
$assets = [];
$root = realpath(dirname(__DIR__) . '/storage/uploads');

function repairFields(array $data, array $fields): array {
    global $app, $check, $converted, $missing, $assets, $root;
    foreach ($fields as $field) {
        $name = $field['name'];
        $value = $data[$name] ?? null;
        if ($value === null || $value === '') continue;
        if ($field['type'] === 'asset' && is_string($value)) {
            $path = ltrim($value, '/');
            // Correct two stale seed filenames using existing website artwork.
            $path = [
                'techavenue/career/career_header.jpg' => 'techavenue/career/career_header.png',
                'techavenue/industry/banking.png' => 'techavenue/icons/industries/banking-financial-services.svg',
            ][$path] ?? $path;
            $file = realpath($root . '/' . $path);
            if (!$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)
                || !preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', $file)) {
                $missing[] = $value;
                continue;
            }
            $converted++;
            if ($check) continue;
            if (!isset($assets[$path])) {
                $asset = $app->dataStorage->findOne('assets', ['seedPath' => $path]);
                if (!$asset) {
                    $added = $app->module('assets')->add([$file], ['seedPath' => $path]);
                    $asset = $added[0] ?? null;
                }
                if (!$asset || empty($asset['_id'])) throw new RuntimeException("Unable to register {$path}");
                $assets[$path] = $asset;
            }
            $data[$name] = $assets[$path];
        } elseif ($field['type'] === 'set' && is_array($value)) {
            $children = $field['opts']['fields'] ?? [];
            if (!empty($field['multiple'])) {
                foreach ($value as $key => $row) {
                    if (is_array($row)) $value[$key] = repairFields($row, $children);
                }
                $data[$name] = $value;
            } else {
                $data[$name] = repairFields($value, $children);
            }
        }
    }
    return $data;
}

foreach (glob(dirname(__DIR__) . '/storage/content/*.model.php') as $file) {
    $model = include $file;
    $collection = $model['type'] === 'singleton' ? 'content/singletons' : 'content/collections/' . $model['name'];
    $filter = $model['type'] === 'singleton' ? ['_model' => $model['name']] : [];
    foreach ($app->dataStorage->find($collection, ['filter' => $filter])->toArray() as $item) {
        $updated = repairFields($item, $model['fields'] ?? []);
        if (!$check && $updated !== $item) $app->dataStorage->save($collection, $updated);
    }
}

echo ($check ? 'Pending' : 'Converted') . " Asset fields: {$converted}\n";
foreach (array_unique($missing) as $path) fwrite(STDERR, "Missing or unsupported image: {$path}\n");
exit($missing || ($check && $converted) ? 1 : 0);
