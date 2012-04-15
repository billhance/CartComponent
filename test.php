<?php

require_once(__DIR__ . '/Cart/Cart.php');
require_once(__DIR__ . '/Cart/Item.php');
require_once(__DIR__ . '/Cart/Discount.php');
require_once(__DIR__ . '/Cart/Shipment.php');

$itemA = new Item(1234, '12.50');
$itemB = new Item(4312, '99.99');

$shipmentA = new Shipment(3, '11.99');
$shipmentA->setIsDiscountable(false)
	      ->setIsTaxable(true);

$shipmentB = new Shipment(4, '10.00');
$shipmentB->setIsTaxable(false);

$discountA = new Discount(2, '10.00');
$discountB = new Discount(3, '0.75');
$discountB->setAs(Discount::$asPercent)
		  ->setTo(Discount::$toShipping);

$cartA = new Cart();
$totalsA = $cartA->getTotals();

$cartB = new Cart();
$cartB->addItem($itemA);
$cartB->addItem($itemB);

$cartB->addDiscount($discountA);
$cartB->addShipment($shipmentA);
$cartB->addDiscount($discountB);
$cartB->addShipment($shipmentB);

$cartB->setIncludeTax(true)
      ->setTaxRate('0.08');

echo "\n{$cartB}\n";
echo print_r($cartB->getTotals(), 1);

$cartC = new Cart();
$cartC->importJson($cartB->toJson());

echo "\n{$cartC}\n";
echo print_r($cartC->getTotals(), 1);

$cartC->setDiscountTaxableLast(false);
echo "\n{$cartC}\n";
echo print_r($cartC->getTotals(), 1);



