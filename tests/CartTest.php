<?php
/**
 * Testing {@link Product}s added and removed from {@link Order}s.
 * 
 * Summary of tests:
 * -----------------
 * add product to cart
 * change quantity of product in cart
 * update product and add it to cart again checking version number
 * add negative quantity to cart
 * add 0 quantity to cart
 * try saving duplicate variations
 * try saving variation without full set of options
 * change product price after it is in the cart
 * cannot add non-published product to the cart
 * customer members can add stuff to carts
 * website visitors can add stuff to carts
 * add product to cart and change price
 * add product variation
 * change quantity of variation
 * add different variations for same product
 * add product and variation to cart and check version
 * add variation to cart with price change
 * check cart totals
 * 
 * TODO
 * ----
 * remove options from product and variaiton when the attribute is deleted
 * Test saving a product with a new attribute, existing variations without this attribute should be disabled
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 */
class CartTest extends SWSTest
{
    
    public function setUp()
    {
        parent::setUp();
        
        Director::set_environment_type('dev');

        //Check that payment module is installed
        $this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
    }

    /**
     * Create product and check basic attributes
     */
  public function testProduct()
  {
      $productA = $this->objFromFixture('Product', 'productA');
      $this->assertEquals($productA->dbObject('Amount')->getAmount(), 500.00, 'The price of Product A should be 500.');
      $this->assertEquals($productA->dbObject('Amount')->getCurrency(), 'NZD', 'The currency of Product A should be NZD.');
  }
    
    /**
     * Add an item to the cart for a basic product and check correct product added
     */
  public function testAddProductToCart()
  {

    //Add published product to cart
    $productA = $this->objFromFixture('Product', 'productA');

      $this->loginAs('admin');
      $productA->doPublish();
      $this->logOut();

      $productALink = $productA->Link();
      $this->get(Director::makeRelative($productALink));
      $this->submitForm('AddToCartForm_AddToCartForm', null, array(
      'Quantity' => 1
    ));

      $order = CartControllerExtension::get_current_order();
      $items = $order->Items();
      
      $firstItem = $items->First();
      $this->assertInstanceOf('ComponentSet', $items);
      $this->assertEquals(1, $items->Count());
      $this->assertInstanceOf('Item', $firstItem);
      $this->assertEquals(1, $firstItem->Quantity);
      
      //Check that the correct product has been added
      $firstProduct = $firstItem->Object();
      $this->assertInstanceOf('Product', $firstProduct);
      $this->assertEquals($productA->Title, $firstProduct->Title);
      $this->assertEquals($productA->dbObject('Amount')->getAmount(), $firstProduct->dbObject('Amount')->getAmount());
  }
    
