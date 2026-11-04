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
function set_(string $name, string $label, array $fields, string $display = ''): array {
    array_unshift($fields, txt('uuid', 'Item ID (keep stable)'));
    $opts = ['fields' => $fields];
    if ($display) $opts['display'] = $display;
    return ['name' => $name, 'type' => 'set', 'label' => $label, 'multiple' => true, 'opts' => $opts];
}

// Write the model PHP file directly to avoid Cockpit's pathCache bug
// (removeModel + saveModel fails because the cache still has the old path)
function createModel(object $content, string $name, string $type, string $label, array $fields): void {
    if ($type === 'collection') array_unshift($fields, txt('uuid', 'URL ID (keep stable after publishing)'));
    if ($name === 'SuccessStories') { $fields[] = txt('industryUuid', 'Industry URL ID'); $fields[] = txt('solutionUuid', 'Solution URL ID'); }
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
    ], '${data.value} — ${data.label}'),
    // Partners
    set_('partners', 'Partners', [
        txt('image', 'Logo'),
    ], '${data.image}'),
    // About
    txt('aboutEyebrow',     'About / Eyebrow'),
    txt('aboutTitle',       'About / Title'),
    area('aboutDescription','About / Description'),
    img('aboutImage',       'About / Image'),
    set_('aboutHighlights', 'About / Highlights', [
        txt('label', 'Label'),
        txt('image', 'Icon'),
    ], '${data.label}'),
    // Mission & Vision
    set_('missionVision', 'Mission & Vision', [
        txt('title',        'Title'),
        txt('description',  'Description'),
        txt('image',        'Image'),
    ], '${data.title}'),
    // Solutions
    txt('solutionsHeading', 'Solutions / Heading'),
    // Testimonials
    txt('testimonialsHeading',      'Testimonials / Heading'),
    area('testimonialsDescription', 'Testimonials / Description'),
    set_('testimonials', 'Testimonials', [
        txt('quote',  'Quote'),
        txt('name',   'Name'),
        txt('role',   'Role'),
        txt('avatar', 'Avatar'),
        num('rating', 'Rating'),
    ], '${data.name} — ${data.role}'),
    // FAQs
    txt('faqsHeading', 'FAQs / Heading'),
    set_('faqs', 'FAQs', [
        txt('question', 'Question'),
        txt('answer',   'Answer'),
    ], '${data.question}'),
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
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
    // Partners
    txt('partnersHeading', 'Partners / Heading'),
    set_('partners', 'Partners', [
        txt('name', 'Name'),
    ]),
    // Team
    txt('teamHeading',      'Team / Heading'),
    area('teamDescription', 'Team / Description'),
    set_('team', 'Team Members', [
        txt('name',  'Name'),
        txt('role',  'Role'),
        txt('image', 'Photo'),
        txt('bio',   'Bio'),
    ], '${data.name} — ${data.role}'),
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
    set_('departments',     'Departments',      [txt('name', 'Name')], '${data.name}'),
    set_('employmentTypes', 'Employment Types', [txt('name', 'Name')], '${data.name}'),
    // Hiring Process
    txt('hiringTitle',       'Hiring Process / Title'),
    area('hiringDescription','Hiring Process / Description'),
    set_('hiringSteps', 'Hiring Steps', [
        txt('title',       'Title'),
        txt('description', 'Description'),
        txt('image',       'Icon'),
    ], '${data.title}'),
    // Life at TechAvenue
    txt('lifeEyebrow',      'Life / Eyebrow'),
    txt('lifeTitle',        'Life / Title'),
    area('lifeDescription', 'Life / Description'),
    set_('lifeHighlights', 'Life Highlights', [
        txt('title',       'Title'),
        txt('description', 'Description'),
        txt('media',       'Image'),
    ], '${data.title}'),
    // Follow Us
    txt('followUsTitle',       'Follow Us / Title'),
    area('followUsDescription','Follow Us / Description'),
    set_('socialLinks', 'Social Links', [
        txt('label', 'Label'),
        txt('href',  'URL'),
        txt('image', 'Icon'),
    ], '${data.label}'),
]);

