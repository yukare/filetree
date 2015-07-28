<?php
/**
 * @file
 * Definition of Drupal\filetree\FileTree.
 */

namespace Drupal\filetree;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Class that renders the FileTree.
 *
 * This class use the option from the filter and render the entire tree,
 * it can be used in other modules too, just pass the correct options.
 *
 * @package Drupal\filetree
 */
class FileTree {
  /**
   * Recursively list folders and files in this directory.
   *
   * Similar to file_scan_directory(), except that we need the hierarchy.
   * Returns a sorted list which is compatible with theme('item_list') or
   * theme('filetree'), folders first, then files.
   *
   * @param string $dir
   *   The path to list the files.
   * @param array $params
   *   The options used in filter.
   *
   * @return array
   *   An array of links.
   */
  public function listFiles($dir, array $params) {
    $list = array();

    if (is_dir($dir) && $handle = opendir($dir)) {
      $folders = $files = array();
      while (FALSE !== ($file = readdir($handle))) {
        if (!in_array($file, array('.', '..', 'CVS')) && $file[0] != '.') {
          if (is_dir("$dir/$file")) {
            $folders[$file] = array(
              '#markup' => $file,
              'children' => $this->listFiles("$dir/$file", $params),
              '#wrapper_attributes' => array('class' => 'folder'),
            );
          }
          else {
            $filename = "$dir/$file";
            $pathinfo = pathinfo($file);
            // Sometimes a file do not have any extension, like README files.
            if (!isset($pathinfo['extension'])) {
              $pathinfo['extension'] = '';
            }
            $basename = basename($file);
            if ($params['url'] == '') {
              $name = $params['extensions'] ? $pathinfo['basename'] : substr($pathinfo['basename'], 0, strrpos($pathinfo['basename'], '.'));
              $url = $params['absolute'] ? file_create_url($filename) : substr(file_create_url($filename), strlen($GLOBALS['base_url'] . '/'));
              $url_object = URL::fromUri($url);
              $files[$file] = array(
                '#markup' => \Drupal::l($name, $url_object),
                '#wrapper_attributes' => array('class' => $this->icon($pathinfo['extension'])),
              );
            }
          }
        }
      }
      closedir($handle);
      asort($folders);
      asort($files);
      $list += $folders;
      $list += $files;
    }
    return $list;
  }

  /**
   * Determines which icon should be displayed, based on file extension.
   *
   * @param string $extension
   *   The file extension to get the icon.
   *
   * @return string
   *   The icon to use fr given extension.
   */
  public function icon($extension) {
    $extension = strtolower($extension);
    $icon = 'file';
    $map = array(
      'application' => array('exe'),
      // 'code' => array(''),
      'css' => array('css'),
      'db' => array('sql'),
      'doc' => array('doc', 'docx'),
      'film' => array('avi', 'mov'),
      'flash' => array('flv', 'swf'),
      'html' => array('htm', 'html'),
      // 'java' => array(''),
      // 'linux' => array(''),
      'music' => array('mp3', 'aac'),
      'pdf' => array('pdf'),
      'php' => array('php'),
      'image' => array('jpg', 'jpeg', 'gif', 'png', 'bmp'),
      'ppt' => array('ppt'),
      'psd' => array('psd'),
      // 'ruby' => array(''),
      'script' => array('asp'),
      'txt' => array('txt'),
      'xls' => array('xls', 'xlsx'),
      'zip' => array('zip'),
    );
    foreach ($map as $key => $values) {
      foreach ($values as $value) {
        if ($extension == $value) {
          $icon = $key;
        }
      }
    }
    return $icon;
  }

  /**
   * Render the filetree.
   *
   * @param array $files
   *   An array with file links to render(output from $this->listFiles).
   * @param array $params
   *   Options from the filter.
   *
   * @return string
   *   The rendered filetree ready to output.
   */
  public function render(array $files, array $params) {
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
        $render = array(
          '#theme' => 'item_list',
          'items' => $controls,
          '#type' => 'ul',
          '#attributes' => array('class' => 'controls'),
          '#wrapper_attributes' => array('class' => 'controls'),
        );
        $output .= render($render);
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
    $id = Html::cleanCssIdentifier(uniqid('filetree-'));
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