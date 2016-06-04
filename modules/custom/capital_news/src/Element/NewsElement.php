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
    $element['link'] = [
      '#type' => 'link',
      '#title' => $element['#label'],
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

    $element['addtofavorite'] = [
      '#type' => 'link',
      '#title' => 'Favorite',
      '#url' => Url::fromRoute('capital_news.favoriteajax'),
      '#attributes' => [
        'class' => "add-favorate",
      ]

    ];

    $element['description'] = [
      '#markup' => $element['#description'],
    ];

    $element['news_type'] = [
      '#markup' => $element['#news_type'],
    ];
    $element['title'] = [
      '#markup' => $element['#label'],
    ];
    $element['url'] = [
      '#markup' => $element['#url'],
    ];

    return $element;
  }
}
