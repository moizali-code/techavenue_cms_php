<?php
/**
 * Cockpit model initialisation — runs on every container start.
 * Idempotent: skips models that already exist.
 *
 * Bootstraps Cockpit directly so we can call the internal saveModel() API
 * without needing CSRF tokens or an HTTP session.
 */

define('COCKPIT_CLI', true);
define('APP_CLI', true);

// Bootstrap Cockpit
require __DIR__ . '/bootstrap.php';
$app = Cockpit::instance();

$content = $app->module('content');

// ── helpers ───────────────────────────────────────────────────────────────────

function field(string $name, string $type, string $label, array $opts = []): array {
    return array_filter(['name' => $name, 'type' => $type, 'label' => $label, 'opts' => $opts ?: null], fn($v) => $v !== null);
}

function obj(string $name, string $label, array $fields): array {
    return ['name' => $name, 'type' => 'object', 'label' => $label, 'opts' => ['fields' => $fields]];
}

function set_(string $name, string $label, array $fields): array {
    return ['name' => $name, 'type' => 'set', 'label' => $label, 'opts' => ['fields' => $fields]];
}

function txt(string $name, string $label = ''): array {
    return field($name, 'text', $label ?: $name);
}

function area(string $name, string $label = ''): array {
    return field($name, 'textarea', $label ?: $name);
}

function asset_(string $name, string $label = ''): array {
    return field($name, 'asset', $label ?: $name);
}

function bool_(string $name, string $label = ''): array {
    return field($name, 'boolean', $label ?: $name);
}

function date_(string $name, string $label = ''): array {
    return field($name, 'date', $label ?: $name);
}

function createModel(object $content, string $name, string $type, string $label, array $fields): void {
    if ($content->exists($name)) {
        echo "  skip: {$name} already exists\n";
        return;
    }
    $content->saveModel($name, [
        'name'   => $name,
        'label'  => $label,
        'type'   => $type,
        'fields' => $fields,
    ]);
    echo "  created: {$name}\n";
}

// ── admin user ────────────────────────────────────────────────────────────────

$adminUser  = getenv('COCKPIT_ADMIN_USER')     ?: 'admin';
$adminEmail = getenv('COCKPIT_ADMIN_EMAIL')    ?: 'admin@techavenue.biz';
$adminPass  = getenv('COCKPIT_ADMIN_PASSWORD') ?: 'admin123';

$existing = $app->dataStorage->findOne('system/users', ['user' => $adminUser]);
if (!$existing) {
    $app->helper('auth')->createUser([
        'user'     => $adminUser,
        'name'     => 'Admin',
        'email'    => $adminEmail,
        'password' => $adminPass,
        'role'     => 'admin',
        'active'   => true,
        '_super'   => true,
    ]);
    echo "Admin user created: {$adminEmail}\n";
} else {
    echo "Admin user already exists, skipping.\n";
}

// ── singletons ────────────────────────────────────────────────────────────────

echo "\nCreating singletons...\n";

// Home
createModel($content, 'Home', 'singleton', 'Home Page', [
    obj('intro', 'Intro', [
        txt('headingStart', 'Heading Start'),
        txt('headingHighlight', 'Heading Highlight'),
        txt('headingEnd', 'Heading End'),
        txt('headingSecondLine', 'Heading Second Line'),
        txt('experienceLabel', 'Experience Label'),
        txt('experienceValue', 'Experience Value'),
        area('description', 'Description'),
        asset_('image', 'Image'),
    ]),
    set_('stats', 'Stats', [
        txt('value', 'Value'),
        txt('label', 'Label'),
    ]),
    set_('partners', 'Partners', [
        asset_('image', 'Logo'),
    ]),
    obj('about', 'About Section', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
        set_('highlights', 'Highlights', [
            txt('label', 'Label'),
            asset_('image', 'Icon'),
        ]),
    ]),
    set_('missionVision', 'Mission & Vision', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
    ]),
    obj('solutions', 'Solutions Section', [
        txt('heading', 'Heading'),
    ]),
    obj('testimonials', 'Testimonials', [
        txt('heading', 'Heading'),
        area('description', 'Description'),
        set_('items', 'Items', [
            area('quote', 'Quote'),
            txt('name', 'Name'),
            txt('role', 'Role'),
            asset_('avatar', 'Avatar'),
            field('rating', 'number', 'Rating'),
        ]),
    ]),
    obj('faqs', 'FAQs', [
        txt('heading', 'Heading'),
        set_('items', 'Items', [
            txt('question', 'Question'),
            area('answer', 'Answer'),
        ]),
    ]),
]);

