<?php
/**
 * Cockpit model initialisation — runs on every container start.
 * Force-recreates every model with the latest field structure.
 */

define('COCKPIT_CLI', true);
define('APP_CLI', true);

require __DIR__ . '/bootstrap.php';
$app = Cockpit::instance();

$content = $app->module('content');

// ── helpers ───────────────────────────────────────────────────────────────────

function f(string $name, string $type, string $label, array $opts = []): array {
    $field = ['name' => $name, 'type' => $type, 'label' => $label];
    if ($opts) $field['opts'] = $opts;
    return $field;
}
function txt(string $name, string $label): array   { return f($name, 'text',     $label); }
function area(string $name, string $label): array  { return f($name, 'textarea', $label); }
function img(string $name, string $label): array   { return f($name, 'asset',    $label); }
function bool_(string $name, string $label): array { return f($name, 'boolean',  $label); }
function date_(string $name, string $label): array { return f($name, 'date',     $label); }
function num(string $name, string $label): array   { return f($name, 'number',   $label); }
function set_(string $name, string $label, array $fields): array {
    return f($name, 'set', $label, ['fields' => $fields]);
}

// Write the model PHP file directly to avoid Cockpit's pathCache bug
// (removeModel + saveModel fails because the cache still has the old path)
function createModel(object $content, string $name, string $type, string $label, array $fields): void {
    $storagePath = __DIR__ . '/storage/content';
    if (!is_dir($storagePath)) mkdir($storagePath, 0775, true);

    $model = [
        'name'      => $name,
        'label'     => $label,
        'info'      => '',
        'type'      => $type,
        'fields'    => $fields,
        'preview'   => [],
        'group'     => null,
        'meta'      => null,
        '_created'  => time(),
        '_modified' => time(),
    ];
    $export = var_export($model, true);
    file_put_contents("{$storagePath}/{$name}.model.php", "<?php\n return {$export};");
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
    echo "Admin user exists.\n";
}

// ── singletons ────────────────────────────────────────────────────────────────

echo "\nCreating singletons...\n";

createModel($content, 'Home', 'singleton', 'Home Page', [
    // Intro
    txt('introHeadingStart',      'Intro / Heading Start'),
    txt('introHeadingHighlight',  'Intro / Heading Highlight'),
    txt('introHeadingEnd',        'Intro / Heading End'),
    txt('introHeadingSecondLine', 'Intro / Heading Second Line'),
    txt('introExperienceLabel',   'Intro / Experience Label'),
    txt('introExperienceValue',   'Intro / Experience Value'),
    area('introDescription',      'Intro / Description'),
    img('introImage',             'Intro / Image'),
    // Stats
    set_('stats', 'Stats', [
        txt('value', 'Value'),
        txt('label', 'Label'),
    ]),
    // Partners
    set_('partners', 'Partners', [
        img('image', 'Logo'),
    ]),
    // About
    txt('aboutEyebrow',     'About / Eyebrow'),
    txt('aboutTitle',       'About / Title'),
    area('aboutDescription','About / Description'),
    img('aboutImage',       'About / Image'),
    set_('aboutHighlights', 'About / Highlights', [
        txt('label',  'Label'),
        img('image',  'Icon'),
    ]),
    // Mission & Vision
    set_('missionVision', 'Mission & Vision', [
        txt('title',        'Title'),
        area('description', 'Description'),
        img('image',        'Image'),
    ]),
    // Solutions
    txt('solutionsHeading', 'Solutions / Heading'),
    // Testimonials
    txt('testimonialsHeading',      'Testimonials / Heading'),
    area('testimonialsDescription', 'Testimonials / Description'),
    set_('testimonials', 'Testimonials', [
        area('quote', 'Quote'),
        txt('name',   'Name'),
        txt('role',   'Role'),
        img('avatar', 'Avatar'),
        num('rating', 'Rating'),
    ]),
    // FAQs
    txt('faqsHeading', 'FAQs / Heading'),
    set_('faqs', 'FAQs', [
        txt('question',  'Question'),
        area('answer',   'Answer'),
    ]),
]);

