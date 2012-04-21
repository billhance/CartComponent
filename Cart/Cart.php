<?php

/**
 * Basic Cart object
 * This object is intended to represent a common shopping cart.
 * A developer can load their own product, shipment, and discount objects, as needed, based on simple data contained here.
 * The state of the cart can be imported/exported as json, which allows for easy handling and storage of shopping carts.
 *
 * (c) Jesse Hanson [jessehanson.com]
 */

class Cart 
{

    /**
     * @var array products
     */
    protected $_items; // items[prefix-productId] = Item
    
    /**
     * @var array discounts
     */
    protected $_discounts; // discounts[prefix-discountId] = Discount
    
    /**
     * @var array shipments
     */
    protected $_shipments; // shipments[company-method] = Shipment

    /**
     * Flag whether including tax in total 
     */
    protected $_includeTax; // enable/disable per state

    /**
     * Tax rate eg 0.08
     */
    protected $_taxRate;

    /**
     * Decimal point precision
     */
    protected $_precision;

    /**
     * Flag whether to discount taxable total first or last,
     *  only effective if pre-tax discounts "overlap" and reduce
     *  the taxable sub-total
     *
     * @var boolean
     */
    protected $_discountTaxableLast;

    //define array keys
    
    static $items = 'items'; //used as a prefix also, to keep keys formatted as strings
    
    static $discounts = 'discounts'; //includes gift certificates and coupons
    
    static $shipments = 'shipments';

    static $includeTax = 'include_tax';
    
    static $tax = 'tax';

    static $taxRate = 'tax_rate';

    static $total = 'total';

    static $discountTaxableLast = 'discount_taxable_last';
    
