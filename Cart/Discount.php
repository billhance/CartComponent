<?php

/**
 * Basic Discount object
 * Discounts are applied as a flat amount or a percentage.
 * Discounts can be applied to shipments or items in general, or to specific items, shipments
 * If a Discount is declared as 'specified', the Items and Shipments specified within the Discount
 *  will not be able to be discounted by other general or non-specific Discounts.
 * At this time, Items and Shipments within multiple specified-type discounts can be discounted more than once.
 * Specified-type Discounts and non-specified-type Discounts are mutually exclusive.
 * Items and Shipments within multiple specified-type discounts can be discounted more than once, 
 *  as well as for non-specified-type Discounts. (they are mutually exclusive however)
 *
 * (c) Jesse Hanson [jessehanson.com]
 */

class Discount 
{

	/**
	 * @var string|int
	 */
	protected $_id; // YOUR Discount Id

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
	protected $_to; // products|shipping|specified

	/**
	 * @var array of Item, key => quantity
	 */
	protected $_items;

	/**
	 * @var array of Shipment, key => key
	 */
	protected $_shipments;

	/**
	 * @var bool
	 */
	protected $_isPreTax; // either before or after tax

	// vars for array representation

	static $id = 'id'; // array key

	static $value = 'value'; // array key

    static $as = 'as'; // array key

    static $asFlat = 'flat'; // array key value

    static $asPercent = 'percent'; // array key value

    static $to = 'to'; // array key

    static $toSpecified = 'specified'; // array key value

    static $toShipments = 'shipments'; // array key value

    static $toItems = 'items'; // array key value

    static $isPreTax = 'is_pre_tax'; // array key

    static $prefix = 'discount-'; // array key prefix

	public function __construct($id = 0, $value = 0, $as = 'flat', $to = 'items', $isPreTax = true, $items = array(), $shipments = array())
	{
		$this->_id = $id;
		$this->_as = $as;
		$this->_value = $value;
		$this->_to = $to;
		$this->_isPreTax = $isPreTax;
		$this->_items = $items;
		$this->_shipments = $shipments;
	}

	/**
	 * Get key for associative arrays
	 */
	static function getKey($id)
	{
		return self::$prefix . $id;
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
			self::$id   		=> $this->getId(),
			self::$value        => $this->getValue(),
			self::$as           => $this->getAs(),
			self::$to           => $this->getTo(),
			self::$isPreTax     => $this->getIsPreTax(),
			self::$toItems      => $this->getItems(),
			self::$toShipments  => $this->getShipments(),
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

		$id = isset($data[self::$id]) ? $data[self::$id] : '';

		$as = isset($data[self::$as]) ? $data[self::$as] : '';
		$as = ($as == self::$asFlat) ? self::$asFlat : self::$asPercent;

		$to = isset($data[self::$to]) ? $data[self::$to] : '';
		if (!in_array($to, array(self::$toSpecified, self::$toItems, self::$toShipments))) {
			$to = self::$toItems;
		}

		$value = isset($data[self::$value]) ? $data[self::$value] : 0;
		$isPreTax = isset($data[self::$isPreTax]) ? $data[self::$isPreTax] : false;

		$toItems = isset($data[self::$toItems]) ? $data[self::$toItems] : array();
		$toShipments = isset($data[self::$toShipments]) ? $data[self::$toShipments] : array();

		$this->_id = $id;
		$this->_as = $as;
		$this->_to = $to;
		$this->_value = $value;
		$this->_isPreTax = $isPreTax;
		$this->_items = $toItems;
		$this->_shipments = $toShipments;

		return $this;
	}

	/**
	 * Reset object to defaults
	 */
	public function reset()
	{
		$this->_id = 0;
		$this->_as = self::$asFlat;
		$this->_to = self::$toItems;
		$this->_value = 0;
		$this->_isPreTax = false;
		$this->_items = array();
		$this->_shipments = array();

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
	public function setId($id)
	{
		$this->_id = $id;
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

	// DEV NOTE:
	// Items and Shipments are only for specified discounts
	// set $this->_to = self::$toSpecified

	/**
	 * Accessor
	 */
	public function getItems()
	{
		return $this->_items;
	}

	/**
	 * Add an Item to this Discount
	 */
	public function addItem(Item $item, $qty = 1)
	{
		$key = Item::getKey($item->getId());
		$this->_items[$key] = $qty;
		return $this;
	}

	/**
	 * Remove an Item from this Discount
	 */
	public function removeItem($key)
	{
		if (isset($this->_items[$key])) {
			unset($this->_items[$key]);
		}
		return $this;
	}

	/**
	 * Assert item exists
	 *
	 * @param string itemKey
	 * @return boolean hasItem
	 */
	public function hasItem($key)
	{
		return isset($this->_items[$key]);
	}

	/**
	 * Set quantity to item
	 */
	public function setItemQty($key, $qty)
	{
		if (isset($this->_items[$key])) {
			$this->_items[$key] = $qty;
		}
		return $this;
	}

	/**
	 * Accessor
	 */
	public function getShipments()
	{
		return $this->_shipments;
	}

	/**
	 * Add a Shipment to this Discount
	 */
	public function addShipment(Shipment $shipment)
	{
		$key = Shipment::getKey($shipment->getId());
		$this->_shipments[$key] = $key;
		return $this;
	}

	/**
	 * Remove an Item from this Discount
	 */
	public function removeShipment($key)
	{
		if (isset($this->_shipments[$key])) {
			unset($this->_shipments[$key]);
		}
		return $this;
	}

	/**
	 * Assert shipment exists
	 *
	 * @param string itemKey
	 * @return boolean hasItem
	 */
	public function hasShipment($key)
	{
		return isset($this->_shipments[$key]);
	}

}