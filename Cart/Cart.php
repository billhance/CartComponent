<?php

class Cart 
{
    /**
     * @var int id The database row Id 
     */
    protected $_id;

    /**
     * @var Customer
     */
    protected $_customer;

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
     *
     * @var bool
     */
    protected $_includeTax; // enable/disable per state

    /**
     * Tax rate eg 0.08
     *
     * @var float
     */
    protected $_taxRate; // per state / county

    /**
     * Decimal point precision, based on customer country
     *
     * @var int
     */
    protected $_precision; // from config

    /**
     * Decimal point precision for Calculator
     *  If a tax rate is .07025 , and a subtotal is 1000
     *   70.25 should be charged for tax, not 70.00
     *
     * @var int
     */
    protected $_calculatorPrecision = 4; //from config

    /**
     * Flag whether to discount taxable subtotal first or last.
     *  This is only effective if the sum of pre-tax discounts "overlaps" 
     *  the sum of taxable items/shipments; which reduces the taxable subtotal,
     *  and ultimately the amount of tax being paid for
     *
     * @var boolean
     */
    protected $_discountTaxableLast; //from config
    
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Retrieve calculator
     */
    public function getCalculator()
    {
        return new Calculator($this);
    }

    /**
     * Get all totals from calculator
     */
    public function getTotals()
    {
        return $this->getCalculator()->getTotals();
    }

    /**
     * Get all totals from calculator
     */
    public function getDiscountedTotals()
    {
        return $this->getCalculator()->getDiscountedTotals();
    }

    /**
     *
     */
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
        return array(
            'id'          => $this->getId(),
            'customer'    => $this->getCustomer()->toArray(),
            'items'       => $this->getItemsAsArray(),
            'discounts'   => $this->getDiscountsAsArray(),
            'shipments'   => $this->getShipmentsAsArray(),
            'tax_rate'    => $this->getTaxRate(),
            'include_tax' => $this->getIncludeTax(),
            'discount_taxable_last' => $this->getDiscountTaxableLast(),
        );
    }

    /**
     *
     */
    public function getDiscountsAsArray()
    {
        $discounts = array();
        if (count($this->_discounts) > 0) {
            foreach($this->_discounts as $discountKey => $discount) {
                $discounts[$discountKey] = $discount->toArray();
            }
        }
        return $discounts;
    }

    /**
     *
     */
    public function getItemsAsArray()
    {
        $items = array();
        if (count($this->_items) > 0) {
            foreach($this->_items as $productKey => $product) {
                $items[$productKey] = $product->toArray();
            }
        }
        return $items;
    }

    /**
     *
     */
    public function getShipmentsAsArray()
    {
        $shipments = array();
        if (count($this->_shipments) > 0) {
            foreach($this->_shipments as $shipmentKey => $shipment) {
                $shipments[$shipmentKey] = $shipment->toArray();
            }
        }
        return $shipments;
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

        if (isset($cart['id'])) {
            $this->setId($cart['id']);
        }

        if (isset($cart['customer'])) {
            $customerData = $cart['customer'];
            $customer = new Customer();
            if (is_array($customerData)) {
                $customer->importJson(json_encode($customerData));
                $this->setCustomer($customer);
            }
        }
        
        if (isset($cart['items']) && count($cart['items']) > 0) {
            $items = $cart['items'];
            foreach($items as $productKey => $item) {
                $itemJson = json_encode($item);
                $item = new Item();
                $item->importJson($itemJson);
                $this->addItem($item);
            }
        }

        if (isset($cart['shipments']) && count($cart['shipments']) > 0) {
            $shipments = $cart['shipments'];
            foreach($shipments as $shipmentKey => $shipment) {
                $tmpShipment = new Shipment();
                if ($shipment instanceof stdClass) {
                    $tmpShipment->importStdClass($shipment);
                    $this->addShipment($tmpShipment);
                } else if (is_array($shipment)) {
                    $tmpShipment->importJson(json_encode($shipment));
                    $this->addShipment($tmpShipment);
                }
            }
        }
        
        if (isset($cart['discounts']) && count($cart['discounts']) > 0) {
            $discounts = $cart['discounts'];
            foreach($discounts as $discountKey => $discount) {
                $tmpDiscount = new Discount();
                if ($discount instanceof stdClass) {
                    $tmpDiscount->importStdClass($discount);
                    $this->addDiscount($tmpDiscount);
                } else if (is_array($tmpDiscount)) {
                    $tmpDiscount->importJson(json_encode($discount));
                    $this->addDiscount($tmpDiscount);
                }
            }
        }

        if (isset($cart['include_tax'])) {
            $includeTax = $cart['include_tax'];
            $this->setIncludeTax($includeTax);
        }

        if (isset($cart['tax_rate'])) {
            $taxRate = $cart['tax_rate'];
            $this->setTaxRate($taxRate);
        }

        if (isset($cart['discount_taxable_last'])) {
            $discountTaxableLast = $cart['discount_taxable_last'];
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
        $this->_id = 0;
        $this->_customer = new Customer();
        $this->_items = array();
        $this->_discounts = array();
        $this->_shipments = array();
        $this->_includeTax = false;
        $this->_taxRate = 0.0;
        $this->_precision = 2;
        $this->_calculatorPrecision = 4;
        $this->_discountTaxableLast = true;

        return $this;
    }

    /**
     *
     */
    public function isValidCondition(DiscountCondition $condition)
    {
        /*
        Note: the Discount system isnt using this yet
        */
        switch($condition->getSourceField()) {
            case 'total':
                $condition->setSourceValue($this->getCalculator()->getTotal());
                break;
            case 'item_total':
                $condition->setSourceValue($this->getCalculator()->getItemTotal());
                break;
            case 'shipment_total':
                $condition->setSourceValue($this->getCalculator()->getShipmentTotal());
                break;
            case 'discounted_item_total':
                $condition->setSourceValue($this->getCalculator()->getDiscountedItemTotal());
                break;
            case 'discounted_shipment_total':
                $condition->setSourceValue($this->getCalculator()->getDiscountedShipmentTotal());
                break;
            default:
                //no-op
                break;
        }

        return $condition->isValid();
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
     * Set decimal point precision
     */
    public function setCalculatorPrecision($precision)
    {
        $this->_calculatorPrecision = (int) $precision;
        return $this;
    }

    /**
     * Get decimal point precision
     */
    public function getCalculatorPrecision()
    {
        return $this->_calculatorPrecision;
    }

    /**
     * Mutator
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * Accessor
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Mutator
     */
    public function setCustomer(Customer $customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    /**
     * Accessor
     */
    public function getCustomer()
    {
        return $this->_customer;
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
     * @param string|Item
     * @return this
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
     *
     */
    public function hasItems()
    {
        return (count($this->getItems()) > 0);
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
     * This helps with separating specific discounts
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
     *
     */
    public function hasDiscounts()
    {
        return (count($this->getDiscounts()) > 0);
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
     * @param Shipment
     * @return this
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
     * @param string key : associative array key
     * @return this
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
     *
     */
    public function hasShipments()
    {
        return (count($this->getShipments()) > 0);
    }

    /**
     * Get keys of shipments that have been specified in discounts
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
     *  gets all discounts regardless of type
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