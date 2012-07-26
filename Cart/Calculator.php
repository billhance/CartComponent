<?php

class Calculator {

    /**
     * @var Cart $cart
     */
    protected $_cart;
    
    
    public function __construct(Cart $cart = null)
    {
        $this->_cart = $cart;
    }

    /**
     * Setter
     *
     * @param Cart $cart
     * @return $this
     */
    public function setCart(Cart $cart)
    {
        $this->_cart = $cart;
        return $this;
    }

    /**
     * Accessor
     *
     * @return Cart
     */
    public function getCart()
    {
        return $this->_cart;
    }

    /**
     * Decorator for float values
     *
     * @param mixed
     * @return string
     */
    public function currency($value)
    {
        $value = (float) $value;
        return number_format($value, $this->_cart->getCalculatorPrecision(), '.', '');
    }

    /**
     * Decorator for float values
     *
     * @param mixed
     * @return string
     */
    public function format($value)
    {
        $value = (float) $value;
        return number_format($value, $this->_cart->getPrecision(), '.', '');
    }

    /**
     * Get all totals
     *
     * @return array
     */
    public function getTotals()
    {
        return array(
            'items'     => $this->format($this->getItemTotal()),
            'shipments' => $this->format($this->getShipmentTotal()),
            'discounts' => $this->format($this->getDiscountTotal()),
            'tax'       => $this->format($this->getTaxTotal()),
            'total'     => $this->format($this->getTotal()),
        );
    }

