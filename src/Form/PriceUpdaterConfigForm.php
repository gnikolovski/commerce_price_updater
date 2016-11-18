<?php

namespace Drupal\commerce_price_updater\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Contains \Drupal\commerce_price_updater\Form\PriceUpdaterConfigForm.
 */

/**
 * Defines a form that configures commerce‚price updater settings.
 */
class PriceUpdaterConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_price_updater_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('commerce_price_updater.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_price_updater.settings');

    $form['default_separator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default CSV separator'),
      '#required' => TRUE,
      '#options' => array(
        '0' => $this->t('Comma'),
        '1' => $this->t('Semicolon'),
        '2' => $this->t('TAB'),
        '3' => $this->t('Custom'),
      ),
      '#description' => t('Choose the default separator you will use in your CSV files.'),
      '#default_value' => !empty($config->get('default_separator')) ? $config->get('default_separator') : 0,
    );

    $form['custom_separator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom separator'),
      '#size' => 10,
      '#description' => t('Enter your custom CSV column separator.'),
      '#default_value' => $config->get('custom_separator'),
      '#states' => array(
        'visible' => array(
          ':input[name="default_separator"]' => array('value' => '3'),
        ),
      ),
    );

    $form['file_status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make CSV files permanent'),
      '#description' => $this->t('Check this option to set status of uploaded CSV files to permanent.'),
      '#default_value' => $config->get('file_status'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $default_separator = $form_state->getValue('default_separator');
    $custom_separator = $form_state->getValue('custom_separator');

    if ($default_separator == 3 && $custom_separator == '') {
      $form_state->setErrorByName('custom_separator', $this->t('You must enter a value for the custom separator field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('commerce_price_updater.settings');
    $config->set('default_separator', $values['default_separator']);
    $config->set('custom_separator', $values['custom_separator']);
    $config->set('file_status', $values['file_status']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
