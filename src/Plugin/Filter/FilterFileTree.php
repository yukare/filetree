<?php

/**
 * @file
 * Contains \Drupal\filetree\Plugin\Filter\FileTree.
 */

// Namespace for filter.
namespace Drupal\filetree\Plugin\Filter;

// Base class for filters.
use Drupal\filter\Plugin\FilterBase;

// Necessary for forms.
use Drupal\Core\Form\FormStateInterface;

// Necessary for result of process().
use Drupal\filter\FilterProcessResult;

// Necessary for URL.
use Drupal\Core\Url;

use Drupal\Component\Utility\Html;

/**
 * Provides a base filter for FileTree Filter.
 *
 * @Filter(
 *   id = "filter_filetree",
 *   title = @Translation("File Tree"),
 *   description = @Translation("Replaces [filetree arguments] with
 *     an inline list of files."),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "allowed_html" = "<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <h4> <h5> <h6>",
 *     "filter_html_help" = TRUE,
 *     "filter_html_nofollow" = FALSE
 *   },
 *   weight = 0
 * )
 */
class FilterFileTree extends FilterBase {

  /**
   * Object with configuration.
   *
   * @var \Drupal\core\config\config
   */
  private $config;

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cache tags and #post_render_cache callbacks.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode) {
    // Look for our special [filetree] token.
    if (!preg_match_all('/(?:<p>)?\[filetree\s*(.*?)\](?:<\/p>)?/s', $text, $matches)) {
      $result = new FilterProcessResult($text);
      return $result;
    }

    // Setup our default parameters.
    $default_params = array(
      'dir' => NULL,
      'multi' => TRUE,
      'controls' => TRUE,
      'extensions' => TRUE,
      'absolute' => TRUE,
      'url' => '',
      'animation' => TRUE,
    );
    // The token might be present multiple times; loop through each instance.
    foreach ($matches[1] as $key => $passed_params) {

      // Load the defaults.
      $params[$key] = $default_params;

      // Parse the parameters (but only the valid ones).
      preg_match_all('/(\w*)=(?:\"|&quot;)(.*?)(?:\"|&quot;)/', $passed_params, $matches2[$key]);
      foreach ($matches2[$key][1] as $param_key => $param_name) {
        if (in_array($param_name, array_keys($default_params))) {
          // If default param is a boolean, convert the passed param to boolean.
          // Note: "false" (as a string) is considered TRUE by PHP, so there's a
          // special check for it.
          if (is_bool($default_params[$param_name])) {
            $params[$key][$param_name] = $matches2[$key][2][$param_key] == "false" ? FALSE : (bool) $matches2[$key][2][$param_key];
          }
          else {
            $params[$key][$param_name] = $matches2[$key][2][$param_key];
          }
        }
      }

      // Make sure that "dir" was provided,
      if (!$params[$key]['dir']
        // ...it's an allowed path for this input format,
        //or !drupal_match_path($params[$key]['dir'], $filter->settings['folders'])
        // ...the URI builds okay,
        or !$params[$key]['uri'] = file_build_uri($params[$key]['dir'])
        // ...and it's within the files directory.
        or !file_prepare_directory($params[$key]['uri'])
      ) {
        continue;
      }

      // Render tree.
      $files = _filetree_list_files($params[$key]['uri'], $params[$key]);
      $render = array(
        '#theme' => 'filetree',
        '#files' => $files,
        '#params' => $params,
      );
      $rendered = $this->render($files, $params[0]);
      // Replace token with rendered tree.
      $text = str_replace($matches[0], $rendered, $text);
    }

    // Create the object with result.
    $result = new FilterProcessResult($text);
    // Associate assets to be attached.
    $result->setAssets(array(
      'library' => array(
        'filetree/filetree',
      ),
    ));
    return $result;
  }

  /**
   * Get the tips for the filter.
   *
   * @param bool $long
   *   If get the long or short tip.
   *
   * @return string
   *   The tip to show for the user.
   */
  public function tips($long = FALSE) {
    $output = t('You may use [filetree dir="some-directory"] to display a list of files inline.');
    if ($long) {
      $output = '<p>' . $output . '</p>';
      $output .= '<p>' . t('Additional options include "multi", "controls", "extensions", and "absolute"; for example, [filetree dir="some-directory" multi="false" controls="false" extensions="false" absolute="false"].') . '</p>';
    }
    return $output;
  }

  protected function render($files, $params) {
    $output = '';

    // Render controls (but only if multiple folders is enabled, and only if
    // there is at least one folder to expand/collapse).
    if ($params['multi'] and $params['controls']) {
      $has_folder = FALSE;
      foreach ($files as $file) {
        if (isset($file['#children'])) {
          $has_folder = TRUE;
          break;
        }
      }
      if ($has_folder) {
        $controls = array(
          '<a href="#" class="expand">' . t('expand all') . '</a>',
          '<a href="#" class="collapse">' . t('collapse all') . '</a>',
        );
        $output .= theme('item_list', array(
          'items' => $controls,
          'title' => NULL,
          'type' => 'ul',
          'attributes' => array('class' => 'controls'),
          '#attributes' => array('class' => 'controls'),
          '#wrapper_attributes' => array('class' => 'controls'),
        ));
      }
    }

    // Render files.
    $render = array(
      '#theme' => 'item_list',
      '#items' => $files,
      '#type' => 'ul',
      '#attributes' => array('class' => 'files'),
    );
    $output .= render($render);

    // Generate classes and unique ID for wrapper div.
    $id = Html::cleanCssIdentifier($foo);(uniqid('filetree-'));
    $classes = array('filetree');
    if ($params['multi']) {
      $classes[] = 'multi';
    }
    // If using animation, add class.
    if ($params['animation']) {
      $classes[] = 'filetree-animation';
    }
    return '<div id="' . $id . '" class="' . implode(' ', $classes) . '">' . $output . '</div>';
  }

}
