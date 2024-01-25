<?php

namespace Drupal\Tests\commerce_price_updater\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface.
 *
 * @group commerce_price_updater
 */
class CommercePriceUpdaterUITest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_price_updater',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer commerce price updater']));
  }

  /**
   * Tests form structure.
   */
  public function testFormStructure() {
    $this->drupalGet('admin/commerce/config/price-updater');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Commerce Price Updater | Drupal');
    $this->assertSession()->selectExists('edit-default-separator');
    $this->assertSession()->fieldExists('edit-custom-separator');
    $this->assertSession()->checkboxNotChecked('edit-file-status');
    $this->assertSession()->buttonExists($this->t('Save configuration'));
  }

  /**
   * Tests form access.
   */
  public function testFormAccess() {
    $this->drupalLogout();
    $this->drupalGet('admin/commerce/config/price-updater');
    $this->assertSession()->statusCodeEquals(403);
  }

}
