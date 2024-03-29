<?php

namespace Drupal\commerce_price_updater\Form;

use Drupal\commerce_price\Price;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PriceUpdaterForm.
 *
 * @package Drupal\commerce_price_updater\PriceUpdaterForm
 */
class PriceUpdaterForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The file service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactory $config_factory,
    EntityTypeManager $entity_type_manager,
    Messenger $messenger,
    FileSystem $file_system
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'price_updater_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = 'public://commerce-price-updater/';
    $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);

    $config = $this->configFactory->get('commerce_price_updater.settings');

    $form['csv_file'] = [
      '#title' => $this->t('CSV file'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://commerce-price-updater/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        'file_validate_size' => [10485760],
      ],
      '#required' => TRUE,
      '#description' => $this->t('CSV format: SKU, PRICE, LIST PRICE. Replace comma with your separator. LIST PRICE is optional column.'),
    ];

    $form['separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Separator'),
      '#options' => [
        '0' => $this->t('Comma'),
        '1' => $this->t('Semicolon'),
        '2' => $this->t('TAB'),
        '3' => $this->t('Custom'),
      ],
      '#description' => $this->t('Choose or set <a href="/admin/commerce/config/price-updater">default separator</a> used in CSV files.'),
      '#default_value' => $config->get('default_separator'),
    ];

    $form['custom_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom separator'),
      '#size' => 10,
      '#description' => $this->t('Enter your custom CSV column separator.'),
      '#default_value' => $config->get('custom_separator'),
      '#states' => [
        'visible' => [
          ':input[name="separator"]' => ['value' => '3'],
        ],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update prices'),
      '#button_type' => 'primary',
    ];

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
    $config = $this->configFactory->get('commerce_price_updater.settings');
    $selected_separator = $form_state->getValue('separator');
    switch ($selected_separator) {
      case 0:
        $separator = ',';
        break;

      case 2:
        $separator = "\t";
        break;

      case 3:
        $separator = $form_state->getValue('custom_separator');
        break;

      default:
        $separator = ';';
        break;
    }

    $csv_file = $form_state->getValue('csv_file');
    if (!isset($csv_file[0])) {
      $this->messenger->addError($this->t('CSV file not found.'));
      return FALSE;
    }

    $file = $this->entityTypeManager->getStorage('file')->load($csv_file[0]);
    if (!$file) {
      $this->messenger->addError($this->t('CSV file not found.'));
      return FALSE;
    }

    $file_status = $config->get('file_status');
    if ($file_status == 1) {
      $file->setPermanent();
    }
    $file->save();
    $file_path = $file->getFileUri();
    if (!file_exists($file_path)) {
      $this->messenger->addError($this->t('CSV file not found.'));
      return FALSE;
    }

    $handle = fopen($file_path, 'r');
    $batch = [
      'operations' => [],
      'title' => $this->t('Updating product variation prices...'),
    ];

    $counter = 0;
    while ($line = fgetcsv($handle, 4096, $separator)) {
      if (!$line) {
        continue;
      }
      $sku = isset($line[0]) ? trim($line[0]) : NULL;
      $price = isset($line[1]) ? trim($line[1]) : NULL;
      $list_price = isset($line[2]) ? trim($line[2]) : NULL;
      if (
        $sku &&
        $price &&
        is_numeric($price)
      ) {
        $batch['operations'][] = [[$this, 'updateProductPrice'], [$sku, $price, $list_price]];
        $counter++;
      }
    }
    fclose($handle);

    if ($counter > 0) {
      batch_set($batch);
      $this->messenger->addMessage($this->t('Product prices successfully updated.'));
    }
    else {
      $this->messenger->addWarning($this->t('Nothing to import. Please check your CSV file and separator.'));
    }
  }

  /**
   * Update product price.
   */
  public function updateProductPrice($sku, $price, $list_price) {
    $storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $product_variations = $storage->loadByProperties(['sku' => $sku]);
    if (!$product_variations) {
      return FALSE;
    }

    foreach ($product_variations as $product_variation) {
      try {
        $price_obj = $product_variation->getPrice();
        $price_currency = $price_obj->getCurrencyCode();
        $new_price = new Price($price, $price_currency);
        $new_list_price = NULL;
        if (is_numeric($list_price)) {
          $new_list_price = new Price($list_price, $price_currency);
        }
        $product_variation->set('price', $new_price);
        $product_variation->set('list_price', $new_list_price);
        $product_variation->save();
      }
      catch (\Exception $e) {
        $this->messenger->addError($e->getMessage());
      }
    }
  }

}
