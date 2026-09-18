<?php
$sandbox = dirname(__DIR__, 2) . '/.runtime/wordpress';
if (!is_file($sandbox . '/.gitpress-test-sandbox')) { throw new RuntimeException('Missing isolated test sandbox.'); }
require $sandbox . '/wp-load.php';
wp_set_current_user(get_user_by('login', 'gpf_admin')->ID);
use GitPress\Forms\{Database, Definition, Repository, Pdf};
$definition = Definition::empty();
$definition['settings']['pdf'] = ['title' => 'Client intake', 'intro' => 'Thank you, {name}. A copy of your submission is below.', 'footer' => 'GitPress Forms · Prepared for your records', 'paper' => 'letter'];
$definition['fields'] = [];
foreach (['name' => 'Full name', 'email' => 'Email address', 'project' => 'Project overview', 'details' => 'Additional details'] as $name => $label) { $definition['fields'][] = ['id' => $name, 'name' => $name, 'label' => $label, 'type' => 'textarea', 'children' => [], 'options' => []]; }
$form = Repository::save(['title' => 'PDF acceptance fixture', 'definition' => $definition]);
$entry = ['id' => 999, 'form_id' => $form['id'], 'form_version' => 1, 'created_at' => Database::now(), 'status' => 'approved', 'response' => ['name' => 'Renée Martínez', 'email' => 'renee@example.test', 'project' => 'A welcoming booking experience for a neighborhood barbershop.', 'details' => implode("\n\n", array_fill(0, 20, 'The form should be easy to complete on a phone, support clear labels and validation, and keep submitted information within WordPress. This long answer verifies pagination and readable wrapping across pages.'))]];
$bytes = Pdf::entry($entry);
if (!str_starts_with($bytes, '%PDF-') || strlen($bytes) < 5000) { throw new RuntimeException('PDF generation failed.'); }
file_put_contents(dirname(__DIR__, 2) . '/.runtime/entry-example.pdf', $bytes);
echo 'Generated PDF acceptance fixture (' . strlen($bytes) . " bytes).\n";