createModel($content, 'About', 'singleton', 'About Page', [
    txt('headerEyebrow',     'Header / Eyebrow'),
    txt('headerTitle',       'Header / Title'),
    area('headerDescription','Header / Description'),
    img('headerImage',       'Header / Image'),
    // Company Values
    txt('valuesHeading',  'Values / Heading'),
    img('valuesImage',    'Values / Image'),
    set_('values', 'Company Values', [
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Partners
    txt('partnersHeading', 'Partners / Heading'),
    set_('partners', 'Partners', [
        txt('name', 'Name'),
    ]),
    // Team
    txt('teamHeading',      'Team / Heading'),
    area('teamDescription', 'Team / Description'),
    set_('team', 'Team Members', [
        txt('name',         'Name'),
        txt('role',         'Role'),
        img('image',        'Photo'),
        area('bio',         'Bio'),
    ]),
    // CTA
    txt('ctaTitle',       'CTA / Title'),
    area('ctaDescription','CTA / Description'),
]);

createModel($content, 'Contact', 'singleton', 'Contact Page', [
    txt('headerEyebrow',     'Header / Eyebrow'),
    txt('headerTitle',       'Header / Title'),
    area('headerDescription','Header / Description'),
    img('headerImage',       'Header / Image'),
    // Details
    txt('detailsEyebrow',     'Details / Eyebrow'),
    txt('detailsTitle',       'Details / Title'),
    area('detailsDescription','Details / Description'),
    txt('officeAddress',  'Office Address'),
    txt('phone',          'Phone'),
    txt('email',          'Email'),
    txt('website',        'Website'),
    txt('businessHours',  'Business Hours'),
    txt('weekendHours',   'Weekend Hours'),
    txt('mapEmbedUrl',    'Map Embed URL'),
    txt('directionsUrl',  'Directions URL'),
    // Form
    txt('formTitle',       'Form / Title'),
    area('formDescription','Form / Description'),
]);

createModel($content, 'Career', 'singleton', 'Career Page', [
    txt('headerEyebrow',      'Header / Eyebrow'),
    txt('headerTitle',        'Header / Title'),
    area('headerDescription', 'Header / Description'),
    img('headerImage',        'Header / Image'),
    txt('headerBadgeLabel',   'Header / Badge Label'),
    // Open Positions
    txt('positionsTitle',       'Open Positions / Title'),
    area('positionsDescription','Open Positions / Description'),
    set_('departments',     'Departments',      [txt('name', 'Name')]),
    set_('employmentTypes', 'Employment Types', [txt('name', 'Name')]),
    // Hiring Process
    txt('hiringTitle',       'Hiring Process / Title'),
    area('hiringDescription','Hiring Process / Description'),
    set_('hiringSteps', 'Hiring Steps', [
        txt('title',        'Title'),
        area('description', 'Description'),
        img('image',        'Icon'),
    ]),
    // Life at TechAvenue
    txt('lifeEyebrow',      'Life / Eyebrow'),
    txt('lifeTitle',        'Life / Title'),
    area('lifeDescription', 'Life / Description'),
    set_('lifeHighlights', 'Life Highlights', [
        txt('title',        'Title'),
        area('description', 'Description'),
        img('media',        'Image'),
    ]),
    // Follow Us
    txt('followUsTitle',       'Follow Us / Title'),
    area('followUsDescription','Follow Us / Description'),
    set_('socialLinks', 'Social Links', [
        txt('label', 'Label'),
        txt('href',  'URL'),
        img('image', 'Icon'),
    ]),
]);

createModel($content, 'Services', 'singleton', 'Services Page', [
    txt('headerTitle',       'Header / Title'),
    area('headerDescription','Header / Description'),
    img('headerImage',       'Header / Image'),
    // Core Services
    txt('servicesHeading',       'Core Services / Heading'),
    area('servicesDescription',  'Core Services / Description'),
    set_('coreServices', 'Core Services', [
        img('icon',         'Icon'),
        txt('title',        'Title'),
        area('description', 'Description'),
        txt('articleUuid',  'Article UUID'),
    ]),
    // Articles
    set_('articles', 'Articles', [
        txt('eyebrow',      'Eyebrow'),
        txt('title',        'Title'),
        area('description', 'Description'),
        img('image',        'Image'),
        img('iconImage',    'Icon Image'),
        area('features',    'Features (one per line)'),
    ]),
]);

createModel($content, 'SuccessStoriesPage', 'singleton', 'Success Stories Page', [
    txt('headerTitle',          'Header / Title'),
    area('headerDescription',   'Header / Description'),
    img('headerBackgroundImage','Header / Background Image'),
    // Intro
    txt('introTitleStart',      'Intro / Title Start'),
    txt('introTitleHighlight',  'Intro / Title Highlight'),
    txt('introTitleEnd',        'Intro / Title End'),
    area('introDescription',    'Intro / Description'),
]);

// ── collections ───────────────────────────────────────────────────────────────

echo "\nCreating collections...\n";

createModel($content, 'Industries', 'collection', 'Industries', [
    txt('title',       'Title'),
    img('icon',        'Icon'),
    area('description','Description'),
    // Header
    txt('headerTitle',          'Header / Title'),
    area('headerDescription',   'Header / Description'),
    img('headerBackground',     'Header / Background Image'),
    // Overview
    txt('overviewTitle',       'Overview / Title'),
    area('overviewDescription','Overview / Description'),
    img('overviewImage',       'Overview / Image'),
    set_('overviewHighlights', 'Overview Highlights', [
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Challenges
    txt('challengesTitle',       'Challenges / Title'),
    area('challengesDescription','Challenges / Description'),
    set_('challenges', 'Challenges', [
        img('icon',         'Icon'),
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Solutions
    txt('solutionsTitle',       'Solutions / Title'),
    area('solutionsDescription','Solutions / Description'),
    set_('solutions', 'Solutions', [
        img('icon',         'Icon'),
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
]);

createModel($content, 'Jobs', 'collection', 'Jobs', [
    txt('title',          'Job Title'),
    txt('department',     'Department'),
    txt('location',       'Location'),
    txt('employmentType', 'Employment Type'),
    txt('experience',     'Experience'),
    txt('workplace',      'Workplace'),
]);

createModel($content, 'LegacySolutions', 'collection', 'Legacy Solutions', [
    txt('title',        'Title'),
    img('icon',         'Icon'),
    area('description', 'Description'),
    set_('highlights', 'Highlights', [
        txt('label',     'Label'),
        img('checkIcon', 'Check Icon'),
    ]),
    // Header
    txt('headerTitle',       'Header / Title'),
    area('headerDescription','Header / Description'),
    img('headerImage',       'Header / Image'),
    set_('headerStats', 'Header Stats', [
        txt('value', 'Value'),
        txt('label', 'Label'),
    ]),
    // Overview
    txt('overviewTitle',       'Overview / Title'),
    area('overviewDescription','Overview / Description'),
    // Key Info
    txt('keyInfoTitle', 'Key Info / Title'),
    set_('keyInfo', 'Key Info', [
        txt('key',   'Key'),
        txt('value', 'Value'),
    ]),
    // Experience
    txt('experienceTitle',       'Experience / Title'),
    area('experienceDescription','Experience / Description'),
    img('experienceImage',       'Experience / Image'),
    set_('experienceItems', 'Experience Items', [
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Testimonial
    area('testimonialQuote',  'Testimonial / Quote'),
    txt('testimonialName',    'Testimonial / Name'),
    txt('testimonialRole',    'Testimonial / Role'),
    img('testimonialAvatar',  'Testimonial / Avatar'),
]);

createModel($content, 'SolutionPages', 'collection', 'Solution Pages', [
    txt('title',  'Title'),
    img('image',  'Image'),
    img('icon',   'Icon'),
    // Solution Header
    txt('solutionHeading',       'Solution Header / Heading'),
    area('solutionDescription',  'Solution Header / Description'),
    // Solutions
    txt('solutionsHeading',      'Solutions / Heading'),
    area('solutionsDescription', 'Solutions / Description'),
    set_('solutionItems', 'Solution Items', [
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Phases
    set_('phases', 'Phases', [
        txt('title',        'Title'),
        txt('focus',        'Focus'),
        area('description', 'Description'),
    ]),
]);

createModel($content, 'NewsItems', 'collection', 'News Items', [
    txt('title',        'Title'),
    txt('category',     'Category'),
    area('description', 'Description'),
    date_('publishedAt','Published At'),
    img('image',        'Image'),
    area('html',        'HTML Content'),
    bool_('featured',   'Featured'),
]);

createModel($content, 'Events', 'collection', 'Events', [
    txt('title',        'Title'),
    txt('location',     'Location'),
    date_('startsAt',   'Starts At'),
    img('image',        'Image'),
    area('html',        'HTML Content'),
]);

createModel($content, 'SuccessStories', 'collection', 'Success Stories', [
    txt('title',        'Title'),
    img('image',        'Image'),
    area('description', 'Description'),
    txt('industryTitle','Industry'),
    txt('solutionTitle','Solution'),
    // Challenges
    txt('challengesTitle',       'Challenges / Title'),
    area('challengesDescription','Challenges / Description'),
    set_('challenges', 'Challenges', [
        img('icon',         'Icon'),
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Solutions
    txt('solutionsTitle',       'Solutions / Title'),
    area('solutionsDescription','Solutions / Description'),
    set_('solutionItems', 'Solution Items', [
        img('icon',         'Icon'),
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
    // Impact
    txt('impactTitle',       'Impact / Title'),
    area('impactDescription','Impact / Description'),
    set_('impactItems', 'Impact Items', [
        txt('value',        'Value'),
        txt('title',        'Title'),
        area('description', 'Description'),
    ]),
]);

echo "\nDone.\n";
