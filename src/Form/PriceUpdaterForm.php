<?php

namespace Drupal\commerce_price_updater\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Class PriceUpdaterForm.
 *
 * @package Drupal\commerce_price_updater\PriceUpdaterForm
 */
class PriceUpdaterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'price_updater';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = 'public://commerce-price-updater/';
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);

    $config = \Drupal::config('commerce_price_updater.settings');

    $form['csv_file'] = array(
      '#title' => $this->t('CSV file'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://commerce-price-updater/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv'),
        'file_validate_size' => array(10485760),
      ),
      '#required' => TRUE,
      '#description' => $this->t('CSV format: SKU, PRICE. Replace comma with your separator.'),
    );

    $form['separator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Separator'),
      '#options' => array(
        '0' => t('Comma'),
        '1' => t('Semicolon'),
        '2' => t('TAB'),
        '3' => t('Custom'),
      ),
      '#description' => $this->t('Choose or set <a href="/admin/commerce/price-updater/config">default separator</a> used in CSV files.'),
      '#default_value' => $config->get('default_separator'),
    );

    $form['custom_separator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom separator'),
      '#size' => 10,
      '#description' => t('Enter your custom CSV column separator.'),
      '#default_value' => $config->get('custom_separator'),
      '#states' => array(
        'visible' => array(
          ':input[name="separator"]' => array('value' => '3'),
        ),
      ),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update prices'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $separator = $form_state->getValue('separator');
    $custom_separator = $form_state->getValue('custom_separator');

    if ($separator == 3 && $custom_separator == '') {
      $form_state->setErrorByName('custom_separator', $this->t('You must enter a value for the custom separator field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('commerce_price_updater.settings');
    $selected_separator = $form_state->getValue('separator');
    switch ($selected_separator) {
      case 0:
        $separator = ',';
        break;

      case 1:
        $separator = ';';
        break;

      case 2:
        $separator = "\t";
        break;

      case 3:
        $separator = $form_state->getValue('custom_separator');
        break;
    }

    $csv_file = $form_state->getValue('csv_file');
    $file = File::load($csv_file[0]);
    if (!$file) {
      drupal_set_message($this->t('CSV file not found.'), 'error');
      return FALSE;
    }

    $file_status = $config->get('file_status');
    if ($file_status == 1) {
      $file->setPermanent();
    }
    $file->save();
    $file_path = $file->getFileUri();
    if (!file_exists($file_path)) {
      drupal_set_message($this->t('CSV file not found.'), 'error');
      return FALSE;
    }

    $handle = fopen($file_path, 'r');
    $batch = array(
      'operations' => array(),
      'title' => $this->t('Updating product variation prices...'),
    );

    $counter = 0;
    while ($line = fgetcsv($handle, 4096, $separator)) {
      if (!$line) {
        continue;
      }
  	  $sku = isset($line[0]) ? trim($line[0]) : NULL;
      $price = isset($line[1]) ? trim($line[1]) : NULL;
      if ($sku && $price && is_numeric($price)) {
        $batch['operations'][] = array(
          'Drupal\commerce_price_updater\PriceUpdater::update',
          array($sku, $price),
        );
        $counter++;
      }
    }
    fclose($handle);

    if ($counter > 0) {
      batch_set($batch);
      drupal_set_message($this->t('Product prices successfully updated.'));
    }
    else {
      drupal_set_message($this->t('Nothing to import. Please check your CSV file and separator.'), 'warning');
    }
  }

}
