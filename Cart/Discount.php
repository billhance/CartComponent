<?php

/**
 * Basic Discount object
 * Apply a discount as flat or percent, to items or shipments.
 * This object is just starting to evolve into more complex discounts. 
 *
 * (c) Jesse Hanson [jessehanson.com]
 */

class Discount 
{

	/**
	 * @var string|int
	 */
	protected $_discountId; // YOUR Id

	/**
	 * @var string
	 */
	protected $_as; // percent|flat

	/**
	 * @var float
	 */
	protected $_value; // eg 2.50 is a discount, 0.25 is 25% off, 

	/**
	 * @var string
	 */
	protected $_to; // products|shipping

	/**
	 * @var array of Item
	 */
	protected $_items;

	/**
	 * @var string
	 */
	//protected $_toKey; // all|productKey|shipmentKey // possible option to apply to a single item, or all items

	/**
	 * @var bool
	 */
	protected $_isPreTax; // either before or after tax

	// vars for array representation

	static $discountId = 'discount_id'; // array key

	static $value = 'value'; // array key

    static $as = 'as'; // array key

    static $asFlat = 'flat'; // array key value

    static $asPercent = 'percent'; // array key value

    static $to = 'to'; // array key

    static $toShipping = 'shipping'; // array key value

    static $toProducts = 'products'; // array key value

    static $isPreTax = 'is_pre_tax';

	public function __construct($discountId = 0, $value = 0, $as = 'flat', $to = 'products', $isPreTax = true)
	{
		$this->_discountId = $discountId;
		$this->_as = $as;
		$this->_value = $value;
		$this->_to = $to;
		$this->_isPreTax = $isPreTax;
		$this->_items = array();
	}

	/**
	 * Serialize object as string
	 *
	 */
	public function __toString()
    {
        return $this->toJson();
    }

	/**
	 * Serialize object as json
	 *
	 */
	public function toJson()
	{
		return json_encode($this->toArray());
	}

	/**
	 * Serialize object as array
	 *
	 */
	public function toArray()
	{
		return array(
			self::$discountId => $this->getDiscountId(),
			self::$value      => $this->getValue(),
			self::$as         => $this->getAs(),
			self::$to         => $this->getTo(),
			self::$isPreTax   => $this->getIsPreTax(),
			//self::$items // complex discounts are just starting to get interesting
		);
	}

	/**
	 * Import composition from json
	 */
	public function importJson($json, $reset = true)
	{
		$data = @ (array) json_decode($json);

		if ($reset) {
			$this->reset();
		}

		$discountId = isset($data[self::$discountId]) ? $data[self::$discountId] : '';
		$as = isset($data[self::$as]) ? $data[self::$as] : '';
		$to = isset($data[self::$to]) ? $data[self::$to] : '';
		$value = isset($data[self::$value]) ? $data[self::$value] : 0;
		$isPreTax = isset($data[self::$isPreTax]) ? $data[self::$isPreTax] : false;

		$this->_discountId = $discountId;
		$this->_as = $as;
		$this->_to = $to;
		$this->_value = $value;
		$this->_isPreTax = $isPreTax;
		//$this->_items // will have complex discounts

		return $this;
	}

	/**
	 * Reset object to defaults
	 */
	public function reset()
	{
		$this->_value = 0;
		$this->_as = self::$asFlat;
		$this->_to = self::$toProducts;
		$this->_isPreTax = false;
		$this->_items = array();
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getDiscountId()
	{
		return $this->_discountId;
	}

	/**
	 * Mutator
	 */
	public function setDiscountId($discountId)
	{
		$this->_discountId = $discountId;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getAs()
	{
		return $this->_as;
	}

	/**
	 * Mutator
	 */
	public function setAs($as)
	{
		$this->_as = $as;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Mutator
	 */
	public function setValue($value)
	{
		$this->_value = $value;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getTo()
	{
		return $this->_to;
	}

	/**
	 * Mutator
	 */
	public function setTo($to)
	{
		$this->_to = $to;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getIsPreTax()
	{
		return $this->_isPreTax;
	}

	/**
	 * Mutator
	 */
	public function setIsPreTax($beforeTax)
	{
		$this->_isPreTax = $beforeTax;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getItems()
	{
		return $this->_items;
	}

	/**
	 * Add an item to this shipment
	 */
	public function addItem($productKey, Item $item)
	{
		$this->_items[$productKey] = $item;
		return $this;
	}

	/**
	 * Remove an item from this shipment
	 */
	public function removeItem($productKey)
	{
		if (isset($this->_items[$productKey])) {
			unset($this->_items[$productKey]);
		}
		return $this;
	}

}