createModel($content, 'Services', 'singleton', 'Services Page', [
    txt('headerTitle',       'Header / Title'),
    area('headerDescription','Header / Description'),
    img('headerImage',       'Header / Image'),
    // Core Services
    txt('servicesHeading',       'Core Services / Heading'),
    area('servicesDescription',  'Core Services / Description'),
    set_('coreServices', 'Core Services', [
        txt('icon',        'Icon'),
        txt('title',       'Title'),
        txt('description', 'Description'),
        txt('articleUuid', 'Article UUID'),
    ], '${data.title}'),
    // Articles
    set_('articles', 'Articles', [
        txt('eyebrow',     'Eyebrow'),
        txt('title',       'Title'),
        txt('description', 'Description'),
        txt('image',       'Image'),
        txt('iconImage',   'Icon Image'),
        txt('features',    'Features (one per line)'),
        txt('featureIcons', 'Feature icon paths (one per line, same order)'),
    ], '${data.title}'),
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
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
    // Challenges
    txt('challengesTitle',       'Challenges / Title'),
    area('challengesDescription','Challenges / Description'),
    set_('challenges', 'Challenges', [
        txt('icon',        'Icon'),
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
    // Solutions
    txt('solutionsTitle',       'Solutions / Title'),
    area('solutionsDescription','Solutions / Description'),
    set_('solutions', 'Solutions', [
        txt('icon',        'Icon'),
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
]);

createModel($content, 'Jobs', 'collection', 'Jobs', [
    txt('title',          'Job Title'),
    txt('department',     'Department'),
    txt('location',       'Location'),
    txt('employmentType', 'Employment Type'),
    txt('experience',     'Experience'),
    txt('workplace',      'Workplace'),
    area('description',   'Job Description'),
    txt('applicationUrl', 'Application URL (leave empty to use contact email)'),
]);

createModel($content, 'LegacySolutions', 'collection', 'Legacy Solutions', [
    txt('title',        'Title'),
    img('icon',         'Icon'),
    area('description', 'Description'),
    set_('highlights', 'Highlights', [
        txt('label',     'Label'),
        txt('checkIcon', 'Check Icon'),
    ], '${data.label}'),
    // Header
    txt('headerTitle',       'Header / Title'),
    area('headerDescription','Header / Description'),
    img('headerImage',       'Header / Image'),
    set_('headerStats', 'Header Stats', [
        txt('value', 'Value'),
        txt('label', 'Label'),
    ], '${data.value} — ${data.label}'),
    // Overview
    txt('overviewTitle',       'Overview / Title'),
    area('overviewDescription','Overview / Description'),
    // Key Info
    txt('keyInfoTitle', 'Key Info / Title'),
    set_('keyInfo', 'Key Info', [
        txt('key',   'Key'),
        txt('value', 'Value'),
    ], '${data.key}: ${data.value}'),
    // Experience
    txt('experienceTitle',       'Experience / Title'),
    area('experienceDescription','Experience / Description'),
    img('experienceImage',       'Experience / Image'),
    set_('experienceItems', 'Experience Items', [
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
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
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
    // Phases
    set_('phases', 'Phases', [
        txt('title',       'Title'),
        txt('focus',       'Focus'),
        txt('description', 'Description'),
    ], '${data.title}'),
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
        txt('icon',        'Icon'),
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
    // Solutions
    txt('solutionsTitle',       'Solutions / Title'),
    area('solutionsDescription','Solutions / Description'),
    set_('solutionItems', 'Solution Items', [
        txt('icon',        'Icon'),
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.title}'),
    // Impact
    txt('impactTitle',       'Impact / Title'),
    area('impactDescription','Impact / Description'),
    set_('impactItems', 'Impact Items', [
        txt('value',       'Value'),
        txt('title',       'Title'),
        txt('description', 'Description'),
    ], '${data.value} — ${data.title}'),
]);


createModel($content, 'SiteSettings', 'singleton', 'Website Settings', [
    txt('siteName', 'Site Name'),
    area('siteTitle', 'Site Title'),
    area('siteDescription', 'Site Description'),
    img('logo', 'Logo'),
    txt('logoAlt', 'Logo Alt'),
    txt('homeLabel', 'Home Label'),
    txt('contactLabel', 'Contact Label'),
    txt('contactHref', 'Contact Href'),
    set_('navigation', 'Navigation', [txt('label', 'Label'), txt('href', 'Href'), txt('group', 'Group')]),
    set_('companyLinks', 'Company Links', [txt('title', 'Title'), txt('description', 'Description'), txt('href', 'Href'), txt('image', 'Image')]),
    area('footerDescription', 'Footer Description'),
    txt('quickLinksHeading', 'Quick Links Heading'),
    set_('quickLinks', 'Quick Links', [txt('label', 'Label'), txt('href', 'Href')]),
    set_('socialLinks', 'Social Links', [txt('label', 'Label'), txt('href', 'Href'), txt('image', 'Image')]),
    txt('footerNewsletterHeading', 'Footer Newsletter Heading'),
    area('footerNewsletterDescription', 'Footer Newsletter Description'),
    txt('emailPlaceholder', 'Email Placeholder'),
    txt('subscribeLabel', 'Subscribe Label'),
    txt('copyright', 'Copyright'),
    area('ctaTitle', 'Cta Title'),
    area('ctaDescription', 'Cta Description'),
    txt('newsEyebrow', 'News Eyebrow'),
    txt('newsHeading', 'News Heading'),
    txt('latestStoriesHeading', 'Latest Stories Heading'),
    txt('readStoryLabel', 'Read Story Label'),
    txt('eventsHeading', 'Events Heading'),
    txt('newsletterHeading', 'Newsletter Heading'),
    area('newsletterDescription', 'Newsletter Description'),
    txt('newsletterEmailPlaceholder', 'Newsletter Email Placeholder'),
    txt('newsletterButtonLabel', 'Newsletter Button Label'),
    area('industryStoriesTitle', 'Industry Stories Title'),
    area('industryStoriesDescription', 'Industry Stories Description'),
    area('solutionStoriesTitle', 'Solution Stories Title'),
    area('solutionStoriesDescription', 'Solution Stories Description'),
    txt('storiesActionLabel', 'Stories Action Label'),
    txt('storiesActionHref', 'Stories Action Href'),
    txt('requestDemoLabel', 'Request Demo Label'),
    txt('watchDemoLabel', 'Watch Demo Label'),
    txt('demoEyebrow', 'Demo Eyebrow'),
    area('demoTitle', 'Demo Title'),
    area('demoDescription', 'Demo Description'),
    set_('organizationSizes', 'Organization Sizes', [txt('name', 'Name')]),
    txt('teamMemberModalAbout', 'Team Member Modal About'),
    txt('teamMemberModalClose', 'Team Member Modal Close'),
    txt('careerHeaderExplorePositions', 'Career Header Explore Positions'),
    txt('careerHeaderLearnAboutUs', 'Career Header Learn About Us'),
    txt('openPositionsApplyNow', 'Open Positions Apply Now'),
    txt('openPositionsNoPositionsAvaliable', 'Open Positions No Positions Avaliable'),
    txt('contactDetailsHeadOffice', 'Contact Details Head Office'),
    txt('contactDetailsPhone', 'Contact Details Phone'),
    txt('contactDetailsEmail', 'Contact Details Email'),
    txt('contactDetailsWebsite', 'Contact Details Website'),
    txt('contactDetailsBusinessHours', 'Contact Details Business Hours'),
    txt('contactDetailsGetDirections', 'Contact Details Get Directions'),
    txt('contactFormFullName', 'Contact Form Full Name'),
    txt('contactFormJohnDoe', 'Contact Form John Doe'),
    txt('contactFormCompanyName', 'Contact Form Company Name'),
    txt('contactFormAcmeCorp', 'Contact Form Acme Corp'),
    txt('contactFormEmailAddress', 'Contact Form Email Address'),
    txt('contactFormJohnCompanyCom', 'Contact Form John Company Com'),
    txt('contactFormContactNumber', 'Contact Form Contact Number'),
    txt('contactForm923000000000', 'Contact Form923000000000'),
    txt('contactFormOrganization', 'Contact Form Organization'),
    txt('contactFormOrganizationType', 'Contact Form Organization Type'),
    txt('contactFormIndustry', 'Contact Form Industry'),
    txt('contactFormSelectIndustry', 'Contact Form Select Industry'),
    txt('contactFormVerticleOfInterest', 'Contact Form Verticle Of Interest'),
    txt('contactFormSelectAVerticle', 'Contact Form Select A Verticle'),
    txt('contactFormSubject', 'Contact Form Subject'),
    txt('contactFormBriefSubjectOfInquiry', 'Contact Form Brief Subject Of Inquiry'),
    txt('contactFormMessage', 'Contact Form Message'),
    txt('contactFormHowCanWeHelpYou', 'Contact Form How Can We Help You'),
    area('contactFormIAgreeToBeContactedByTech', 'Contact Form I Agree To Be Contacted By Tech'),
    txt('contactFormViewSuccessStories', 'Contact Form View Success Stories'),
    txt('contactFormSendMessage', 'Contact Form Send Message'),
    txt('homeAboutLearnMore', 'Home About Learn More'),
    txt('homeSolutionsViewSolution', 'Home Solutions View Solution'),
    txt('requestDemoModalFullName', 'Request Demo Modal Full Name'),
    txt('requestDemoModalJohnDoe', 'Request Demo Modal John Doe'),
    txt('requestDemoModalContactNumber', 'Request Demo Modal Contact Number'),
    txt('requestDemoModal15550000000', 'Request Demo Modal15550000000'),
    txt('requestDemoModalEmailAddress', 'Request Demo Modal Email Address'),
    txt('requestDemoModalJohnCompanyCom', 'Request Demo Modal John Company Com'),
    txt('requestDemoModalSolutionForDemo', 'Request Demo Modal Solution For Demo'),
    txt('requestDemoModalSelectASolution', 'Request Demo Modal Select A Solution'),
    txt('requestDemoModalCompanyName', 'Request Demo Modal Company Name'),
    txt('requestDemoModalEnterpriseCorp', 'Request Demo Modal Enterprise Corp'),
    area('requestDemoModalCurrentJobTitle', 'Request Demo Modal Current Job Title'),
    txt('requestDemoModalDirectorOfIT', 'Request Demo Modal Director Of I T'),
    txt('requestDemoModalOrganizationSize', 'Request Demo Modal Organization Size'),
    txt('requestDemoModalSelectSize', 'Request Demo Modal Select Size'),
    txt('requestDemoModalAskAQuestion', 'Request Demo Modal Ask A Question'),
    txt('requestDemoModalTellUsMoreAboutYourSpecificNeeds', 'Request Demo Modal Tell Us More About Your Specific Needs'),
    area('requestDemoModalIAgreeToBeContactedByTech', 'Request Demo Modal I Agree To Be Contacted By Tech'),
    txt('requestDemoModalCancel', 'Request Demo Modal Cancel'),
    txt('requestDemoModalRequestDemo', 'Request Demo Modal Request Demo'),
    txt('successStoriesGridSolutions', 'Success Stories Grid Solutions'),
    txt('successStoriesGridIndustry', 'Success Stories Grid Industry'),
    txt('successStoriesGridNoSuccessStoriesMatchTheSelectedFilters', 'Success Stories Grid No Success Stories Match The Selected Filters'),
    txt('successStoriesHeaderSuccessStories', 'Success Stories Header Success Stories'),
    txt('aboutContactLabel', 'About / Contact button'),
    txt('aboutStoriesLabel', 'About / Success stories button'),
    txt('servicesEyebrow', 'Services / Header eyebrow'),
    txt('allFilterLabel', 'Filters / All label'),
]);

echo "\nDone.\n";



