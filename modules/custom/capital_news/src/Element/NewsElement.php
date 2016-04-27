<?php
/**
 * @file
 * Contains \Drupal\capital_news\Element\NewsElement.
 */
 
namespace Drupal\capital_news\Element;
 
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;
 
/**
 * Provides an example element.
 *
 * @RenderElement("news_element")
 */
class NewsElement extends RenderElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'news_element',
      '#label' => 'Default Label',
      '#description' => 'Default Description',
      '#url' => 'http://www.drupal.org',
      '#pre_render' => [
        [$class, 'preRenderNewsElement'],
      ],
    ];
  }
 
  /**
   * Prepare the render array for the template.
   */
  public static function preRenderNewsElement($element) {
    // Create a link render array using our #label.
\Drupal::logger('my_module')->notice($element['#url']);
    $element['link'] = [
      '#type' => 'link',
      '#title' => $element['#label'],
      '#url' => Url::fromUri($element['#url']),
    ];
 
    // Create a description render array using #description.
    $element['description'] = [
      '#markup' => $element['#description']
    ];
 
    $element['pre_render_addition'] = [
      '#markup' => 'Additional text.'
    ];
 
    return $element;
  }
}
