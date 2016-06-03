<?php

namespace Drupal\capital_views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area ViewAreaButtonArea handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("view_area_button_area")
 */
class ViewAreaButtonArea extends AreaPluginBase {
  /**
   * {@inheritdoc}
   */
  /**
   * Extend base class defineOptions().
   *
   * For initially defining the configurable options for the button.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['viewareabuttonrequiredpermission'] = array(
      'default' => 'create page content',
      'translatable' => FALSE,
    );
    $options['viewareabuttonlabel'] = array(
      'default' => 'Add page',
      'translatable' => TRUE,
    );
    $options['viewareabuttonuri'] = array(
      'default' => '/node/add/page',
      'translatable' => FALSE,
    );
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  /**
   * Extend base class buildOptionsForm().
   *
   * For rendering HTML for the button's configurable options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['viewareabuttonrequiredpermission'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Visible when user has this permission'),
      '#default_value' => $this->options['viewareabuttonrequiredpermission'],
      '#description' => $this->t('May be blank.  If a permission is provided, then only users with the permission will see the button.  You can change the default id "page" to the id of any other content type.'),
    );
    $form['viewareabuttonlabel'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $this->options['viewareabuttonlabel'],
      '#description' => $this->t('The label for the button.  The available tokens below can be used here.  Is translated.'),
    );

    $form['viewareabuttonuri'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button URI'),
      '#default_value' => $this->options['viewareabuttonuri'],
      '#description' => $this->t('The URI to navigate to. <ul><li>The default is for adding a node of the content type "page".  You can change the default id "page" to the id of any other content type.</li><li>The available tokens below can be used here.</li><li>The tokens "[node:title]" and "[node:nid]" can be used when the view will be displayed on pages that have a primary node.</li><li>You may use <a href="https://www.drupal.org/project/prepopulate">the Prepopulate module</a> to prepopulate Entity Reference fields.  Example syntax:  /node/add/page?edit[entity_ref_field_machine_name_here]=[node:title] ([node:nid])&destination=node/[node:nid]<br>If you want to use Prepopulate for an Entity Reference field, then as of as of February 14, 2016, you will have to manually patch the Prepopulate module as per issue #h<a href="https://www.drupal.org/node/2668010">2668010</a>.</li></ul>'
      ),
    );
    // Display available global tokens.
    parent::globalTokenForm($form, $form_state);

    // NOTE:  If the view is for a single content type, then during the view
    // render that content type's id can be retrieved with the following code.
    // Perhaps this code code be useful in a future version of this module
    // plugin?  (to help with default values?)
    // $typeValue = $this->view->filter['type']->value;
    // $this->view->filter['type']->value[key($typeValue)];
  }
  /**
   * {@inheritdoc}
   */
  /**
   * Implement Drupal\views\Plugin\views\AreaPluginBase::render().
   *
   * For rendering HTML for a view area, e.g. a header or footer.
   */
  public function render($empty = FALSE) {
    // We check if the views result are empty, OR the settings of this area
    // force showing this area even if the view is empty,
    // AND if the current user has the permission the configuring user
    // specified.
    if ((!$empty || !empty($this->options['empty']))
        && \Drupal::currentUser()->hasPermission($this->options['viewareabuttonrequiredpermission'])) {

      // Uncomment any of the following lines during debugging to print object
      // information to the page's message area
      // ksm($GLOBALS);
      // ksm(\Drupal::routeMatch()->getParameter('node'));
      // // The following line displays all tokens from the token service
      // ksm(\Drupal::service('token')->getInfo() );
      // ksm($this);
      // ksm($this->view);
      $output = array();

      // Replace any tokens the configuring user provided.
      $buttonuri = $this->globalTokenReplace($this->options['viewareabuttonuri']);

      // Perhaps replace preceding and following lines of code with code from
      // here:
      // https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Utility!Token.php/function/Token%3A%3Areplace/8
      // (To view a list of the currently available tokens on your site, you
      // can navigate to the Help > Token (admin/help/token) page.).
      // -
      // If the configuring user provided the token "[node:nid]" then replace
      // it with the id of the active node.
      if (strpos($buttonuri, '[node:nid]') !== FALSE && (\Drupal::routeMatch()->getParameter('node'))) {
        $buttonuri = str_replace("[node:nid]", \Drupal::routeMatch()->getParameter('node')->id(), $buttonuri);
      }

      // If the user provided the token "[node:title]" then replace it with
      // the title of the active node.
      if (strpos($buttonuri, '[node:title]') !== FALSE && (\Drupal::routeMatch()->getParameter('node'))) {
        $buttonuri = str_replace("[node:title]", \Drupal::routeMatch()->getParameter('node')->getTitle(), $buttonuri);
      }

      // If the user did not provide a URL that starts with http: or https:
      // then set the prefix it so its scheme is internal:
      if (substr($buttonuri, 0, 5) != 'http:' && substr($buttonuri, 0, 6) != 'https:') {
        $buttonuri = 'internal:' . $buttonuri;
      }

      // Create a link for the button action from the provided label and uri.
      $output['link'] = [
        '#title' => $this->t($this->globalTokenReplace($this->options['viewareabuttonlabel'])),
        '#type' => 'link',
        '#url' => Url::fromUri($buttonuri) ,
        '#attributes' => [
          'class' => ['button', 'secondary', 'btn', 'btn-info', 'pull-right'],
        ],
      ];

      return $output;
    }
    return array();
  }

}