// About
createModel($content, 'About', 'singleton', 'About Page', [
    obj('header', 'Header', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
    ]),
    obj('companyValues', 'Company Values', [
        txt('heading', 'Heading'),
        asset_('image', 'Image'),
        set_('values', 'Values', [
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    obj('partners', 'Partners', [
        txt('heading', 'Heading'),
        set_('items', 'Items', [
            txt('name', 'Name'),
        ]),
    ]),
    obj('team', 'Team', [
        txt('heading', 'Heading'),
        area('description', 'Description'),
        set_('members', 'Members', [
            txt('name', 'Name'),
            txt('role', 'Role'),
            asset_('image', 'Photo'),
            area('description', 'Bio'),
        ]),
    ]),
    obj('cta', 'CTA', [
        txt('title', 'Title'),
        area('description', 'Description'),
    ]),
]);

// Contact
createModel($content, 'Contact', 'singleton', 'Contact Page', [
    obj('header', 'Header', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
    ]),
    obj('details', 'Contact Details', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        txt('officeAddress', 'Office Address'),
        txt('phone', 'Phone'),
        txt('email', 'Email'),
        txt('website', 'Website'),
        txt('businessHours', 'Business Hours'),
        txt('weekendHours', 'Weekend Hours'),
        txt('mapEmbedUrl', 'Map Embed URL'),
        txt('directionsUrl', 'Directions URL'),
    ]),
    obj('form', 'Form', [
        txt('title', 'Title'),
        area('description', 'Description'),
    ]),
]);

// Career
createModel($content, 'Career', 'singleton', 'Career Page', [
    obj('header', 'Header', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
        txt('badgeLabel', 'Badge Label'),
    ]),
    obj('openPositions', 'Open Positions', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('departments', 'Departments', [txt('name', 'Name')]),
        set_('employmentTypes', 'Employment Types', [txt('name', 'Name')]),
    ]),
    obj('hiringProcess', 'Hiring Process', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('steps', 'Steps', [
            txt('title', 'Title'),
            area('description', 'Description'),
            asset_('image', 'Icon'),
        ]),
    ]),
    obj('lifeAtTechAvenue', 'Life at Tech Avenue', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('highlights', 'Highlights', [
            txt('title', 'Title'),
            area('description', 'Description'),
            asset_('media', 'Image'),
        ]),
    ]),
    obj('followUs', 'Follow Us', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('links', 'Links', [
            txt('label', 'Label'),
            txt('href', 'URL'),
            asset_('image', 'Icon'),
        ]),
    ]),
]);

// Services
createModel($content, 'Services', 'singleton', 'Services Page', [
    obj('header', 'Header', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
    ]),
    obj('coreServices', 'Core Services', [
        txt('heading', 'Heading'),
        area('description', 'Description'),
        set_('services', 'Services', [
            asset_('image', 'Icon'),
            txt('title', 'Title'),
            area('description', 'Description'),
            txt('articleUuid', 'Article UUID'),
        ]),
    ]),
    set_('articles', 'Articles', [
        txt('eyebrow', 'Eyebrow'),
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
        asset_('iconImage', 'Icon Image'),
        set_('features', 'Features', [
            asset_('image', 'Icon'),
            txt('title', 'Title'),
        ]),
    ]),
]);

// SuccessStoriesPage
createModel($content, 'SuccessStoriesPage', 'singleton', 'Success Stories Page', [
    obj('header', 'Header', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('backgroundImage', 'Background Image'),
    ]),
    obj('intro', 'Intro', [
        txt('titleStart', 'Title Start'),
        txt('titleHighlight', 'Title Highlight'),
        txt('titleEnd', 'Title End'),
        area('description', 'Description'),
    ]),
]);

// ── collections ───────────────────────────────────────────────────────────────

echo "\nCreating collections...\n";

