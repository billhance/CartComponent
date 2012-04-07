<?php

/**
 * Basic Shipment object
 * This object represents a common shipment.
 * Currently, there is no logic around calculating the price.
 * All of the variables outside of the price are simply for storage of your own variables.
 * 
 * (c) Jesse Hanson [jessehanson.com]
 */

class Shipment 
{

	/**
	 * @var string|int
	 */
	protected $_shipmentId;

	/**
	 * @var bool
	 */
	protected $_isTaxable;

	/**
	 * @var bool
	 */
	protected $_isDiscountable;

	/**
	 * @var array of Item
	 */
	protected $_items;

	/**
	 * @var float
	 */
	protected $_price;

	/**
	 * @var float
	 */
	protected $_weight;

	/**
	 * @var string|int
	 */
	protected $_method;

	/**
	 * @var string|int
	 */
	protected $_vendor;

	// array keys for import/export

	static $shipmentId = 'shipment_id';

	static $price = 'price';

	static $isTaxable = 'is_taxable';

	static $isDiscountable = 'is_discountable';

	static $weight = 'weight';

	static $method = 'method';

	static $vendor = 'vendor';

	public function __construct($shipmentId = 0, $price = '', $isTaxable = false, $isDiscountable = true, $weight = '', $method = '', $vendor = '')
	{
		$this->_shipmentId = $shipmentId;
		$this->_vendor = $vendor;
		$this->_method = $method;
		$this->_weight = $weight;
		$this->_price = $price;
		$this->_isTaxable = $isTaxable;
		$this->_isDiscountable = $isDiscountable;
		$this->_items = array();
	}

	/**
	 * Serialize object as string (json)
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * Serialize object as json string
	 */
	public function toJson()
	{
		return json_encode($this->toArray());
	}

	/**
	 * Serialize object as array
	 */
	public function toArray()
	{
		return array(
			self::$shipmentId      => $this->getShipmentId(),
			self::$price           => $this->getPrice(),
			self::$isTaxable       => $this->getIsTaxable(),
			self::$isDiscountable  => $this->getIsDiscountable(),
			self::$weight          => $this->getWeight(),
			self::$method          => $this->getMethod(),
			self::$vendor          => $this->getVendor(),
		);
	}

	/**
	 * Import object from json
	 */
	public function importJson($json, $reset = true)
	{
		if ($reset) {
			$this->reset();
		}

		$data = @ (array) json_decode($json);

		$shipmentId = isset($data[self::$shipmentId]) ? $data[self::$shipmentId] : '';
		$price = isset($data[self::$price]) ? $data[self::$price] : 0;
		$isTaxable = isset($data[self::$isTaxable]) ? $data[self::$isTaxable] : false;
		$isDiscountable = isset($data[self::$isDiscountable]) ? $data[self::$isDiscountable] : false;
		$weight = isset($data[self::$weight]) ? $data[self::$weight] : 0;
		$method = isset($data[self::$method]) ? $data[self::$method] : '';
		$vendor = isset($data[self::$vendor]) ? $data[self::$vendor] : '';

		$this->_shipmentId = $shipmentId;
		$this->_price = $price;
		$this->_isTaxable = $isTaxable;
		$this->_isDiscountable = $isDiscountable;
		$this->_weight = $weight;
		$this->_method = $method;
		$this->_vendor = $vendor;

		return $this;
	}

	/**
	 * Reset to defaults
	 */
	public function reset()
	{
		$this->_shipmentId = '';
		$this->_price = 0;
		$this->_isTaxable = false;
		$this->_isDiscountable = true;
		$this->_weight = 0;
		$this->_method = '';
		$this->_vendor = '';
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getShipmentId()
	{
		return $this->_shipmentId;
	}

	/**
	 * Mutator
	 */
	public function setShipmentId($shipmentId)
	{
		$this->_shipmentId = $shipmentId;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getPrice()
	{
		return $this->_price;
	}

	/**
	 * Mutator
	 */
	public function setPrice($price)
	{
		$this->_price = $price;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getWeight()
	{
		return $this->_weight;
	}

	/**
	 * Accessor
	 */
	public function getIsTaxable()
	{
		return $this->_isTaxable;
	}

	/**
	 * Mutator
	 */
	public function setIsTaxable($isTaxable)
	{
		$this->_isTaxable = $isTaxable;
		return $this;
	}

	/**
     * Accessor
     */
    public function getIsDiscountable()
    {
        return $this->_isDiscountable;
    }

    /**
     * Mutator
     */
    public function setIsDiscountable($isDiscountable)
    {
        $this->_isDiscountable = $isDiscountable;
        return $this;
    }

	/**
	 * Mutator
	 */
	public function setWeight($weight)
	{
		$this->_weight = $weight;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getMethod()
	{
		return $this->_method;
	}

	/**
	 * Mutator
	 */
	public function setMethod($method)
	{
		$this->_method = $method;
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getVendor()
	{
		return $this->_vendor;
	}

	/**
	 * Mutator
	 */
	public function setVendor($vendor)
	{
		$this->_vendor = $vendor;
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
