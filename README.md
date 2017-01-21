#Commerce Price Updater

##CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

##INTRODUCTION

Most customers of company I work for use some sort of product management
software for keeping track of their product inventory. All these applications
have option to export product prices to a CSV file, so it is very important to
offer our customers option to easily update prices on their website, without
creating complicated and often expensive API integrations. That is the main
reason behind creating this Drupal 8 module.

To use this module you must have Drupal Commerce 2.x installed. We use this
module internally, and it is still in a development phase, just like Drupal
Commerce 2.x, so if you find bugs or if you want to suggest a feature please
open an issue. For now you can import prices only from CSV files, but I plan to
add XML and JSON support. In the 'examples' folder you can find sample CSV file.

##REQUIREMENTS

Drupal Commerce 2.x.

##INSTALLATION

1. Make sure that you installed and enabled Drupal Commerce 2.x
2. Install module as usual via Drupal UI, Drush or Composer
3. Go to "Extend" and enable the Commerce Price Updater module.

##CONFIGURATION

You can choose the default separator in the module configuration:
(admin/commerce/config/price-updater)

###AUTHOR

Goran Nikolovski  
Website: (http://www.gorannikolovski.com)  
Drupal: (https://www.drupal.org/user/3451979)  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: (http://www.studiopresent.com)  
Drupal: (https://www.drupal.org/studio-present)  
Email: info@studiopresent.com
