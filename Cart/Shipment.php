<?php

class Shipment 
{

    /**
     * @var string|int
     */
    protected $_id;

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

    static $prefix = 'shipment-'; // array key prefix

    /**
     * Get key for associative arrays
     */
    static function getKey($id)
    {
        return self::$prefix . $id;
    }

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Serialize object as string
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
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'              => $this->getId(),
            'method'          => $this->getMethod(),
            'vendor'          => $this->getVendor(),
            'code'            => $this->getCode(),
            'price'           => $this->getPrice(),
            'is_taxable'      => $this->getIsTaxable(),
            'is_discountable' => $this->getIsDiscountable(),
            'weight'          => $this->getWeight(),
            'items'           => $this->getItems(),
        );
    }

    /**
     * Import object from json
     *
     * @param string $json
     * @param bool
     * @return Shipment
     */
    public function importJson($json, $reset = true)
    {
        if ($reset) {
            $this->reset();
        }

        $data = @ (array) json_decode($json);

        $id = isset($data['id']) ? $data['id'] : '';
        $price = isset($data['price']) ? $data['price'] : 0;
        $isTaxable = isset($data['is_taxable']) ? $data['is_taxable'] : false;
        $isDiscountable = isset($data['is_discountable']) ? $data['is_discountable'] : false;
        $weight = isset($data['weight']) ? $data['weight'] : 0;
        $method = isset($data['method']) ? $data['method'] : '';
        $vendor = isset($data['vendor']) ? $data['vendor'] : '';

        $items = isset($data['items']) ? $data['items'] : array();
        if ((is_array($items) || $items instanceof stdClass) && count($items) > 0) {
            $items = array();
            foreach($items as $key => $qty) {
                $items[$key] = $qty;
            }
        } else {
            $items = array();
        }

        $this->_id = $id;
        $this->_price = $price;
        $this->_isTaxable = $isTaxable;
        $this->_isDiscountable = $isDiscountable;
        $this->_weight = $weight;
        $this->_method = $method;
        $this->_vendor = $vendor;
        $this->_items = $items;

        return $this;
    }

    /**
     * Import from stdClass
     *
     * @param stdClass
     * @return Shipment
     */
    public function importStdClass($obj)
    {
        $id = isset($obj->id) ? $obj->id : '';
        $price = (float) isset($obj->price) ? $obj->price : 0;
        $weight = (float) isset($obj->weight) ? $obj->weight : 0;
        $isTaxable = (bool) isset($obj->is_taxable) ? $obj->is_taxable : false;
        $isDiscountable = (bool) isset($obj->is_discountable) ? $obj->is_discountable : false;
        $vendor = isset($obj->vendor) ? $obj->vendor : '';
        $method = isset($obj->method) ? $obj->method : '';

        $items = array();
        if (isset($obj->items) && count($obj->items) > 0) {
            foreach($obj->items as $key => $qty) {
                $items[$key] = $qty;
            }
        }

        $this->_id = $id;
        $this->_price = $price;
        $this->_isTaxable = $isTaxable;
        $this->_isDiscountable = $isDiscountable;
        $this->_weight = $weight;
        $this->_method = $method;
        $this->_vendor = $vendor;
        $this->_items = $items;

        return $this;
    }

    /**
     * Reset defaults
     *
     * @return Shipment
     */
    public function reset()
    {
        $this->_id = '';
        $this->_price = 0;
        $this->_isTaxable = false;
        $this->_isDiscountable = true;
        $this->_weight = 0;
        $this->_method = '';
        $this->_vendor = '';
        $this->_items = array();

        return $this;
    }

    /**
     * Check if this Shipment validates a DiscountCondition
     *
     * @param DiscountCondition
     * @return bool
     */
    public function isValidCondition(DiscountCondition $condition)
    {
        switch($condition->getSourceEntityField()) {
            case 'code':
                $condition->setSourceValue($this->getCode());
                break;
            case 'weight':
                $condition->setSourceValue($this->getWeight());
                break;
            case 'price':
                $condition->setSourceValue($this->getPrice());
                break;
            default:
                //no-op
                break;
        }

        return $condition->isValid();
    }

    /**
     * Getter
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Setter
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * Getter
     */
    public function getCode()
    {
        return $this->getVendor() . '_' . $this->getMethod();
    }

    /**
     * Getter
     */
    public function getPrice()
    {
        return $this->_price;
    }

    /**
     * Setter
     */
    public function setPrice($price)
    {
        $this->_price = $price;
        return $this;
    }

    /**
     * Getter
     */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
     * Getter
     */
    public function getIsTaxable()
    {
        return $this->_isTaxable;
    }

    /**
     * Setter
     */
    public function setIsTaxable($isTaxable)
    {
        $this->_isTaxable = $isTaxable;
        return $this;
    }

    /**
     * Getter
     */
    public function getIsDiscountable()
    {
        return $this->_isDiscountable;
    }

    /**
     * Setter
     */
    public function setIsDiscountable($isDiscountable)
    {
        $this->_isDiscountable = $isDiscountable;
        return $this;
    }

    /**
     * Setter
     */
    public function setWeight($weight)
    {
        $this->_weight = $weight;
        return $this;
    }

    /**
     * Getter
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Setter
     */
    public function setMethod($method)
    {
        $this->_method = $method;
        return $this;
    }

    /**
     * Getter
     */
    public function getVendor()
    {
        return $this->_vendor;
    }

    /**
     * Setter
     */
    public function setVendor($vendor)
    {
        $this->_vendor = $vendor;
        return $this;
    }

    /**
     * Getter
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * Add an item reference to this shipment
     *
     * @param Item
     * @return Shipment
     */
    public function addItem(Item $item)
    {
        $key = Item::getKey($item->getId());
        $this->_items[$key] = $item->getQty();
        return $this;
    }

    /**
     * Remove an item reference from this shipment
     *
     * @param Item|string
     * @return Shipment
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
     * Assert item reference exists
     *
     * @param string itemKey
     * @return bool hasItem
     */
    public function hasItem($key)
    {
        if ($key instanceof Item) {
            $key = Item::getKey($key->getId());
        }

        return isset($this->_items[$key]);
    }

}
