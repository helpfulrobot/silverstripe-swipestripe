<?php
/**
 * Shipping rates that can be set in {@link SiteConfig}. Several flat rates can be set 
 * for any supported shipping country.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage shipping
 */
class FlatFeeShippingRate extends DataObject
{
  
    /**
   * Fields for this shipping rate
   * 
   * @var Array
   */
  public static $db = array(
    'Title' => 'Varchar',
    'Description' => 'Varchar',
    'Amount' => 'Money'
    );
    
    /**
     * Shipping rates are associated with SiteConfigs.
     * 
     * TODO The CTF in SiteConfig does not save the SiteConfig ID correctly so this is moot
     * 
     * @var unknown_type
     */
    public static $has_one = array(
    'SiteConfig' => 'SiteConfig',
      'Country' => 'Country_Shipping'
  );
    
  /**
   * Field for editing a {@link FlatFeeShippingRate}.
   * 
   * @return FieldSet
   */
  public function getCMSFields_forPopup()
  {
      $fields = new FieldSet();
    
      $fields->push(new TextField('Title', _t('FlatFeeShippingRate.LABEL', 'Label')));
      $fields->push(new TextField('Description', _t('FlatFeeShippingRate.DESCRIPTION', 'Description')));
    
      $amountField = new MoneyField(_t('FlatFeeShippingRate.AMOUNT', 'Amount'));
      $amountField->setAllowedCurrencies(Product::$allowed_currency);
      $fields->push($amountField);
    
      $countryField = new DropdownField('CountryID', _t('FlatFeeShippingRate.COUNTRY', 'Country'), Country::shipping_countries());
      $fields->push($countryField);

      return $fields;
  }
  
  /**
   * Label for using on {@link FlatFeeShippingField}s.
   * 
   * @see FlatFeeShippingField
   * @return String
   */
  public function Label()
  {
      return $this->Title . ' ' . $this->SummaryOfAmount();
  }
  
  /**
   * Summary of the amount
   * 
   * @return String Amount formatted with Nice()
   */
  public function SummaryOfAmount()
  {
      return $this->Amount->Nice();
  }
}
