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
      '#news_type' => 'google',
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
    $element['link'] = [
      '#type' => 'link',
      '#title' => $element['#label'],
//      '#url' => Url::fromUri($element['#url']),
      '#url' => Url::fromUri($element['#url']),
    ];
    $element['type'] = [
 '#type' => 'html_tag',
  '#tag' => 'span',
  '#value' => $element['#news_type'],
'#attributes' => [
 'class' => "label label-default",
]
    ];
    
      $element['url'] = Url::fromUri($element['#url']);
    $element['addtofavorite'] = [
      '#type' => 'link',
      '#title' => 'Favorite',
//      '#url' => Url::fromUri($element['#url']),
//      '#url' => 'add-favorite/nojs',
      '#url' => Url::fromRoute('capital_news.favoriteajax'),
    ];
 
    // Create a description render array using #description.
    $element['description'] = [
      '#markup' => $element['#description'],
//      '#allowed_tags' => ['strong'],
    ];
    
    $element['news_type'] = [
      '#markup' => $element['#news_type'],
//      '#allowed_tags' => ['strong'],
    ];
    $element['title'] = [
      '#markup' => $element['#label'],
//      '#allowed_tags' => ['strong'],
    ];
    
    return $element;
  }
}