// Industries
createModel($content, 'Industries', 'collection', 'Industries', [
    txt('title', 'Title'),
    asset_('image', 'Icon'),
    area('description', 'Description'),
    obj('header', 'Header', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('backgroundImage', 'Background Image'),
    ]),
    obj('overview', 'Overview', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
        set_('highlights', 'Highlights', [
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    obj('challenges', 'Challenges', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('items', 'Items', [
            asset_('image', 'Icon'),
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    obj('providedSolutions', 'Provided Solutions', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('items', 'Items', [
            asset_('image', 'Icon'),
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
]);

// Jobs
createModel($content, 'Jobs', 'collection', 'Jobs', [
    txt('title', 'Title'),
    txt('department', 'Department'),
    txt('location', 'Location'),
    txt('employmentType', 'Employment Type'),
    txt('experience', 'Experience'),
    txt('workplace', 'Workplace'),
]);

// LegacySolutions
createModel($content, 'LegacySolutions', 'collection', 'Legacy Solutions', [
    txt('title', 'Title'),
    asset_('image', 'Icon'),
    area('description', 'Description'),
    set_('highlights', 'Highlights', [
        txt('label', 'Label'),
        asset_('image', 'Check Icon'),
    ]),
    obj('header', 'Header', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
        set_('items', 'Stats', [
            txt('title', 'Stat Value'),
            txt('description', 'Stat Label'),
        ]),
    ]),
    obj('overview', 'Overview', [
        txt('title', 'Title'),
        area('description', 'Description'),
    ]),
    obj('keyInfo', 'Key Info', [
        txt('title', 'Title'),
        set_('items', 'Items', [
            txt('key', 'Key'),
            txt('value', 'Value'),
        ]),
    ]),
    obj('experience', 'Experience', [
        txt('title', 'Title'),
        area('description', 'Description'),
        asset_('image', 'Image'),
        set_('items', 'Items', [
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    obj('testimonial', 'Testimonial', [
        area('quote', 'Quote'),
        txt('name', 'Name'),
        txt('role', 'Role'),
        asset_('avatar', 'Avatar'),
    ]),
]);

// SolutionPages
createModel($content, 'SolutionPages', 'collection', 'Solution Pages', [
    txt('title', 'Title'),
    asset_('image', 'Image'),
    asset_('icon', 'Icon'),
    obj('solutionHeader', 'Solution Header', [
        txt('heading', 'Heading'),
        area('description', 'Description'),
    ]),
    obj('solutions', 'Solutions', [
        txt('heading', 'Heading'),
        area('description', 'Description'),
        set_('items', 'Items', [
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    set_('phases', 'Phases', [
        txt('title', 'Title'),
        txt('focus', 'Focus'),
        area('description', 'Description'),
    ]),
]);

// NewsItems
createModel($content, 'NewsItems', 'collection', 'News Items', [
    txt('title', 'Title'),
    txt('category', 'Category'),
    area('description', 'Description'),
    date_('publishedAt', 'Published At'),
    asset_('image', 'Image'),
    field('html', 'textarea', 'HTML Content'),
    bool_('featured', 'Featured'),
]);

// Events
createModel($content, 'Events', 'collection', 'Events', [
    txt('title', 'Title'),
    txt('location', 'Location'),
    date_('startsAt', 'Starts At'),
    asset_('image', 'Image'),
    field('html', 'textarea', 'HTML Content'),
]);

// SuccessStories
createModel($content, 'SuccessStories', 'collection', 'Success Stories', [
    txt('title', 'Title'),
    asset_('image', 'Image'),
    area('description', 'Description'),
    obj('industry', 'Industry', [txt('title', 'Title')]),
    obj('solution', 'Solution', [txt('title', 'Title')]),
    obj('challenges', 'Challenges', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('items', 'Items', [
            asset_('image', 'Icon'),
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    obj('providedSolutions', 'Provided Solutions', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('items', 'Items', [
            asset_('image', 'Icon'),
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
    obj('impact', 'Impact', [
        txt('title', 'Title'),
        area('description', 'Description'),
        set_('items', 'Items', [
            txt('value', 'Value'),
            txt('title', 'Title'),
            area('description', 'Description'),
        ]),
    ]),
]);

echo "\nDone.\n";
