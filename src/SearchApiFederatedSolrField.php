<?php

class SearchApiFederatedSolrField extends SearchApiAbstractAlterCallback {

  /**
   * @var SearchApiIndex
   */
  protected $index;
  /**
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    // TODO: Implement alterItems() method.
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    $form['fields'] = ['#type' => 'container'];

    // Temporarily hard-coded
    $fields = ['field1'];

    foreach ($fields as $field) {
      $item = [
        '#type' => 'fieldset',
        '#title' => !empty($this->options['fields'][$field]) ?
          "{$this->options['fields'][$field]['label']} ({$this->options['fields'][$field]['machine_name']})" : t('New field'),
        '#collapsible' => TRUE,
        '#collapsed' => isset($this->options['fields'][$field]),
      ];
      $item['label'] = [
        '#type' => 'textfield',
        '#title' => t('Label'),
        '#default_value' => $this->options['fields'][$field]['label'],
      ];
      $item['machine_name'] = [
        '#type' => 'textfield',
        '#title' => t('Machine Name'),
        '#required' => TRUE,
        '#default_value' => $this->options['fields'][$field]['machine_name'],
      ];
      $item['multivalue'] = array(
        '#type' => 'radios',
        '#title' => t('Multi-value field'),
        '#options' => array(0 => t('No'), 1 => t('Yes')),
        '#default_value' => (TRUE === isset($field['multivalue'])) ? $field['multivalue'] : 1,
        '#required' => TRUE,
        '#description' => t('Whether the combined field is a multi-valued field'),
      );
      $item['type'] = array(
        '#type' => 'select',
        '#title' => t('Data type'),
        '#options' => search_api_default_field_types(),
        '#default_value' => (TRUE === isset($field['type'])) ? $field['type'] : 'integer',
        '#required' => TRUE,
        '#description' => t('Data type to save field as'),
      );
      $item['bundle'] = [
        '#type' => 'fieldset',
        '#title' => t('Value to index for each type'),
        '#description' => t('Enter a token or plain text in the field for each type of indexed item.'),
      ];

      $entity_info = entity_get_info($this->index->getEntityType());
      foreach ($entity_info['bundles'] as $bundle => $bundle_info) {
        $item['bundle'][$bundle] = [
          '#type' => 'textfield',
          '#title' => "{$bundle_info['label']} ({$bundle})",
          '#default_value' => $this->options['fields'][$field]['bundle'][$bundle],
        ];
      }

      $form['fields']['field1'] = $item;
    }

    $form['tokens'] = [
      '#type' => 'fieldset',
      '#title' => t('Tokens'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['tokens']['tokens'] = [
      '#theme' => 'token_tree',
      '#token_types' => [$this->index->getEntityType()],
      '#global_types' => FALSE,
      '#recursion_limit' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    $properties = [];

    foreach ($this->options['fields'] as $field) {
      $properties[$field['machine_name']] = [
        'label' => $field['label'],
        'description' => t('Federated field.'),
        'type' => $field['type'],
      ];
    }

    return $properties;
  }


}
