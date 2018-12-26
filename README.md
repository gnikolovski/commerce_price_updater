# Commerce Price Updater

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

## INTRODUCTION

Most customers of company I work for use some sort of product management
software for keeping track of their product inventory. All these applications
have option to export product prices to a CSV file, so it is very important to
offer our customers an option to easily update prices on their website, without
creating complicated and often expensive API integrations. That is the main
reason behind creating this Drupal 8 module.

To use this module you must have Drupal Commerce 2.x installed. We use this
module internally, and it is still in a development phase, just like Drupal
Commerce 2.x, so if you find any bugs or if you want to suggest a feature please
open an issue.

After you install the module go to '/admin/commerce/price-updater' where you can
upload CSV file and update your product prices. CSV file should have only two
columns: SKU and PRICE, separated by a separator of your choosing. Price must be 
a number, and if your price has decimal places use period to format it
(e.g: 999.99). Uploaded CSV files are stored in a separate folder, and you can
choose if you wish to save these files permanently or temporarily. In the
module's examples folder you can find a sample CSV file.

## REQUIREMENTS

Drupal Commerce 2.x.

## INSTALLATION

1. Make sure that you installed and enabled Drupal Commerce 2.x
2. Install module as usual via Drupal UI, Drush or Composer
3. Go to "Extend" and enable the Commerce Price Updater module.

## CONFIGURATION

You can choose the default separator in the module configuration:
'/admin/commerce/config/price-updater'

### AUTHOR

Goran Nikolovski  
Website: http://gorannikolovski.com  
Drupal: https://www.drupal.org/u/gnikolovski  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: http://www.studiopresent.com  
Drupal: https://www.drupal.org/studio-present  
Email: info@studiopresent.com  