    public function __construct($precision = 2, $includeTax = false, $taxRate = 0, $discountTaxableLast = true)
    {
        $this->_items = array();
        $this->_discounts = array();
        $this->_shipments = array();
        $this->_precision = $precision;
        $this->_includeTax = $includeTax;
        $this->_taxRate = $taxRate;
        $this->_discountTaxableLast = $discountTaxableLast;
    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Export object data to json string.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Serialize object to array
     */
    public function toArray()
    {        
        $items = array();
        if (count($this->_items) > 0) {
            foreach($this->_items as $productKey => $product) {
                $items[$productKey] = $product->toArray();
            }
        }
        
        $discounts = array();
        if (count($this->_discounts) > 0) {
            foreach($this->_discounts as $discountKey => $discount) {
                $discounts[$discountKey] = $discount->toArray();
            }
        }

        $shipments = array();
        if (count($this->_shipments) > 0) {
            foreach($this->_shipments as $shipmentKey => $shipment) {
                $shipments[$shipmentKey] = $shipment->toArray();
            }
        }
        
        return array(
            self::$items      => $items,
            self::$discounts  => $discounts,
            self::$shipments  => $shipments,
            self::$taxRate    => $this->getTaxRate(),
            self::$includeTax => $this->getIncludeTax(),
            self::$discountTaxableLast => $this->getDiscountTaxableLast(),
        );
    }
    
    /**
     * Import object data from json string.
     *
     */
    public function importJson($json, $reset = true)
    {
        // strict parameter
        if (!is_string($json)) {
            return false;
        }
        
        // reset cart
        if ($reset) {
            $this->reset();
        }
        
        $cart = @ (array) json_decode($json); // just gimme an array
        
        if (isset($cart[self::$items]) && count($cart[self::$items]) > 0) {
            $items = $cart[self::$items];
            foreach($items as $productKey => $item) {
                $itemJson = json_encode($item);
                $item = new Item();
                $item->importJson($itemJson);
                $this->addItem($item);
            }
        }
        
        if (isset($cart[self::$discounts]) && count($cart[self::$discounts]) > 0) {
            $discounts = $cart[self::$discounts];
            foreach($discounts as $discountKey => $discount) {
                $discountJson = json_encode($discount);
                $discount = new Discount();
                $discount->importJson($discountJson);
                $this->addDiscount($discount);
            }
        }

        if (isset($cart[self::$shipments]) && count($cart[self::$shipments]) > 0) {
            $shipments = $cart[self::$shipments];
            foreach($shipments as $shipmentKey => $shipment) {
                $shipmentJson = json_encode($shipment);
                $shipment = new Shipment();
                $shipment->importJson($shipmentJson);
                $this->addShipment($shipment);
            }
        }

        if (isset($cart[self::$includeTax])) {
            $includeTax = $cart[self::$includeTax];
            $this->setIncludeTax($includeTax);
        }

        if (isset($cart[self::$taxRate])) {
            $taxRate = $cart[self::$taxRate];
            $this->setTaxRate($taxRate);
        }

        if (isset($cart[self::$discountTaxableLast])) {
            $discountTaxableLast = $cart[self::$discountTaxableLast];
            $this->setDiscountTaxableLast($discountTaxableLast);
        }
        
        return $this;
    }
    
    /**
     * Empty arrays
     *
     */
    public function reset()
    {
        $this->_items = array();
        $this->_discounts = array();
        $this->_shipments = array();
        $this->_includeTax = false;
        $this->_taxRate = 0.0;
        $this->_precision = 2;
        $this->_discountTaxableLast = true;

        return $this;
    }

    /**
     * Decorator for float values
     */
    public function currency($value)
    {
        return number_format($value, $this->_precision);
    }

    /**
     * Set decimal point precision
     */
    public function setPrecision($precision)
    {
        $this->_precision = (int) $precision;
        return $this;
    }

    /**
     * Get decimal point precision
     */
    public function getPrecision()
    {
        return $this->_precision;
    }

    /**
     * Get all totals
     */
    public function getTotals()
    {
        return array(
            self::$items     => $this->getItemTotal(),
            self::$shipments => $this->getShipmentTotal(),
            self::$discounts => $this->getDiscountTotal(),
            self::$tax       => $this->getTaxTotal(),
            self::$total     => $this->getTotal(),
        );
    }

    /**
     * Get Total
     *
     * This method is meant to be usable in a 'universal' way.
     * I am not an accountant, so I'm not sure if this covers the most common calculations.
     * Assumptions:
     *  1. Shipments will never be added or calculated after tax is calculated. 
     *  2. Products or Shipments may be taxable. 
     *  3. Discounts may apply to either shipments or items; before or after tax
     *  4. Percentage discounts are not compounded . This would require a relatively chaotic "weighting system"; add too much complexity
     */
    public function getTotal()
    {
        return $this->currency($this->getItemTotal() + 
                                $this->getShipmentTotal() + 
                                $this->getTaxTotal() - 
                                $this->getDiscountTotal());
    }

    /**
     * Get Item Total
     */
    public function getItemTotal()
    {
        //always zero if empty
        if (!count($this->getItems())) {
            return $this->currency(0);
        }
        
        $itemTotal = 0;
        foreach($this->getItems() as $productKey => $item) {
            $price = $this->currency($item->getPrice());
            $qty = $item->getQty();
            $itemTotal += $this->currency($price * $qty);
        }

        return $this->currency($itemTotal);
    }

    /**
     * Get Shipping Total
     */
    public function getShipmentTotal()
    {
        if (!count($this->getShipments())) {
            return $this->currency(0);
        }

        $total = 0;
        foreach($this->getShipments() as $shipmentKey => $shipment) {
            $total += $this->currency($shipment->getPrice());
        }

        return $this->currency($total);
    }

    /**
     * Get Tax Total
     */
    public function getTaxTotal()
    {
        if (!$this->getIncludeTax()) {
            return $this->currency(0);
        }

        $discountedItemTotal = 0;
        $discountedShipmentTotal = 0;

        if ($this->getDiscountTaxableLast()) {
            //overlap = taxable + discountable - itemTotal;
            //taxable -= overlap;

            if (($this->getTaxableItemTotal() + $this->getPreTaxItemDiscount()) > $this->getItemTotal()) {
                $itemOverlapAmount = $this->currency($this->getTaxableItemTotal() + $this->getPreTaxItemDiscount() - $this->getItemTotal());
                $discountedItemTotal = $this->currency($this->getTaxableItemTotal() - $itemOverlapAmount);
            } else {
                $discountedItemTotal = $this->getTaxableItemTotal();
            }

            if (($this->getTaxableShipmentTotal() + $this->getPreTaxShipmentDiscount()) > $this->getShipmentTotal()) {
                $shipmentOverlapAmount = $this->currency($this->getTaxableShipmentTotal() + $this->getPreTaxShipmentDiscount() - $this->getShipmentTotal());
                $discountedShipmentTotal = $this->currency($this->getTaxableShipmentTotal() - $shipmentOverlapAmount);
            } else {
                $discountedShipmentTotal = $this->getTaxableShipmentTotal();
            }

        } else {

            $discountedItemTotal = $this->getTaxableItemTotal() - $this->getPreTaxItemDiscount();
            if ($discountedItemTotal <= 0) {
                $discountedItemTotal = $this->currency(0);
            }

            $discountedShipmentTotal = $this->getTaxableShipmentTotal() - $this->getPreTaxShipmentDiscount();
            if ($discountedShipmentTotal <= 0) {
                $discountedShipmentTotal = $this->currency(0);
            }
        }

        $taxableTotal = $this->currency($discountedItemTotal + $discountedShipmentTotal);

        $totalTax = $this->currency($this->getTaxRate() * $taxableTotal);

        return $this->currency($totalTax);
    }

    /**
     * Get Discount Total
     */
    public function getDiscountTotal()
    {
        return $this->currency($this->getPreTaxDiscount() + $this->getPostTaxDiscount());
    }

    /**
     * Accessor
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * Retrieve Item
     */
    public function getItem($key)
    {
        return isset($this->_items[$key]) ? $this->_items[$key] : false;
    }

    /**
     * Set Item to Cart 
     *
     */
    public function addItem(Item $item)
    {
        $key = Item::getKey($item->getId());
        $this->_items[$key] = $item;
        return $this;
    }

    /**
     * Remove item from array
     *
     */
    public function removeItem($key)
    {
        if ($key instanceof Item) {
            $key = Item::getKey($key->getId());
        }

        if (isset($this->_items[$key])) {
            unset($this->_items[$key]);
        }

        return $this;
    }

    /**
     * Assert Item exists
     *
     * @param string itemKey
     * @return boolean hasItem
     */
    public function hasItem($key)
    {
        return isset($this->_items[$key]);
    }

    /**
     * Get keys of shipments that have been specified in discounts
     * This helps with separating specific discounts, so specified items
     * are not discounted by non-specific discounts
     *
     * @return array
     */
    public function getSpecifiedDiscountItemKeys()
    {
        $keys = array();
        if (count($this->getDiscounts()) > 0) {
            foreach($this->getDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $shipments = $discount->getItems();
                if (count($discount->getItems()) > 0) {
                    foreach($discount->getItems() as $itemKey => $qty) {
                        $keys[$itemKey] = $itemKey;
                    }
                }
            }
        }
        return $keys;
    }

    /**
     * Accessor
     */
    public function getDiscounts()
    {
        return $this->_discounts;
    }

    /**
     * Retrieve Discount
     */
    public function getDiscount($key)
    {
        return isset($this->_discounts[$key]) ? $this->_discounts[$key] : false;
    }

    /**
     * Add Discount to cart
     *
     */
    public function addDiscount(Discount $discount)
    {
        $key = Discount::getKey($discount->getId());
        $this->_discounts[$key] = $discount;
        return $this;
    }
    
    /**
     * Remove Discount from cart
     *
     */
    public function removeDiscount($key)
    {
        if ($key instanceof Discount) {
            $key = Discount::getKey($key->getId());
        }

        if (isset($this->_discounts[$key])) {
            unset($this->_discounts[$key]);
        }

        return $this;
    }

    /**
     * Assert Discount exists
     *
     * @param string discountKey
     * @return boolean hasDiscount
     */
    public function hasDiscount($key)
    {
        return isset($this->_discounts[$key]);
    }

    /**
     * Accessor
     */
    public function getShipments()
    {
        return $this->_shipments;
    }

    /**
     * Retrieve Shipment
     */
    public function getShipment($key)
    {
        return isset($this->_shipments[$key]) ? $this->_shipments[$key] : false;
    }

    /**
     * Add Shipment to cart
     *
     */
    public function addShipment(Shipment $shipment)
    {
        $key = Shipment::getKey($shipment->getId());
        $this->_shipments[$key] = $shipment;
        return $this;
    }

    /**
     * Remove Shipment from cart
     *
     */
    public function removeShipment($key)
    {
        if ($key instanceof Shipment) {
            $key = Shipment::getKey($key->getId());
        }

        if (isset($this->_shipments[$key])) {
            unset($this->_shipments[$key]);
        }

        return $this;
    }

    /**
     * Assert Shipment exists
     *
     * @param string shipmentKey
     * @return boolean hasShipment
     */
    public function hasShipment($key)
    {
        return isset($this->_shipments[$key]);
    }

    /**
     * Get keys of shipments that have been specified in discounts
     * This helps with separating specific discounts, so specified items
     * are not discounted by non-specific discounts
     *
     * @return array
     */
    public function getSpecifiedDiscountShipmentKeys()
    {
        $keys = array();
        if (count($this->getDiscounts()) > 0) {
            foreach($this->getDiscounts() as $key => $discount) {
                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $shipments = $discount->getShipments();
                if (count($discount->getShipments()) > 0) {
                    foreach($discount->getShipments() as $shipmentKey => $value) {
                        $keys[$shipmentKey] = $shipmentKey;
                    }
                }
            }
        }
        return $keys;
    }

    /**
     * Get Total Item/Shipment/Specified Discount Before Tax
     */
    public function getPreTaxDiscount()
    {
        return $this->currency($this->getPreTaxShipmentDiscount() + 
                               $this->getPreTaxItemDiscount() +
                               $this->getPreTaxSpecifiedDiscount());
    }

    /**
     * Get Total Specified Item/Shipment Discount before Tax
     */
    public function getPreTaxSpecifiedDiscount()
    {
        $total = $this->currency(0);
        if (count($this->getPreTaxDiscounts()) > 0) {
            foreach($this->getPreTaxDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $specifiedTotal = 0;

                $discountItems = $discount->getItems();
                if (count($discountItems) > 0) {
                    foreach($discountItems as $key => $qty) {
                        $item = $this->getItem($key);
                        $price = $item->getPrice();
                        $specifiedTotal += $this->currency($price * $qty);
                    }
                }

                $discountShipments = $discount->getShipments();
                if (count($discountShipments) > 0) {
                    foreach($discountShipments as $key => $value) {
                        //value and key are same
                        $shipment = $this->getShipment($key);
                        $price = $shipment->getPrice();
                        $specifiedTotal += $this->currency($price);
                    }
                }

                //calculate discount
                if ($discount->getAs() == Discount::$asFlat) {
                    $total += $this->currency($discount->getValue());    
                } else if ($discount->getAs() == Discount::$asPercent) {
                    $total += $this->currency($discount->getValue() * $specifiedTotal);
                }

            }
        }
        return $this->currency($total);
    }

    /**
     * Get Total Shipment Discount Before Tax
     */
    public function getPreTaxShipmentDiscount()
    {
        $total = $this->currency(0);
        if (count($this->getPreTaxDiscounts()) > 0) {
            foreach($this->getPreTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue());
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toShipments) {
                    continue;
                }

                // need to get total of un-specified , and discountable

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableShipmentTotal(true)); // only discountable shipments
                } else {
                    $total += $value;
                }
            }
        }

        // need to get total of un-specified , and discountable

        // cannot be more than is discountable
        if ($total > $this->getDiscountableShipmentTotal(true)) {
            $total = $this->getDiscountableShipmentTotal(true);
        }

        return $this->currency($total);
    }

    /**
     * Get Total Shipment Discount After Tax
     */
    public function getPostTaxShipmentDiscount()
    {
        $total = $this->currency(0);
        if (count($this->getPostTaxDiscounts()) > 0) {
            foreach($this->getPostTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue()); // either flat or percentage
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toShipments) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableShipmentTotal(true)); // only discountable shipments
                } else {
                    $total += $value;
                }
            }
        }

        // cannot be more than is discountable
        if ($total > $this->getDiscountableShipmentTotal(true)) {
            $total = $this->getDiscountableShipmentTotal(true);
        }

        return $this->currency($total);
    }

    /**
     * Get Item Discount Before Tax
     */
    public function getPreTaxItemDiscount()
    {
        $total = $this->currency(0);
        if (count($this->getPreTaxDiscounts()) > 0) {
            foreach($this->getPreTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue()); // either flat or percentage
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toItems) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableItemTotal(true)); // only discountable items
                } else {
                    $total += $value;
                }
            }
        }

        // cannot be more than is discountable
        if ($total > $this->getDiscountableItemTotal(true)) {
            $total = $this->getDiscountableItemTotal(true);
        }

        return $this->currency($total);
    }

    /**
     * Get Item Discount After Tax
     */
    public function getPostTaxItemDiscount()
    {
        $total = $this->currency(0);
        if (count($this->getPostTaxDiscounts()) > 0) {
            foreach($this->getPostTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue());
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toItems) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableItemTotal(true)); // only discountable items
                } else {
                    $total += $value;
                }
            }
        }

        // cannot be more than is discountable
        if ($total > $this->getDiscountableItemTotal(true)) {
            $total = $this->getDiscountableItemTotal(true);
        }
        return $this->currency($total);
    }

    /**
     * Get Total Specified Item/Shipment Discount after Tax
     */
    public function getPostTaxSpecifiedDiscount()
    {
        $total = $this->currency(0);
        if (count($this->getPostTaxDiscounts()) > 0) {
            foreach($this->getPostTaxDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $specifiedTotal = 0;

                $discountItems = $discount->getItems();
                if (count($discountItems) > 0) {
                    foreach($discountItems as $key => $qty) {
                        $item = $this->getItem($key);
                        $price = $item->getPrice();
                        $specifiedTotal += $this->currency($price * $qty);
                    }
                }

                $discountShipments = $discount->getShipments();
                if (count($discountShipments) > 0) {
                    foreach($discountShipments as $key => $value) {
                        //value and key are same
                        $shipment = $this->getShipment($key);
                        $price = $shipment->getPrice();
                        $specifiedTotal += $this->currency($price);
                    }
                }

                //calculate discount
                if ($discount->getAs() == Discount::$asFlat) {
                    $total += $this->currency($discount->getValue());    
                } else if ($discount->getAs() == Discount::$asPercent) {
                    $total += $this->currency($discount->getValue() * $specifiedTotal);
                }

            }
        }
        return $this->currency($total);
    }

    /**
     * Get Discounts before Tax
     */
    public function getPreTaxDiscounts()
    {
        $discounts = array();

        if (!count($this->getDiscounts())) {
            return $discounts;
        }

        foreach($this->getDiscounts() as $discountKey => $discount) {
            if ($discount->getIsPreTax()) {
                $discounts[$discountKey] = $discount;
            }
        }

        return $discounts;
    }

    /**
     * Get Discounts after Tax
     */
    public function getPostTaxDiscounts()
    {
        $discounts = array();
        if (!count($this->getDiscounts())) {
            return $discounts;
        }
        foreach($this->getDiscounts() as $discountKey => $discount) {
            if (!$discount->getIsPreTax()) {
                $discounts[$discountKey] = $discount;
            }
        }
        return $discounts;
    }

    /**
     * Get discount total after tax
     */
    public function getPostTaxDiscount()
    {
        return $this->currency($this->getPostTaxItemDiscount() + 
                               $this->getPostTaxShipmentDiscount() +
                               $this->getPostTaxSpecifiedDiscount());
    }

    /**
     * Get taxable shipment total
     */
    public function getTaxableShipmentTotal()
    {
        $total = 0;
        if (count($this->getShipments()) > 0) {
            foreach($this->getShipments() as $shipmentKey => $shipment) {
                if ($shipment->getIsTaxable()) {
                    $total += $this->currency($shipment->getPrice());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Get discountable shipment total
     */
    public function getDiscountableShipmentTotal($unspecifiedOnly = false)
    {
        $total = 0;
        $specifiedShipmentKeys = $this->getSpecifiedDiscountShipmentKeys();
        if (count($this->getShipments()) > 0) {
            foreach($this->getShipments() as $shipmentKey => $shipment) {
                if ($shipment->getIsDiscountable()) {
                    if ($unspecifiedOnly && isset($specifiedShipmentKeys[$shipmentKey])) {
                        continue;
                    }
                    $total += $this->currency($shipment->getPrice());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Get taxable item total
     */
    public function getTaxableItemTotal()
    {
        $total = 0;
        if (count($this->getItems()) > 0) {
            foreach($this->getItems() as $productKey => $item) {
                if ($item->getIsTaxable()) {
                    $total += $this->currency($item->getPrice());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Get total of taxable items and shipments
     */
    public function getTaxableTotal()
    {
        return $this->currency($this->getTaxableItemTotal() + $this->getTaxableShipmentTotal());
    }

    /**
     * Get discountable item total
     */
    public function getDiscountableItemTotal($unspecifiedOnly = false)
    {
        $total = 0;
        if (count($this->getItems()) > 0) {
            $specifiedItemKeys = $this->getSpecifiedDiscountItemKeys();
            foreach($this->getItems() as $productKey => $item) {
                if ($item->getIsDiscountable()) {
                    $total += $this->currency($item->getPrice());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Accessor
     */
    public function getTaxRate()
    {
        return $this->_taxRate;
    }

    /**
     * Mutator
     */
    public function setTaxRate($taxRate)
    {
        $this->_taxRate = $taxRate;
        return $this;
    }

    /**
     * Accessor
     */
    public function getIncludeTax()
    {
        return $this->_includeTax;
    }

    /**
     * Mutator
     */
    public function setIncludeTax($includeTax)
    {
        $this->_includeTax = $includeTax;
        return $this;
    }

    /**
     * Accessor
     */
    public function getDiscountTaxableLast()
    {
        return $this->_discountTaxableLast;
    }

    /**
     * Mutator
     */
    public function setDiscountTaxableLast($discountTaxableLast)
    {
        $this->_discountTaxableLast = $discountTaxableLast;
        return $this;
    }

}