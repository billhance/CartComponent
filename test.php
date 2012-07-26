<?php

require_once(__DIR__ . '/Cart/Customer.php');
require_once(__DIR__ . '/Cart/Calculator.php');
require_once(__DIR__ . '/Cart/Cart.php');
require_once(__DIR__ . '/Cart/Item.php');
require_once(__DIR__ . '/Cart/Shipment.php');
require_once(__DIR__ . '/Cart/Discount.php');
require_once(__DIR__ . '/Cart/DiscountCondition.php');
require_once(__DIR__ . '/Cart/DiscountConditionCompare.php');
require_once(__DIR__ . '/Cart/Factory.php');


//create toothbrush
$itemA = new Item();
$itemA->setId(1)
      ->setName('ToothBrush')
      ->setSku('toothbrush-1')
      ->setPrice('1.99')
      ->setQty(3)
      ->setIsTaxable(true)
      ->setIsDiscountable(true)
      ;

//create toothpaste
$itemB = new Item();
$itemB->setId(2)
      ->setName('ToothPaste')
      ->setSku('toothpaste-2')
      ->setPrice('2.99')
      ->setQty(1)
      ->setIsTaxable(true)
      ->setIsDiscountable(true)
      ;

$itemC = new Item();
$itemC->setId(3)
      ->setName('Anniversary Present')
      ->setSku('present-1')
      ->setPrice('99.99')
      ->setQty(1)
      ->setIsTaxable(true)
      ->setIsDiscountable(true)
      ;

//create a shipment
$shipmentA = new Shipment();
$shipmentA->setId(1)
          ->setVendor('ups')
          ->setMethod('ground')
          ->setPrice('6.95')
          ->setIsDiscountable(true)
          ->setIsTaxable(true)
          ->addItem($itemA)
          ->addItem($itemB)
          ;

$cart = new Cart();
$cart->addItem($itemA)
     ->addItem($itemB)
     ->addItem($itemC)
     ->addShipment($shipmentA)
     ->setIncludeTax(true)
     ->setTaxRate('0.07025')
     ->setId(3)
     ;

//set up a single discount condition, for the shipping method
$condition1 = new DiscountCondition();
$condition1->setId(1)
           ->setName('Shipping: code = ups_ground')
           ->setCompareType(DiscountCondition::$compareEquals)
           ->setCompareValue('ups_ground')
           ->setSourceEntityType('shipments')
           ->setSourceEntityField('code')
           ;

//discount conditions are wrapped by a condition compare object
//compare objects are intended for creating trees of conditionals
$compare1 = new DiscountConditionCompare();
$compare1->setId(1)
         ->setOp('or') // doing a linear 'or' (not left-right) since we only have 1 condition
         ->setSourceEntityType('shipments')
         ->addCondition($condition1)
         ;

//set up a single discount condition, for the item sku
$condition2 = new DiscountCondition();
$condition2->setId(2)
           ->setName('Item: sku = toothpaste-2')
           ->setSourceEntityType('items')
           ->setSourceEntityField('sku')
           ->setCompareType(DiscountCondition::$compareEquals)
           ->setCompareValue('toothpaste-2')
           ;

$condition3 = new DiscountCondition();
$condition3->setId(3)
           ->setName('Item: qty >= 2')
           ->setSourceEntityType('items')
           ->setSourceEntityField('qty')
           ->setCompareType(DiscountCondition::$compareGreaterThanEquals)
           ->setCompareValue('2')
           ;

//set up a single discount condition, for the item sku
$condition4 = new DiscountCondition();
$condition4->setId(4)
           ->setName('Item: sku = toothbrush-1')
           ->setSourceEntityType('items')
           ->setSourceEntityField('sku')
           ->setCompareType(DiscountCondition::$compareEquals)
           ->setCompareValue('toothbrush-1')
           ;

$compare2 = new DiscountConditionCompare();
$compare2->setId(2)
         ->setOp('or') // doing a linear 'or' (not left-right) since we only have 1 condition
         ->setSourceEntityType('items')
         ->addCondition($condition2)
         ;

$compare3 = new DiscountConditionCompare();
$compare3Key = DiscountConditionCompare::getKey(3);
$compare3->setId(3)
         ->setOp('and') // doing a linear 'and'
         ->setSourceEntityType('items')
         ->addCondition($condition3)
         ->addCondition($condition4)
         ;

//create the discount, but don't add the discount unless the conditions are met
//in this example, there is only a target criteria; no pre-requisite criteria
$discountA = new Discount();
$discountA->setId(1)
          ->setName('Free UPS Ground')
          ->setValue('1.00')
          ->setAs(Discount::$asPercent)
          ->setIsPreTax(true)
          ->setTo(Discount::$toSpecified) //not _all_ shipments
          ->setTargetConditionCompare($compare1) //only target criteria, no criteria before
          ;

// Buy 2 ToothBrush, Get 1 ToothPaste free
$discountB = new Discount();
$discountB->setId(2)
          ->setName('Buy 2 ToothBrush, Get 1 ToothPaste free')
          ->setTo(Discount::$toSpecified)
          ->setValue('1.00')
          ->setAs(Discount::$asPercent)
          ->setIsPreTax(true)
          ->setCriteriaConditionCompare($compare3)
          ->setTargetConditionCompare($compare2)
          ;

//apply the automatic discount, if it validates
//(this example is set up to validate)

if ($compare1->isValid($shipmentA)) {
    $discountA->addShipment($shipmentA);
    $cart->addDiscount($discountA);
}

if ($compare3->isValid($itemA) && $compare2->isValid($itemB)) {
    $discountB->addItem($itemB);
    $cart->addDiscount($discountB);
}

//echo print_r($cart->toArray(), 1);
echo "\n{$cart}\n";
echo print_r($cart->getTotals(), 1);
echo print_r($cart->getDiscountedTotals(), 1);

$cart2 = new Cart();
$cart2->importJson($cart->toJson());

//echo print_r($cart2->toArray(), 1);
echo "\n{$cart2}\n";
echo print_r($cart2->getTotals(), 1);
echo print_r($cart2->getDiscountedTotals(), 1);