    /**
     * Add product to the cart twice and check quantity
     */
    public function testAddProductQuantityToCart()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();

        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $items->First()->Quantity);
      
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 2
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
        $this->assertEquals(3, $items->First()->Quantity);
    }
    
    /**
     * Add negative quantity to cart, should have no effect on cart
     */
    public function testAddProductNegativeQuantityToCart()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();

        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $items->First()->Quantity);
      
        $message = null;
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => -1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $items->First()->Quantity);
    }
    
    /**
     * Adding product with zero quantity should have no effect on cart
     */
    public function testAddProductZeroQuantityToCart()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();

        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $items->First()->Quantity);
      
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 0
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $items->First()->Quantity);
    }
    
    /**
     * Published products should get different versions, new versions are new items in the cart
     */
    public function testAddProductVersionToCart()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();
      
        $firstVersion = $productA->Version;
        $this->assertTrue($firstVersion > 0);

        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(1, $items->Count());
      
        $firstProduct = $order->Items()->First()->Object();
        $this->assertEquals($firstVersion, $firstProduct->Version);
      
      //Publish again and check version in the cart
      $this->logInAs('admin');
      //$productA->forceChange();
      $productA->Title = 'Product A Changed';
        $productA->doPublish();
        $this->logOut();
      
        $secondVersion = $productA->Version;
        $this->assertTrue($secondVersion > $firstVersion);
      
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $this->assertEquals(2, $items->Count());
      
        $this->assertEquals($firstVersion, $order->Items()->First()->Object()->Version);
        $this->assertEquals($secondVersion, $order->Items()->Last()->Object()->Version);
    }
    
    /**
     * Add a product to the cart as a visitor to the website
     */
    public function testAddProductToCartLoggedOut()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();
      
        $loggedInAs = $this->session()->get('loggedInAs');
        $this->assertTrue(!$loggedInAs);

        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $this->assertEquals(1, $order->Items()->Count());
    }
    
    /**
     * Add a product logged in as a customer
     */
    public function testAddProductToCartLoggedInCustomer()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();
      
        $this->logInAs($this->objFromFixture('Customer', 'buyer'));
        $buyer = $this->objFromFixture('Customer', 'buyer');
        $loggedInAs = $this->session()->get('loggedInAs');
        $this->assertEquals($buyer->ID, $loggedInAs);
      
        $member = Customer::currentUser();
        $this->assertEquals(true, $member->inGroup('customers'));

        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $this->assertEquals(1, $order->Items()->Count());
    }
    
    /**
     * Change product price after it is in the cart, check that price has not changed in cart
     */
    public function testAddProductToCartChangePrice()
    {
        $productA = $this->objFromFixture('Product', 'productA');

        $this->logInAs('admin');
        $productA->doPublish();
        $this->logOut();
      
        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $firstItem = $items->First();
        $firstProduct = clone $productA;
      
        $this->assertEquals(1, $order->Items()->Count());
        $this->assertEquals($productA->Amount->getAmount(), $firstItem->Amount->getAmount());
        $this->assertEquals($productA->Amount->getCurrency(), $firstItem->Amount->getCurrency());
      
        $newAmount = new Money();
        $newAmount->setAmount(72.34);
        $newAmount->setCurrency('NZD');
      
        $this->logInAs('admin');
        $productA->Amount->setValue($newAmount);
        $productA->doPublish();
        $this->logOut();
      
        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();

        $firstItem = $items->First();
        $secondItem = $items->Last();

        $this->assertEquals(2, $order->Items()->Count());
      
        $this->assertEquals($firstProduct->Amount->getAmount(), $firstItem->Amount->getAmount());
        $this->assertEquals($firstProduct->Amount->getCurrency(), $firstItem->Amount->getCurrency());
      
        $this->assertEquals($newAmount->getAmount(), $secondItem->Amount->getAmount());
        $this->assertEquals($newAmount->getCurrency(), $secondItem->Amount->getCurrency());
    }

    /**
     * Add a product variation to the cart
     */
    public function testAddProductVariationToCart()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $this->logOut();

        $this->get(Director::makeRelative($teeshirtA->Link()));
      
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');
        $this->assertEquals('Enabled', $teeshirtAVariation->Status);
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      
        $this->assertEquals($teeshirtASmallOpt->ID,  $teeshirtAVariation->getOptionForAttribute($sizeAttr->ID)->ID);
        $this->assertEquals($teeshirtARedOpt->ID, $teeshirtAVariation->getOptionForAttribute($colorAttr->ID)->ID);
        $this->assertEquals($teeshirtACottonOpt->ID, $teeshirtAVariation->getOptionForAttribute($materialAttr->ID)->ID);

        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $firstItem = $items->First();
        $itemOptions = $firstItem->ItemOptions();
        $variation = $itemOptions->First()->Object();

        $this->assertEquals(1, $itemOptions->Count());
        $this->assertEquals($teeshirtAVariation->ID, $variation->ID);
        $this->assertEquals($teeshirtAVariation->Version, $variation->Version);
        $this->assertEquals($teeshirtAVariation->Status, $variation->Status);
        $this->assertEquals($teeshirtAVariation->ProductID, $variation->ProductID);
        $this->assertEquals('Variation', $variation->ClassName);
    }
    
    /**
     * Add disabled product variation to cart should not work
     */
    public function testAddDisabledProductVariationToCart()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $teeshirtAVariation->Status = 'Disabled';
        $teeshirtAVariation->write();
        $this->logOut();

        $this->get(Director::makeRelative($teeshirtA->Link()));

        $this->assertEquals('Disabled', $teeshirtAVariation->Status);
        $this->assertFalse($teeshirtAVariation->isEnabled());
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      
        $this->assertEquals($teeshirtASmallOpt->ID,  $teeshirtAVariation->getOptionForAttribute($sizeAttr->ID)->ID);
        $this->assertEquals($teeshirtARedOpt->ID, $teeshirtAVariation->getOptionForAttribute($colorAttr->ID)->ID);
        $this->assertEquals($teeshirtACottonOpt->ID, $teeshirtAVariation->getOptionForAttribute($materialAttr->ID)->ID);
      
        $data = $this->getFormData('AddToCartForm_AddToCartForm');
        $data['Quantity'] = 1;
        $data["Options[{$sizeAttr->ID}]"] = $teeshirtASmallOpt->ID; //Small
    $data["Options[{$colorAttr->ID}]"] = $teeshirtARedOpt->ID; //Red
    $data["Options[{$materialAttr->ID}]"] = $teeshirtACottonOpt->ID; //Cotton

    $this->post(
      Director::absoluteURL($teeshirtA->Link() . '/AddToCartForm/'),
      $data
    );
      
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();

        $this->assertEquals(0, $items->Count());
    }
    
    /**
     * Add invalid product variation to cart should not work
     */
    public function testAddInvalidProductVariationToCart()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $this->logOut();

        $this->get(Director::makeRelative($teeshirtA->Link()));

      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtAMediumOpt = $this->objFromFixture('Option', 'optMediumTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      
        $this->assertEquals($teeshirtASmallOpt->ID,  $teeshirtAVariation->getOptionForAttribute($sizeAttr->ID)->ID);
        $this->assertFalse($teeshirtAMediumOpt->ID == $teeshirtAVariation->getOptionForAttribute($sizeAttr->ID)->ID);
        $this->assertEquals($teeshirtARedOpt->ID, $teeshirtAVariation->getOptionForAttribute($colorAttr->ID)->ID);
        $this->assertEquals($teeshirtACottonOpt->ID, $teeshirtAVariation->getOptionForAttribute($materialAttr->ID)->ID);
      
      //Submit with incorrect variation values, for Medium, Red, Cotton
      /*
      $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtAMediumOpt->ID,  //Medium
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));
      */
      $data = $this->getFormData('AddToCartForm_AddToCartForm');
        $data['Quantity'] = 1;
        $data["Options[{$sizeAttr->ID}]"] = $teeshirtAMediumOpt->ID; //Medium
    $data["Options[{$colorAttr->ID}]"] = $teeshirtARedOpt->ID; //Red
    $data["Options[{$materialAttr->ID}]"] = $teeshirtACottonOpt->ID; //Cotton

    $this->post(
      Director::absoluteURL($teeshirtA->Link() . '/AddToCartForm/'),
      $data
    );

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();

        $this->assertEquals(0, $items->Count());
    }
    
    /**
     * Add product variations and check quantities
     */
  public function testAddProductVariationQuantity()
  {
      $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
      $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');

      $this->logInAs('admin');
      $teeshirtA->doPublish();
      $this->logOut();

      $this->get(Director::makeRelative($teeshirtA->Link()));
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
      $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
      $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
      $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
      $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
      $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      
      $this->assertEquals($teeshirtASmallOpt->ID,  $teeshirtAVariation->getOptionForAttribute($sizeAttr->ID)->ID);
      $this->assertEquals($teeshirtARedOpt->ID, $teeshirtAVariation->getOptionForAttribute($colorAttr->ID)->ID);
      $this->assertEquals($teeshirtACottonOpt->ID, $teeshirtAVariation->getOptionForAttribute($materialAttr->ID)->ID);
      
      $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));

      $order = CartControllerExtension::get_current_order();
      $items = $order->Items();
      $firstItem = $items->First();

      $this->assertEquals(1, $items->Count());
      $this->assertEquals(1, $firstItem->Quantity);
      
      $this->get(Director::makeRelative($teeshirtA->Link()));
      
      $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 2,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));

      $order = CartControllerExtension::get_current_order();
      $items = $order->Items();
      $firstItem = $items->First();

      $this->assertEquals(1, $items->Count());
      $this->assertEquals(3, $firstItem->Quantity);
  }
    
    /**
     * Add different product variations for the same product
     */
    public function testAddProductVariations()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $this->logOut();

        $this->get(Director::makeRelative($teeshirtA->Link()));
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
        $teeshirtAPolyesterOpt = $this->objFromFixture('Option', 'optPolyesterTeeshirt');

        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $firstItem = $items->First();

        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $firstItem->Quantity);
      
      
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $this->logOut();

        $this->get(Director::makeRelative($teeshirtA->Link()));
      
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtAPolyesterOpt->ID, //Polyester
      ));

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();

        $this->assertEquals(2, $items->Count());
        $this->assertEquals(1, $items->First()->Quantity);
        $this->assertEquals(1, $items->Last()->Quantity);
    }
    
    /**
     * Add product variations and check version correct
     */
    public function testAddVariationWithVersion()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $teeshirtAVariation->Amount->setAmount(1.00);
        $teeshirtAVariation->write();
        $this->logOut();

        $firstVersion = $teeshirtAVariation->Version;

        $this->get(Director::makeRelative($teeshirtA->Link()));
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      
        $this->assertEquals($teeshirtASmallOpt->ID,  $teeshirtAVariation->getOptionForAttribute($sizeAttr->ID)->ID);
        $this->assertEquals($teeshirtARedOpt->ID, $teeshirtAVariation->getOptionForAttribute($colorAttr->ID)->ID);
        $this->assertEquals($teeshirtACottonOpt->ID, $teeshirtAVariation->getOptionForAttribute($materialAttr->ID)->ID);
      
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $firstItem = $items->First();
        $itemOptions = $firstItem->ItemOptions();
        $firstItemOption = $itemOptions->First();
        $variation = $firstItemOption->Object();

        $this->assertEquals(1, $items->Count());
        $this->assertEquals(1, $firstItem->Quantity);
        $this->assertEquals($firstVersion, $firstItemOption->ObjectVersion);

        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $teeshirtAVariation->Amount->setAmount(0.00);
        $teeshirtAVariation->write();
        $this->logOut();
      
        $secondVersion = $teeshirtAVariation->Version;
        $this->assertTrue($secondVersion > $firstVersion);
      
        $this->get(Director::makeRelative($teeshirtA->Link()));
      
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtACottonOpt->ID, //Cotton
      ));

        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
        $lastItemOption = $items->Last()->ItemOptions()->Last();

        $this->assertEquals(2, $items->Count());
        $this->assertEquals(1, $items->Last()->Quantity);
        $this->assertEquals($secondVersion, $lastItemOption->ObjectVersion);
    }
    
    /**
     * Add product variation with different price and check order total
     */
    public function testAddVariationWithPriceChanged()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedPolyester');
      
        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $this->logOut();

        $expectedAmount = $teeshirtA->Amount->getAmount() + $teeshirtAVariation->Amount->getAmount();

        $this->get(Director::makeRelative($teeshirtA->Link()));
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
        $teeshirtAPolyesterOpt = $this->objFromFixture('Option', 'optPolyesterTeeshirt');
      
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtAPolyesterOpt->ID, //Polyester
      ));

        $order = CartControllerExtension::get_current_order();

        $this->assertEquals($expectedAmount, $order->Total->getAmount());
    }
    
    /**
     * Get product attribute and test options associated with it
     */
    public function testProductAttributeOptions()
    {
        $attributeSize = $this->objFromFixture('Attribute', 'attrSize');
        $options = $attributeSize->Options();
      
      //Remove all the attribute options that have ProductID > 0, these are not default options
      foreach ($options as $option) {
          if ($option->ProductID != 0) {
              $options->remove($option);
          }
      }
      
        $this->assertInstanceOf('ComponentSet', $options);
        $this->assertEquals(3, $options->Count());
      
        $optionSmall = $options->find('Title', 'Small');
        $this->assertInstanceOf('Option', $optionSmall);
    }
    
    /**
     * Get variation options and test that they are correct
     */
    public function testProductVariationOptions()
    {
        $smallRedCotton = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');
      
        $this->assertEquals('Enabled', $smallRedCotton->Status, 'Variation should be status Enabled by default.');
      
      //Ensure correct options
      $options = $smallRedCotton->Options();
        $this->assertInstanceOf('ComponentSet', $options);
        $this->assertEquals(3, $options->Count());
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
      
        $this->assertEquals(array(
        $teeshirtACottonOpt->ID => 'Cotton',
        $teeshirtARedOpt->ID => 'Red',
        $teeshirtASmallOpt->ID  => 'Small'
      ), $options->map('ID', 'Title'));
    }

    /**
     * Test saving duplicate product variations
     */
    public function testSaveDuplicateProductVariation()
    {
        $brokenSmallRed = $this->objFromFixture('Variation', 'brokenSmallRed');
        $brokenSmallRedDuplicate = $this->objFromFixture('Variation', 'brokenSmallRedDuplicate');
      
        $firstOptions = $brokenSmallRed->Options()->map();
        $secondOptions = $brokenSmallRedDuplicate->Options()->map();

        $this->assertEquals($firstOptions, $secondOptions);
      
      //Hacky way to add attribute options to the record for Variation::isDuplicate()
      foreach ($brokenSmallRedDuplicate->Options() as $option) {
          $brokenSmallRedDuplicate->setField('Options['.$option->AttributeID.']', $option->ID);
      }

        $e = null;
        try {
            $brokenSmallRedDuplicate->write();
        } catch (ValidationException $e) {
            $message = $e->getMessage();
        }
        $this->assertInstanceOf('ValidationException', $e);
    }
    
    /**
     * Add product and variation with quantity to cart and check total and subtotal
     */
    public function testCartTotals()
    {
        $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
        $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedPolyester');
      
        $this->logInAs('admin');
        $teeshirtA->doPublish();
        $this->logOut();

        $quantity = 2;
        $expectedAmount = ($teeshirtA->Amount->getAmount() + $teeshirtAVariation->Amount->getAmount()) * $quantity;

        $this->get(Director::makeRelative($teeshirtA->Link()));
      
      //Add variation to the cart
      $sizeAttr = $this->objFromFixture('Attribute', 'attrSize');
        $colorAttr = $this->objFromFixture('Attribute', 'attrColor');
        $materialAttr = $this->objFromFixture('Attribute', 'attrMaterial');
      
        $teeshirtASmallOpt = $this->objFromFixture('Option', 'optSmallTeeshirt');
        $teeshirtARedOpt = $this->objFromFixture('Option', 'optRedTeeshirt');
        $teeshirtACottonOpt = $this->objFromFixture('Option', 'optCottonTeeshirt');
        $teeshirtAPolyesterOpt = $this->objFromFixture('Option', 'optPolyesterTeeshirt');
      
        $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => $quantity,
        "Options[{$sizeAttr->ID}]" => $teeshirtASmallOpt->ID,  //Small
        "Options[{$colorAttr->ID}]" => $teeshirtARedOpt->ID, //Red
        "Options[{$materialAttr->ID}]" => $teeshirtAPolyesterOpt->ID, //Polyester
      ));

        $order = CartControllerExtension::get_current_order();

        $this->assertEquals($expectedAmount, $order->Total->getAmount());
        $this->assertEquals($expectedAmount, $order->SubTotal->getAmount());
    }
    
    /**
     * Test saving variation without all options set
     * Disabled validation for product variations because preventing disabling a variation
     * 
     * @deprecated
     */
    public function testSaveInvalidProductVariation()
    {
        return;
      
      //This variation only has 1 option instead of 2
      $brokenProductVariation = $this->objFromFixture('Variation', 'brokenMedium');
        $options = $brokenProductVariation->Options();
        $this->assertInstanceOf('ComponentSet', $options);
        $this->assertEquals(1, $options->Count());
      
        $e = null;
        try {
            $brokenProductVariation->write();
        } catch (ValidationException $e) {
            $message = $e->getMessage();
        }
        $this->assertInstanceOf('ValidationException', $e);
    }

  /**
   * Have to use draft site for following test testAddNonPublishedProductToCart
   */
  public function testSetDraftTrue()
  {
      self::$use_draft_site = true;
  }
    
    /**
     * Adding non published product to a cart should fail
     */
    public function testAddNonPublishedProductToCart()
    {
        $productA = $this->objFromFixture('Product', 'productA');
    
        $this->assertEquals(false, $productA->isPublished());
    
        $productALink = $productA->Link();
        $this->get(Director::makeRelative($productALink));

        $message = null;
        try {
            $this->submitForm('AddToCartForm_AddToCartForm', null, array(
        'Quantity' => 1
      ));
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
      
        $this->assertStringEndsWith('Object not written.', $message);
    
        $order = CartControllerExtension::get_current_order();
        $items = $order->Items();
      
        $this->assertEquals(0, $items->Count());
    }
}
