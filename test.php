<?php

require_once(__DIR__ . '/Cart/Cart.php');
require_once(__DIR__ . '/Cart/Item.php');
require_once(__DIR__ . '/Cart/Discount.php');
require_once(__DIR__ . '/Cart/Shipment.php');

$itemA = new Item(1234, '12.50');
$itemB = new Item(4312, '99.99');
$itemB->setIsTaxable(true);

$shipmentA = new Shipment(3, '11.99');
$shipmentA->setIsDiscountable(false)
	      ->setIsTaxable(true)
	      ->addItem($itemA);

$shipmentB = new Shipment(4, '10.00');
$shipmentB->setIsTaxable(false)
		  ->addItem($itemB);

$discountA = new Discount(2, '10.00');

$discountB = new Discount(3, '0.75');
$discountB->setAs(Discount::$asPercent)
		  ->setTo(Discount::$toShipments);

$discountC = new Discount(4, '0.50');
$discountC->setAs(Discount::$asPercent)
		  ->setTo(Discount::$toSpecified)
		  ->addItem($itemA)
		  ->addItem($itemB);

$cart = new Cart();
$cart->addItem($itemA);
$cart->addItem($itemB);
$cart->addDiscount($discountA);
$cart->addShipment($shipmentA);
$cart->addDiscount($discountB);
$cart->addShipment($shipmentB);
$cart->addDiscount($discountC);

$cart->setIncludeTax(true)
     ->setTaxRate('0.08');

echo "\n{$cart}\n";
echo print_r($cart->getTotals(), 1);

$cart2 = new Cart();
$cart2->importJson($cart->toJson());

echo "\n{$cart2}\n";
echo print_r($cart2->getTotals(), 1);

$cart2->setDiscountTaxableLast(false);
$cart2->setPrecision(3);
$cart2->removeDiscount($discountC);
echo "\n{$cart2}\n";
echo print_r($cart2->getTotals(), 1);

