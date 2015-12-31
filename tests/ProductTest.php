<?php
/**
 * Testing {@link Product} attributes and options on product pages.
 * 
 * Summary of tests:
 * -----------------
 * delete product, is unpublished, versions still exist
 * new version of product created when amount changed
 * variations disabled when new attribute added
 * correct options for variations returned on product page on first, second and third attribute dropdowns
 * cannot save negative amount for product variation
 * 
 * TODO
 * ----
 * add new variation
 * add product to parent page, check URL works
 * add product to multiple categories, check that it appears on each
 * disable all variations, product should be unpublished
 * try saving product with 'action_publish' passed as a Get var, when no enabled variations exist product should not be published
 * 
 * add product to cart, stock depletes latest version of product
 * add variation to cart, stock depletes latest version of variation
 * remove product from cart, stock replenishes latest version of product
 * remote variation from cart, stock replenishes latest version of variation
 * scheduled task deletes order and associated objects, replenishes stock
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 */
class ProductTest extends SWSTest
{

    public static $use_draft_site = true;
    
    public function setUp()
    {
        parent::setUp();
    }
    
    /**
     * Try to delete a product, make sure it is unpublished but that versions remain the same
     */
    public function testDeleteProduct()
    {
        $this->loginAs('admin');
        $productA = $this->objFromFixture('Product', 'productA');
        $productID = $productA->ID;
      
      //Publish
      $productA->doPublish();
        $this->assertTrue($productA->isPublished());

        $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
        $versionsAfterPublished = array();
        foreach ($versions as $versionRow) {
            $versionsAfterPublished[] = $versionRow;
        }

      
    //Delete
      $productA->delete();
        $this->assertTrue(!$productA->isPublished());

        $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
        $versionsAfterDelete = array();
        foreach ($versions as $versionRow) {
            $versionsAfterDelete[] = $versionRow;
        }
      
        $this->assertTrue($versionsAfterPublished == $versionsAfterDelete);

      //$versions = DB::query('SELECT * FROM "SiteTree_Live" WHERE "ID" = ' . $productID);
    }
    
    /**
     * Try to publish a product with amount changed
     */
    public function testChangeProductAmount()
    {
        $this->loginAs('admin');
        $productA = $this->objFromFixture('Product', 'productA');
        $productID = $productA->ID;
      
      //Publish
      $productA->doPublish();
        $this->assertTrue($productA->isPublished());

        $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
        $versionsAfterPublished = array();
        foreach ($versions as $versionRow) {
            $versionsAfterPublished[] = $versionRow;
        }

        $originalAmount = $productA->Amount;
      
        $newAmount = new Money();
        $newAmount->setAmount($originalAmount->getAmount() + 50);
        $newAmount->setCurrency($originalAmount->getCurrency());
      
        $this->assertTrue($newAmount->Amount != $originalAmount->Amount);
      
    //Update price and publish
      $productA->Amount = $newAmount;
        $productA->doPublish();

        $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
        $versionsAfterPriceChange = array();
        foreach ($versions as $versionRow) {
            $versionsAfterPriceChange[] = $versionRow;
        }

        $this->assertTrue(count($versionsAfterPublished) + 1 == count($versionsAfterPriceChange));
        $this->assertEquals($versionsAfterPriceChange[2]['AmountAmount'], $newAmount->getAmount());
    }
    
    /**
     * Try adding a new attribute to a product, existing variations that do not have an option set for 
     * the new attribute should be disabled
     */
    public function testVariationsDisabledAfterAttributeAdded()
    {
        $this->loginAs('admin');
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $variations = $teeshirtA->Variations();
      
        $this->assertTrue($variations->exists());
      
        foreach ($variations as $variation) {
            $this->assertTrue($variation->isEnabled());
        }
      
      //Add an attribute
      $cutAttribute = $this->objFromFixture('Attribute', 'attrCut');
        $existingAttributes = $teeshirtA->getManyManyComponents('Attributes');
        $existingAttributes->add($cutAttribute);

        $teeshirtA->writeComponents();

      //Add the default options for the new attribute
      $existingOptions = $teeshirtA->getComponents('Options');
        $defaultOptions = DataObject::get('Option', "ProductID = 0 AND AttributeID = 4");
      
        foreach ($defaultOptions as $option) {
            $existingOptions->add($option);
        }
        $teeshirtA->writeComponents();

        $teeshirtA->write();

        foreach ($teeshirtA->Variations() as $variation) {
            $this->assertTrue(!$variation->isEnabled());
        }
    }
    
