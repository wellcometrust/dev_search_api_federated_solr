<?php

/**
 * @copyright Copyright (c) 2018 Palantir.net
 */

/**
 * Class SearchApiFederatedSolrRemap
 * Provides a Search API index data alteration that remaps property names for indexed items.
 */
class SearchApiFederatedSolrRemap extends SearchApiAbstractAlterCallback {

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    if (is_array($this->options['properties'])) {
      return $this->options['properties'];
    }

    return [];
  }


  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    foreach ($items as &$item) {
      foreach ($this->options['remap'] as $destination => $source) {
        if ($source && isset($item->{$source})) {
          $item->{$destination} = $item->{$source};
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    $form['remap'] = [
      '#type' => 'fieldset',
      '#title' => t('Remap properties'),
    ];
    foreach ($this->federatedFieldOptions() as $k => $title) {
      $form['remap'][$k] = [
        '#type' => 'select',
        '#title' => $title,
        '#options' => $this->indexFieldOptions(),
        '#default_value' => isset($this->options['remap'][$k]) ? $this->options['remap'][$k] : '',
      ];
    }

    return $form;
  }

  public function configurationFormSubmit(array $form, array &$values, array &$form_state) {
    $properties = [];

    $fields = $this->index->getFields(FALSE);
    foreach (array_filter($values['remap']) as $destination => $source) {
      $properties[$destination] = [
        'label' => t('@field (remapped from @key)', ['@field' => $fields[$source]['name'], '@key' => $source]),
        'description' => $fields[$source]['description'],
        'type' => $fields[$source]['type'],
      ];
    }

    $values['properties'] = $properties;

    return parent::configurationFormSubmit($form, $values, $form_state); // TODO: Change the autogenerated stub
  }


  protected function federatedFields() {
    return [
      'federated_title' => [
        'name' => t('Federated Title'),
        'description' => '',
        'type' => 'string'
      ],
      'rendered_output' => [
        'name' => t('Rendered Output'),
        'description' => '',
        'type' => 'text',
      ]
    ];
  }

    protected function federatedFieldOptions() {
      $options = $this->federatedFields();
      array_walk($options, function (&$item, $key) {
        $item = "{$item['name']} ({$key})";
      });
      return $options;
    }

  protected function indexFieldOptions() {
    $options = array_diff_key($this->index->getFields(FALSE), $this->federatedFields());
    array_walk($options, function (&$item, $key) {
      $item = "{$item['name']} ({$key})";
    });
    return ['- ' . t('None') . ' -'] + $options;
  }

}
