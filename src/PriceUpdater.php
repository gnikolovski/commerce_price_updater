<?php

namespace Drupal\commerce_price_updater;

class PriceUpdater {

  /**
   * Set price for product with provided SKU.
   */
  public static function update($sku, $price){
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
    $product_variations = $storage->loadByProperties(array('sku' => $sku));
    if (!$product_variations) {
      return FALSE;
    }

    foreach ($product_variations as $product_variation) {
      try {
        $price_obj = $product_variation->getPrice();
        $price_currency = $price_obj->getCurrencyCode();
        $new_price = new \Drupal\commerce_price\Price($price, $price_currency);
        $product_variation->set('price', $new_price);
        $product_variation->save();
      } catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
    }
  }

}