    /**
     * Load the project page and test the first select for correct product options
     * 
     * # Teeshirt Variations
     * # Small, Red, Cotton
     * # Small, Red, Polyester
     * # Small, Purple, Cotton
     * # Small, Purple, Polyester
     * #
     * # Medium, Purple, Cotton
     * # Medium, Purple, Silk
     * #
     * # Extra Large, Red, Cotton
     * # Extra Large, Red, Polyester
     * # Extra Large, Purple, Cotton
     */
  public function testProductOptionsFirstSet()
  {
      $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
      $attributes = $teeshirtA->Attributes();
      $options = $teeshirtA->Options();
      $variations = $teeshirtA->Variations();

      $this->loginAs('admin');
      $teeshirtA->doPublish();
      $this->logOut();
      
      $this->loginAs($this->objFromFixture('Customer', 'buyer'));
      $this->get(Director::makeRelative($teeshirtA->Link()));

      //Check that options fields exist for each attribute
      $attributeOptionsMap = array();
      $firstAttributeID = null;
      foreach ($attributes as $attribute) {
          if (!$firstAttributeID) {
              $firstAttributeID = $attribute->ID;
          }
        
        //$this->assertPartialMatchBySelector('#Options['.$attribute->ID.']', '1');

        $options = $teeshirtA->getOptionsForAttribute($attribute->ID);
          $attributeOptionsMap[$attribute->ID] = $options->map();
      }
    
      
      //Check that first option select has valid options in it
      $tempAttributeOptionsMap = $attributeOptionsMap;
      $firstAttributeOptions = array_shift($tempAttributeOptionsMap);
      
      $productPage = new DOMDocument();
      $productPage->loadHTML($this->mainSession->lastContent());
      //echo $productPage->saveHTML();

      //Find the options for the first attribute select
      $selectFinder = new DomXPath($productPage);
      $firstAttributeSelectID = 'AddToCartForm_AddToCartForm_Options-'.$firstAttributeID;
      $firstSelect = $selectFinder->query("//select[@id='$firstAttributeSelectID']");
      
      foreach ($firstSelect as $node) {
          $tmp_doc = new DOMDocument();
          $tmp_doc->appendChild($tmp_doc->importNode($node, true));
          $innerHTML = $tmp_doc->saveHTML();

          $optionFinder = new DomXPath($tmp_doc);

          if ($firstAttributeOptions) {
              foreach ($firstAttributeOptions as $optionID => $optionTitle) {
                  $options = $optionFinder->query("//option[@value='$optionID']");
                  $this->assertEquals(1, $options->length);
              }
          }
      }
  }
  
  /**
   * Post add to cart form and retreive second set of product options
   */
  public function testProductOptionsSecondSet()
  {
      $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
      $attributes = $teeshirtA->Attributes();
      $options = $teeshirtA->Options();
      $variations = $teeshirtA->Variations();
    
      $this->loginAs('admin');
      $teeshirtA->doPublish();
      $this->logOut();
    
      $this->loginAs($this->objFromFixture('Customer', 'buyer'));
      $this->get(Director::makeRelative($teeshirtA->Link()));
    
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
      $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
      $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
      $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
      $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
      $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      $teeshirtAPolyesterOpt = $this->objFromFixture('Option', 'optPolyesterTeeshirt');
    
      $data = $this->getFormData('AddToCartForm_AddToCartForm');
      unset($data["Options[{$colorAttr->ID}]"]);
      unset($data["Options[{$materialAttr->ID}]"]);
      unset($data["Options[{$sizeAttr->ID}]"]);
    
      $data['Options'][$colorAttr->ID] = $teeshirtARedOpt->ID;
      $data['NextAttributeID'] = $materialAttr->ID;
    
      $this->post(
      Director::absoluteURL($teeshirtA->Link() . '/options/'),
      $data
    );
    
      $decoded = json_decode($this->mainSession->lastContent());
    
      $expected = array(
      $teeshirtACottonOpt->ID => $teeshirtACottonOpt->Title,
      $teeshirtAPolyesterOpt->ID => $teeshirtAPolyesterOpt->Title
    );
      $actual = array();
      foreach ($decoded->options as $optionID => $optionName) {
          $actual[$optionID] = $optionName;
      }
      $this->assertEquals($expected, $actual);
  }
  
  /**
   * Post add to cart form and retreive third set of product options
   */
  public function testProductOptionsThirdSet()
  {
      $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
      $attributes = $teeshirtA->Attributes();
      $options = $teeshirtA->Options();
      $variations = $teeshirtA->Variations();
      
      $this->loginAs('admin');
      $teeshirtA->doPublish();
      $this->logOut();
      
      $this->loginAs($this->objFromFixture('Customer', 'buyer'));
      $this->get(Director::makeRelative($teeshirtA->Link()));

      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
      $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
      $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
      $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
      $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
      $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      $teeshirtAPolyesterOpt = $this->objFromFixture('Option', 'optPolyesterTeeshirt');
      $teeshirtAExtraLargeOpt = $this->objFromFixture('Option', 'optExtraLargeTeeshirt');
      
      $data = $this->getFormData('AddToCartForm_AddToCartForm');
      unset($data["Options[{$colorAttr->ID}]"]);
      unset($data["Options[{$materialAttr->ID}]"]);
      unset($data["Options[{$sizeAttr->ID}]"]);
      
      $data['Options'][$colorAttr->ID] = $teeshirtARedOpt->ID;
      $data['Options'][$materialAttr->ID] = $teeshirtACottonOpt->ID;
      $data['NextAttributeID'] = $sizeAttr->ID;

      $this->post(
        Director::absoluteURL($teeshirtA->Link() . '/options/'),
        $data
      );
      
      $decoded = json_decode($this->mainSession->lastContent());
      
      $expected = array(
        $teeshirtASmallOpt->ID => $teeshirtASmallOpt->Title,
        $teeshirtAExtraLargeOpt->ID => $teeshirtAExtraLargeOpt->Title
      );
      $actual = array();
      foreach ($decoded->options as $optionID => $optionName) {
          $actual[$optionID] = $optionName;
      }
      $this->assertEquals($expected, $actual);
  }
  
    /**
     * Try to save a Variation with a negative price difference
     * 
     * @see Variation::validate()
     */
    public function testNegativeVariationPrice()
    {
        $this->loginAs('admin');
        $smallRedShortsVariation = $this->objFromFixture('Variation', 'shortsSmallRedCotton');
      
        $originalAmount = $smallRedShortsVariation->Amount;
      
        $this->assertTrue($originalAmount->getAmount() >= 0);
      
        $newAmount = new Money();
        $newAmount->setAmount(-1);
        $newAmount->setCurrency($originalAmount->getCurrency());
      
        $smallRedShortsVariation->Amount = $newAmount;
        $errorMessage = null;
        try {
            $smallRedShortsVariation->write();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
      
      //Make sure there is an error when trying to save
      $this->assertTrue($errorMessage != null);
    }
}
