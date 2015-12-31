<?php
/**
 * So that {@link FlatFeeTaxRate}s can be created in {@link SiteConfig}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage shipping
 */
class FlatFeeTaxConfigDecorator extends DataObjectDecorator
{

    /**
   * Attach {@link FlatFeeTaxRate}s to {@link SiteConfig}.
   * 
   * @see DataObjectDecorator::extraStatics()
   */
    public function extraStatics()
    {
        return array(
            'has_many' => array(
              'FlatFeeTaxRates' => 'FlatFeeTaxRate'
            )
        );
    }

    /**
     * Create {@link ComplexTableField} for managing {@link FlatFeeTaxRate}s.
     * 
     * @see DataObjectDecorator::updateCMSFields()
     */
  public function updateCMSFields(FieldSet &$fields)
  {

    //$fields->addFieldToTab("Root", new TabSet('Shop')); 
    $fields->addFieldToTab("Root.Shop",
      new TabSet('Tax')
    );
      $fields->addFieldToTab("Root.Shop.Tax",
      new Tab('FlatFeeTax')
    );
    
      $managerClass = (class_exists('DataObjectManager')) ? 'DataObjectManager' : 'ComplexTableField';
      $flatFeeManager = new $managerClass(
      $this->owner,
      'FlatFeeTaxRates',
      'FlatFeeTaxRate',
      array(
        'Title' => 'Label',
        'Description' => 'Description',
        'Country.Title' => 'Country',
        'SummaryOfRate' => 'Rate'
      ),
      'getCMSFields_forPopup'
    );
      $fields->addFieldToTab("Root.Shop.Tax.FlatFeeTax", $flatFeeManager);
  }
}