    /**
     * Get discounted totals
     *
     * @return array
     */
    public function getDiscountedTotals()
    {
        return array(
            'items'     => $this->format($this->getDiscountedItemTotal()),
            'shipments' => $this->format($this->getDiscountedShipmentTotal()),
            'tax'       => $this->format($this->getTaxTotal()),
            'total'     => $this->format($this->getTotal()),
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
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->currency($this->getItemTotal() + 
                               $this->getShipmentTotal() + 
                               $this->getTaxTotal() - 
                               $this->getDiscountTotal());
    }

    /**
     * Get total of Items, after Discounts
     *
     * @return string
     */
    public function getDiscountedItemTotal()
    {
        $total = $this->getItemTotal() - $this->getItemDiscountTotal();
        return $this->currency($total);
    }

    /**
     * Get Item Total, before Discounts
     *
     * @return string
     */
    public function getItemTotal()
    {
        //always zero if empty
        if (!count($this->_cart->getItems())) {
            return $this->currency(0);
        }
        
        $itemTotal = 0;
        foreach($this->_cart->getItems() as $productKey => $item) {
            $price = $this->currency($item->getPrice());
            $qty = $item->getQty();
            $itemTotal += $this->currency($price * $qty);
        }

        return $this->currency($itemTotal);
    }

    /**
     * Get total of Shipments, after Discounts
     *
     * @return string
     */
    public function getDiscountedShipmentTotal()
    {
        $total = $this->getShipmentTotal() - $this->getShipmentDiscountTotal();
        if ($total < 0) {
            $total = 0;
        }
        return $this->currency($total);
    }

    /**
     * Get total of Shipments, before Discounts
     *
     * @return string
     */
    public function getShipmentTotal()
    {
        if (!count($this->_cart->getShipments())) {
            return $this->currency(0);
        }

        $total = 0;
        foreach($this->_cart->getShipments() as $shipmentKey => $shipment) {
            $total += $this->currency($shipment->getPrice());
        }

        return $this->currency($total);
    }

    /**
     * Get total Tax, after Discounts
     *
     * @return string
     */
    public function getTaxTotal()
    {
        if (!$this->_cart->getIncludeTax()) {
            return $this->currency(0);
        }

        $discountedItemTotal = 0;
        $discountedShipmentTotal = 0;

        if ($this->_cart->getDiscountTaxableLast()) {
            
            //overlap = taxable + discountable - itemTotal;
            //taxable -= overlap;

            if (($this->getTaxableItemTotal() + $this->getPreTaxItemDiscountTotal()) > $this->getItemTotal()) {
                $itemOverlapAmount = $this->currency($this->getTaxableItemTotal() + $this->getPreTaxItemDiscountTotal() - $this->getItemTotal());
                $discountedItemTotal = $this->currency($this->getTaxableItemTotal() - $itemOverlapAmount);
            } else {
                $discountedItemTotal = $this->getTaxableItemTotal() - $this->getPreTaxItemDiscountTotal();
            }

            if (($this->getTaxableShipmentTotal() + $this->getPreTaxShipmentDiscountTotal()) > $this->getShipmentTotal()) {
                $shipmentOverlapAmount = $this->currency($this->getTaxableShipmentTotal() + $this->getPreTaxShipmentDiscountTotal() - $this->getShipmentTotal());
                $discountedShipmentTotal = $this->currency($this->getTaxableShipmentTotal() - $shipmentOverlapAmount);
            } else {
                $discountedShipmentTotal = $this->getTaxableShipmentTotal() - $this->getPreTaxShipmentDiscountTotal();
            }

        } else {

            $discountedItemTotal = $this->getTaxableItemTotal() - $this->getPreTaxItemDiscountTotal();
            if ($discountedItemTotal <= 0) {
                $discountedItemTotal = $this->currency(0);
            }

            $discountedShipmentTotal = $this->getTaxableShipmentTotal() - $this->getPreTaxShipmentDiscountTotal();
            if ($discountedShipmentTotal <= 0) {
                $discountedShipmentTotal = $this->currency(0);
            }
        }

        $taxableTotal = $this->currency($discountedItemTotal + $discountedShipmentTotal);

        $totalTax = $this->currency($this->_cart->getTaxRate() * $taxableTotal);

        return $this->currency($totalTax);
    }

    /**
     * Get Discount Total
     *  Also ensure that the sum of pre-tax discounts, and post-tax discounts
     *  is not more than is discountable, for both Items and Shipments
     *
     * @return string
     */
    public function getDiscountTotal()
    {
        $total = $this->getItemDiscountTotal() + $this->getShipmentDiscountTotal();

        if ($total > $this->getDiscountableItemTotal() + $this->getDiscountableShipmentTotal()) {
            $total = $this->getDiscountableItemTotal() + $this->getDiscountableShipmentTotal();
        }
        return $this->currency($total);
    }

    /**
     * Get total discount of Items.
     *
     * @return string
     */
    public function getItemDiscountTotal()
    {
        $total = $this->getPreTaxItemDiscountTotal() + $this->getPostTaxItemDiscountTotal();

        if ($total > $this->getDiscountableItemTotal()) {
            $total = $this->getDiscountableItemTotal();
        }

        return $this->currency($total);
    }

    /**
     * Get total discount of non-specified shipments.
     * This method can be used to ensure the sum of pre-tax 
     *  and post-tax discounts to Shipments, is not more than is discountable
     *
     * @return string
     */
    public function getShipmentDiscountTotal()
    {
        $total = $this->getPreTaxShipmentDiscountTotal() + $this->getPostTaxShipmentDiscountTotal();

        if ($total > $this->getDiscountableShipmentTotal()) {
            $total = $this->getDiscountableShipmentTotal();
        }

        return $total;
    }

    /**
     * Get total Item/Shipment discount before tax
     *
     * @return string
     */
    public function getPreTaxDiscountTotal()
    {
        $total = $this->getPreTaxShipmentDiscountTotal() + $this->getPreTaxItemDiscountTotal();

        return $this->currency($total);
    }

    /**
     * Get Discount total after tax
     *
     * @return string
     */
    public function getPostTaxDiscountTotal()
    {
        $total = $this->getPostTaxItemDiscountTotal() + $this->getPostTaxShipmentDiscountTotal();

        return $this->currency($total);
    }

    /**
     * Get total Shipment discount before tax
     *
     * @return string
     */
    public function getPreTaxShipmentDiscountTotal()
    {
        $total = $this->currency(0);

        if (count($this->_cart->getPreTaxDiscounts()) > 0) {
            foreach($this->_cart->getPreTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue());
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toShipments) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableShipmentTotal());
                } else {
                    $total += $value;
                }
            }
        }

        $total += $this->getPreTaxSpecifiedShipmentDiscountTotal();

        // cannot be more than is discountable
        if ($total > $this->getDiscountableShipmentTotal()) {
            $total = $this->getDiscountableShipmentTotal();
        }

        return $this->currency($total);
    }

    /**
     * Get Total Shipment Discount After Tax
     * 
     * @return string
     */
    public function getPostTaxShipmentDiscountTotal()
    {
        $total = $this->currency(0);

        if (count($this->_cart->getPostTaxDiscounts()) > 0) {
            foreach($this->_cart->getPostTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue()); // either flat or percentage
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toShipments) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableShipmentTotal());
                } else {
                    $total += $value;
                }
            }
        }

        $total += $this->getPostTaxSpecifiedShipmentDiscountTotal();

        // cannot be more than is discountable
        if ($total > $this->getDiscountableShipmentTotal()) {
            $total = $this->getDiscountableShipmentTotal();
        }

        return $this->currency($total);
    }

    /**
     * Get total Item discount before tax
     *
     * @return string
     */
    public function getPreTaxItemDiscountTotal()
    {
        $total = $this->currency(0);

        if (count($this->_cart->getPreTaxDiscounts()) > 0) {
            foreach($this->_cart->getPreTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue()); // either flat or percentage
                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toItems) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableItemTotal());
                } else {
                    $total += $value;
                }
            }
        }

        
        $total += $this->getPreTaxSpecifiedItemDiscountTotal();

        // cannot be more than is discountable
        if ($total > $this->getDiscountableItemTotal()) {
            $total = $this->getDiscountableItemTotal();
        }

        return $this->currency($total);
    }

    /**
     * Get total Item discount after tax
     *
     * @return string
     */
    public function getPostTaxItemDiscountTotal()
    {
        $total = $this->currency(0);

        if (count($this->_cart->getPostTaxDiscounts()) > 0) {
            foreach($this->_cart->getPostTaxDiscounts() as $discountKey => $discount) {

                $value = $this->currency($discount->getValue());

                $as = ($discount->getAs() == Discount::$asFlat) ? Discount::$asFlat : Discount::$asPercent;

                if ($discount->getTo() != Discount::$toItems) {
                    continue;
                }

                if ($as == Discount::$asPercent) {
                    $total += $this->currency($value * $this->getDiscountableItemTotal());
                } else {
                    $total += $value;
                }
            }
        }
        
        $total += $this->getPostTaxSpecifiedItemDiscountTotal();

        // cannot be more than is discountable
        if ($total > $this->getDiscountableItemTotal()) {
            $total = $this->getDiscountableItemTotal();
        }
        return $this->currency($total);
    }

    /**
     * Get Total (specified) Item discount after Tax
     *
     * @return string
     */
    public function getPostTaxSpecifiedItemDiscountTotal()
    {
        $total = $this->currency(0);
        if (count($this->_cart->getPostTaxDiscounts()) > 0) {
            foreach($this->_cart->getPostTaxDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $specifiedTotal = 0;

                $discountItems = $discount->getItems();
                if (count($discountItems) > 0) {
                    foreach($discountItems as $key => $qty) {
                        $item = $this->_cart->getItem($key);
                        $price = $item->getPrice();
                        if ($item->getIsDiscountable()) {
                            if ($item->getQty() < $qty) {
                                $qty = $item->getQty();
                            }
                            $specifiedTotal += $this->currency($price * $qty);
                        }
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
     * Get Total (specified) Shipment discount after Tax
     *
     * @return string
     */
    public function getPostTaxSpecifiedShipmentDiscountTotal()
    {
        $total = $this->currency(0);
        if (count($this->_cart->getPostTaxDiscounts()) > 0) {
            foreach($this->_cart->getPostTaxDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $specifiedTotal = 0;

                $discountShipments = $discount->getShipments();
                if (count($discountShipments) > 0) {
                    foreach($discountShipments as $key => $value) {
                        //value and key are same
                        $shipment = $this->_cart->getShipment($key);
                        $price = $shipment->getPrice();
                        if ($shipment->getIsDiscountable()) {
                            $specifiedTotal += $this->currency($price);
                        }
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
     * Get Total (specified) Item discount before Tax
     *
     * @return string
     */
    public function getPreTaxSpecifiedItemDiscountTotal()
    {
        $total = $this->currency(0);
        if (count($this->_cart->getPreTaxDiscounts()) > 0) {
            foreach($this->_cart->getPreTaxDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $specifiedTotal = 0;

                $discountItems = $discount->getItems();
                if (count($discountItems) > 0) {
                    foreach($discountItems as $key => $qty) {
                        $item = $this->_cart->getItem($key);
                        $price = $item->getPrice();
                        if ($item->getIsDiscountable()) {
                            if ($item->getQty() < $qty) {
                                $qty = $item->getQty();
                            }
                            $specifiedTotal += $this->currency($price * $qty);
                        }
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
     * Get Total (specified) Shipment discount before Tax
     *
     * @return string
     */
    public function getPreTaxSpecifiedShipmentDiscountTotal()
    {
        $total = $this->currency(0);
        if (count($this->_cart->getPreTaxDiscounts()) > 0) {
            foreach($this->_cart->getPreTaxDiscounts() as $key => $discount) {

                if ($discount->getTo() != Discount::$toSpecified) {
                    continue;
                }

                $specifiedTotal = 0;

                $discountShipments = $discount->getShipments();
                if (count($discountShipments) > 0) {
                    foreach($discountShipments as $key => $value) {
                        //value and key are same
                        $shipment = $this->_cart->getShipment($key);
                        $price = $shipment->getPrice();
                        if ($shipment->getIsDiscountable()) {
                            $specifiedTotal += $this->currency($price);
                        }
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
     * Get the max amount that can be taxed, for Items and Shipments
     *
     * @return string
     */
    public function getTaxableTotal()
    {
        return $this->currency($this->getTaxableItemTotal() + $this->getTaxableShipmentTotal());
    }

    /**
     * Get the max amount that can be taxed on items
     *
     * @return string
     */
    public function getTaxableItemTotal()
    {
        $total = 0;
        if (count($this->_cart->getItems()) > 0) {
            foreach($this->_cart->getItems() as $productKey => $item) {
                if ($item->getIsTaxable()) {
                    $total += $this->currency($item->getPrice() * $item->getQty());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Get taxable shipment total
     *
     * @return string
     */
    public function getTaxableShipmentTotal()
    {
        $total = 0;
        if (count($this->_cart->getShipments()) > 0) {
            foreach($this->_cart->getShipments() as $shipmentKey => $shipment) {
                if ($shipment->getIsTaxable()) {
                    $total += $this->currency($shipment->getPrice());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Get discountable shipment total
     *
     * @return string
     */
    public function getDiscountableShipmentTotal()
    {
        $total = 0;
        if (count($this->_cart->getShipments()) > 0) {
            foreach($this->_cart->getShipments() as $shipmentKey => $shipment) {
                if ($shipment->getIsDiscountable()) {
                    $total += $this->currency($shipment->getPrice());
                }
            }
        }
        return $this->currency($total);
    }

    /**
     * Get the max amount that can be discounted from Items
     *
     * @return string
     */
    public function getDiscountableItemTotal()
    {
        $total = 0;
        if (count($this->_cart->getItems()) > 0) {
            foreach($this->_cart->getItems() as $itemKey => $item) {
                if ($item->getIsDiscountable()) {
                    $total += $this->currency($item->getPrice() * $item->getQty());
                }
            }
        }
        return $this->currency($total);
    }

}