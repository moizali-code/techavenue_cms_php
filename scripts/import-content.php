<?php
/** One-time, additive import of the frontend's editorial content. Existing edits win. */
define('COCKPIT_CLI', true);
define('APP_CLI', true);
require dirname(__DIR__) . '/bootstrap.php';
$app = Cockpit::instance();
$seed = json_decode(file_get_contents(__DIR__ . '/frontend-content.json'), true, flags: JSON_THROW_ON_ERROR);
$apply = in_array('--apply', $argv, true);
$changed = 0;

function supplement(array $existing, array $defaults): array {
    foreach ($defaults as $key => $value) {
        if (is_string($value) && str_starts_with($value, 'techavenue/') && is_string($existing[$key] ?? null)
            && str_starts_with($existing[$key], 'techavenue/') && !str_contains($value, '..')
            && is_file(dirname(__DIR__) . '/storage/uploads/' . $value)
            && !is_file(dirname(__DIR__) . '/storage/uploads/' . $existing[$key])) {
            $existing[$key] = $value;
        }
        if ($key === 'articleUuid' && in_array($existing[$key] ?? null, ['construction', 'education', 'telecom', 'human-capital', 'e-government'], true)) $existing[$key] = $value;
        if (!array_key_exists($key, $existing) || $existing[$key] === null || $existing[$key] === '' || $existing[$key] === []) {
            $existing[$key] = $value;
        } elseif (is_array($existing[$key]) && is_array($value)) {
            if (array_is_list($value)) {
                foreach ($value as $index => $row) {
                    if (!is_array($row)) continue;
                    $match = null;
                    foreach ($existing[$key] as $i => $current) {
                        if (!is_array($current)) continue;
                        foreach (['uuid', 'title', 'name', 'label', 'key'] as $identity) {
                            if (isset($row[$identity], $current[$identity]) && $row[$identity] === $current[$identity]) { $match = $i; break 2; }
                        }
                    }
                    if ($match === null && isset($existing[$key][$index]) && empty($existing[$key][$index]['uuid'])) $match = $index;
                    if ($match === null) $existing[$key][] = $row;
                    else $existing[$key][$match] = supplement($existing[$key][$match], $row);
                }
            } else $existing[$key] = supplement($existing[$key], $value);
        }
    }
    return $existing;
}

foreach ($seed as $name => $defaults) {
    $model = $app->module('content')->model($name);
    if (!$model) throw new RuntimeException("Model missing: {$name}");
    $singleton = $model['type'] === 'singleton';
    $collection = $singleton ? 'content/singletons' : "content/collections/{$name}";
    foreach ($singleton ? [$defaults] : $defaults as $item) {
        $existing = $singleton ? $app->dataStorage->findOne($collection, ['_model' => $name]) :
            ($app->dataStorage->findOne($collection, ['uuid' => $item['uuid']]) ?: $app->dataStorage->findOne($collection, ['title' => $item['title']]));
        $original = $existing;
        // Older imports stored section objects where current models expect rows.
        foreach ($item as $field => $value) {
            if (!is_array($value) || !array_is_list($value) || !isset($existing[$field]['items'])) continue;
            $rows = $existing[$field]['items'];
            foreach ($existing[$field] as $index => $row) {
                if (!is_int($index) || !is_array($row)) continue;
                if (!in_array($row['title'] ?? null, array_column($rows, 'title'), true)) $rows[] = $row;
            }
            $existing[$field] = $rows;
        }
        $updated = supplement($existing ?: [], $item);
        if (!$existing) $updated += ['_model' => $name, '_state' => 1, '_created' => time(), '_modified' => time()];
        if ($updated !== $original) {
            $changed++;
            if ($apply) $app->dataStorage->save($collection, $updated);
        }
        // Retain repeated legacy seed entries as drafts, with their contents intact.
        if (!$singleton && $existing && isset($item['uuid'])) {
            foreach ($app->dataStorage->find($collection, ['filter' => ['title' => $item['title']]])->toArray() as $duplicate) {
                if ($duplicate['_id'] === $existing['_id'] || !empty($duplicate['uuid']) || ($duplicate['_state'] ?? 0) !== 1) continue;
                $duplicate['_state'] = 0;
                if ($apply) $app->dataStorage->save($collection, $duplicate);
            }
        }
    }
}
if ($apply) {
    $role = $app->dataStorage->findOne('system/roles', ['appid' => 'website-reader']);
    if ($role) {
        foreach (array_keys($seed) as $name) $role['permissions']["content/{$name}/read"] = true;
        $app->dataStorage->save('system/roles', $role);
        $app->helper('acl')->cache();
    }
}
echo ($apply ? 'Imported/updated' : 'Would update') . " {$changed} content entries; existing text and assets preserved.\n";
