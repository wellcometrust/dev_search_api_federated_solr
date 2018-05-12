<?php

/**
 * @file
 * Contains \Drupal\search_api_solr_federated\Form\SearchApiFederatedSolrSearchAppSettingsForm.
 */

namespace Drupal\search_api_federated_solr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchApiFederatedSolrSearchAppSettingsForm.
 *
 * @package Drupal\search_api_federated_solr\Form
 */
class SearchApiFederatedSolrSearchAppSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_federated_solr_search_app_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'search_api_federated_solr.search_app.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('search_api_federated_solr.search_app.settings');

    $index_options = [];
    $search_api_indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();
    /* @var  $search_api_index \Drupal\search_api\IndexInterface */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search app path'),
      '#default_value' => $config->get('path'),
      '#description' => $this
        ->t('The path for the search app (Default: "/search-app").'),
    ];

    $form['search_index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#description' => $this->t('Defines <a href="/admin/config/search/search-api">which search_api index and server</a> the search app should use.'),
      '#options' => $index_options,
      '#default_value' => $config->get('index.id'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'getSiteName'],
        'event' => 'change',
        'wrapper' => 'site-name-property',
      ],
    ];

    $form['site_name_property'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['site-name-property'],
      ],
      '#value' => $config->get('index.has_site_name_property') ? 'true' : '',
    ];

    $form['set_search_site'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set the "Site name" facet to this site'),
      '#default_value' => $config->get('facet.site_name.set_default'),
      '#description' => $this
        ->t('When checked, only search results from this site will be shown, by default, until this site\'s checkbox is unchecked in the search app\'s "Site name" facet.'),
      '#states' => [
        'visible' => [
          ':input[name="site_name_property"]' => [
            'value' => "true"
          ],
        ],
      ],
    ];

    $form['no_results_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#default_value' => $config->get('content.no_results'),
      '#description' => $this
        ->t('This text is shown when a query returns no results. (Default: "Your search yielded no results.")'),
    ];


    $form['search_prompt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search prompt text'),
      '#default_value' => $config->get('content.search_prompt'),
      '#description' => $this
        ->t('This text is shown when no query term has been entered. (Default: "Please enter a search term.")'),
    ];

    $form['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of search results per page'),
      '#default_value' => $config->get('results.rows'),
      '#description' => $this
        ->t('The max number of results to render per search results page. (Default: 20)'),
    ];

    $form['page_buttons'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of pagination buttons'),
      '#default_value' => $config->get('pagination.buttons'),
      '#description' => $this
        ->t('The max number of numbered pagination buttons to show at a given time. (Default: 5)'),
    ];

    $form['#cache'] = ['max-age' => 0];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the search app configuration
    $config = $this->configFactory->getEditable('search_api_federated_solr.search_app.settings');

    // Set the search app path.
    $path = $form_state->getValue('path');
    $current_path = $config->get('path');
    if ($path && $path !== $current_path) {
      $config->set('path', $path);
      $rebuild_routes = TRUE;
    }

    // Set the search app configuration setting for the default search site flag.
    $set_search_site = $form_state->getValue('set_search_site');
    $config->set('facet.site_name.set_default', $set_search_site);

    // Get the id of the chosen index.
    $search_index = $form_state->getValue('search_index');
    // Save the selected index option in search app config (for form state).
    $config->set('index.id', $search_index);

    // Get the index configuration object.
    $index_config = \Drupal::config('search_api.index.' . $search_index);
    $site_name_property = $index_config->get('field_settings.site_name.configuration.site_name');
    $config->set('index.has_site_name_property', $site_name_property ? TRUE : FALSE);

    // Get the id of the chosen index's server.
    $index_server = $index_config->get('server');

    // Get the server url.
    $server_config = \Drupal::config('search_api.server.' . $index_server);
    $server = $server_config->get('backend_config.connector_config');
    // Get the required server config field data.
    $server_url = $server['scheme'] . '://' . $server['host'] . ':' . $server['port'];
    // Check for the non-required server config field data before appending.
    $server_url .= $server['path'] ?: '';
    $server_url .= $server['core'] ? '/' . $server['core'] : '';
    // Append the request handler.
    $server_url .= '/select';

    // Set the search app configuration setting for the solr backend url.
    $config->set('index.server_url', $server_url);

    // Set the no results text.
    $config->set('content.no_results', $form_state->getValue('no_results_text'));

    // Set the search prompt text.
    $config->set('content.search_prompt', $form_state->getValue('search_prompt_text'));

    // Set the number of rows.
    $config->set('results.rows', $form_state->getValue('rows'));

    // Set the number of pagination buttons.
    $config->set('pagination.buttons', $form_state->getValue('page_buttons'));


    $config->save();

    if ($rebuild_routes) {
      // Rebuild the routing information without clearing all the caches.
      \Drupal::service('router.builder')->rebuild();
    }

    parent::submitForm($form, $form_state);
  }

  public function getSiteName(array &$form, FormStateInterface $form_state) {
    // Get the id of the chosen index.
    $search_index = $form_state->getValue('search_index');
    // Get the index configuration object.
    $index_config = \Drupal::config('search_api.index.' . $search_index);
    $is_site_name_property = $index_config->get('field_settings.site_name.configuration.site_name') ? 'true' : '';

    $elem = [
      '#type' => 'hidden',
      '#name' => 'site_name_property',
      '#value' => $is_site_name_property,
      '#attributes' => [
        'id' => ['site-name-property'],
      ],
    ];

    return $elem;
  }
}
