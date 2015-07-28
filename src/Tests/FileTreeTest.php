<?php

// Namespace of tests.
namespace Drupal\filetree\Tests;

// Use of base class for the tests.
use Drupal\simpletest\WebTestBase;

/**
 * Tests for FileTree in node content.
 *
 * Those tests are for the content of the node, to make sure they are
 * processed by filetree.
 *
 * @group filetree
 */
class FileTreeTest extends WebTestBase {

  /**
   * A global filter adminstrator.
   */
  protected $filterAdminUser;

  /**
   * A global user for adding pages.
   */
  protected $normalUser;

  /**
   * Object with configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * List of modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'libraries',
    'geshifilter',
    'filter',
    'filetree',
  );

  /**
   * Code run before each and every test method.
   */
  public function setUp() {
    parent::setUp();

    // Create object with configuration.
    $this->config = \Drupal::configFactory()
      ->getEditable('filetree.settings');

    // Create a content type, as we will create nodes on test.
    $settings = array(
      // Override default type (a random name).
      'type' => 'filetree_content_type',
      'name' => 'FileTree Content',
    );
    $this->drupalCreateContentType($settings);

    // Create a filter admin user.
    $permissions = array(
      'administer filters',
      'administer nodes',
      'access administration pages',
      'create filetree_content_type content',
      'edit any filetree_content_type content',
      'administer site configuration',
    );
    $this->filterAdminUser = $this->drupalCreateUser($permissions);

    // Log in with filter admin user.
    $this->drupalLogin($this->filterAdminUser);

    // Add an text format with only filetree filter.
    $extra = array();
    $extra['filters[filter_filetree][settings][folders]'] = 'filetree';
    $this->createTextFormat('filetree_text_format', array('filter_filetree'), $extra);

    // Copy the files.
    global $base_path;
    $this->copyRecursive(
      $_SERVER['DOCUMENT_ROOT'] . '/' . drupal_get_path('module', 'filetree') . '/src/Tests/Files',
      $_SERVER['DOCUMENT_ROOT'] . '/' . $this->publicFilesDirectory . '/filetree');
  }

  /**
   * Create a new text format.
   *
   * @param string $format_name
   *   The name of new text format.
   * @param array $filters
   *   Array with the machine names of filters to enable.
   */
  protected function createTextFormat($format_name, array $filters, array $extra = array()) {
    $edit = array();
    $edit['format'] = $format_name;
    $edit['name'] = $this->randomMachineName();
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = 1;
    foreach ($filters as $filter) {
      $edit['filters[' . $filter . '][status]'] = TRUE;
    }
    $edit += $extra;
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertRaw(t('Added text format %format.', array('%format' => $edit['name'])), 'New filter created.');
    $this->drupalGet('admin/config/content/formats');
  }

  /**
   * Test if the filter works.
   */
  protected function testFileTree() {
    $body = $this->randomMachineName(100) .
      '<p>[filetree dir="filetree"]</p>';
    // Create a node.
    $node = array(
      'title' => 'Test for FileTree Filter',
      'body' => array(
        array(
          'value' => $body,
          'format' => 'filetree_text_format',
        ),
      ),
      'type' => 'filetree_content_type',
    );
    $this->drupalCreateNode($node);
    $this->drupalGet('node/1');
  }

  /**
   * List all files/directories from a directory in recursive way.
   *
   * @param string $path
   *   Full path to directory to list.
   *
   * @return array
   *   An array with all files and folders from $path.
   */
  protected function listFiles($path) {
    $files = array();
    if (is_file($path)) {
      $files[] = $path;
    }
    elseif (is_dir($path)) {
      $all_files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
      foreach ($all_files as $file) {
        /** @var \SplFileInfo $file */
        if (basename($file->getPathname()) != '.' && basename($file->getPathname()) != '..') {
          $files[] = $file->getPathname();
        }
      }
    }
    return $files;
  }

  /**
   * Copy files and folders in recursive way.
   *
   * It will copy all files and folders from $source to $dest, if some folder
   * is missing it will create.
   *
   * @param string $source
   *   The full path to source directory.
   * @param string $dest
   *   The full path to destination directory.
   */
  protected function copyRecursive($source, $dest) {
    $files = $this->listFiles($source);
    foreach ($files as $file) {
      $dest_file = substr_replace($file, $dest, 0, strlen($source));
      if (!is_dir(dirname($dest_file))) {
        mkdir(dirname($dest_file), 0777, TRUE);
      }
      copy($file, $dest_file);
      chmod($dest_file, 0777);
      debug($dest_file);
    }
  }
}