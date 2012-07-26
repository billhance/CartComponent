<?php

class Discount 
{
    /**
     * @var string|int
     */
    protected $_id; // YOUR Discount/Rule Id

    /**
     * @var array
     */
    protected $_discountConditions; // discountConditions[key] = DiscountCondition object

    /**
     * @var DiscountConditionCompare
     */
    protected $_criteriaConditionCompare;

    /**
     * @var DiscountConditionCompare
     */
    protected $_targetConditionCompare;

    /*
    [compare-1] => array(
        [op] = and,
        [0] => array(
            [op] = and,
            [left] = condition-1,
            [right] = condition-2,
        ),
        [1] => array(
            [op] = or,
            [left] = rule-3,
            [right] = [compare-2] => array(
                [op] = or,
                [left] = condition-4,
                [right] = condition-5,
            )
        ),
    )
    */

    /**
     * @var string
     */
    protected $_as; // percent|flat

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var int timestamp
     */
    protected $_startDatetime;

    /**
     * @var int timestamp
     */
    protected $_endDatetime;

    /**
     * @var int
     */
    protected $_priority; // YOUR Discount/Rule priority

    /**
     * @var float
     */
    protected $_value; // eg 2.50 is a flat discount, 0.25 is 25% off, 

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

    /**
     * @var bool
     */
    protected $_isAuto; // system will try to apply it automatically

    /**
     * @var bool
     */
    protected $_isStopper;

    /**
     * @var string
     */
    protected $_couponCode; // should already be validated

    // array key values
    static $asFlat = 'flat';
    static $asPercent = 'percent';
    static $toSpecified = 'specified';
    static $toShipments = 'shipments';
    static $toItems = 'items';

    static $prefix = 'discount-'; // array key prefix

    
    public function __construct()
    {
        $this->reset();
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
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Serialize object as json
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
        $criteriaCompareData = array();
        if (is_object($this->getCriteriaConditionCompare())) {
            $criteriaCompareData = $this->getCriteriaConditionCompare()->toArray();
        }

        $targetCompareData = array();
        if (is_object($this->getTargetConditionCompare())) {
            $targetCompareData = $this->getTargetConditionCompare()->toArray();
        }

        return array(
            'id'          => $this->getId(),
            'name'        => $this->getName(),
            'value'       => $this->getValue(),
            'as'          => $this->getAs(),
            'to'          => $this->getTo(),
            'is_pre_tax'  => $this->getIsPreTax(),
            'is_auto'     => $this->getIsAuto(),
            'coupon_code' => $this->getCouponCode(),
            'items'       => $this->getItems(),
            'shipments'   => $this->getShipmentsAsArray(),
            'pre_conditions' => $criteriaCompareData,
            'target_conditions' => $targetCompareData,
            'is_stopper'  => $this->getIsStopper(),
            'priority'    => $this->getPriority(),
        );
    }

    /**
     * Import composition from json
     *
     * @param string $json
     * @param bool
     * @return Discount
     */
    public function importJson($json, $reset = true)
    {
        $data = @ (array) json_decode($json);

        if ($reset) {
            $this->reset();
        }

        $id = isset($data['id']) ? $data['id'] : '';
        $name = isset($data['name']) ? $data['name'] : '';

        $as = isset($data['as']) ? $data['as'] : '';
        $as = ($as == self::$asFlat) ? self::$asFlat : self::$asPercent;

        $to = isset($data['to']) ? $data['to'] : '';
        if (!in_array($to, array(self::$toSpecified, 'items', 'shipments'))) {
            $to = 'items';
        }

        $value = isset($data['value']) ? $data['value'] : 0;
        $isPreTax = isset($data['is_pre_tax']) ? $data['is_pre_tax'] : false;

        $toItems = isset($data['items']) ? $data['items'] : array();
        $toShipments = isset($data['shipments']) ? $data['shipments'] : array();

        $shipments = array();
        $items = array();

        if (count($toItems) > 0) {
            foreach($toItems as $key => $item) {
                $tmpItem = new Item();

                if ($item instanceof stdClass) {
                    $tmpItem->importStdClass($item);
                } else if (is_array($item)) {
                    $tmpItem->importJson(json_encode($item));
                }
                $items[$key] = $tmpItem->getQty();
            }
        }

        if (count($toShipments) > 0) {
            foreach($toShipments as $key => $shipment) {
                $tmpShipment = new Shipment();
                if ($shipment instanceof stdClass) {
                    $tmpShipment->importStdClass($shipment);
                } else if (is_array($shipment)) {
                    $tmpShipment->importJson(json_encode($shipment));
                }
                $shipments[$key] = $tmpShipment->toArray();
            }
        }

        $preConditionObj = isset($data['pre_conditions']) ? $data['pre_conditions'] : new stdClass();
        $targetConditionObj = isset($data['target_conditions']) ? $data['target_conditions'] : new stdClass();

        $preCondition = new DiscountConditionCompare();
        $preCondition->importJson(json_encode($preConditionObj));

        $targetCondition = new DiscountConditionCompare();
        $targetCondition->importJson(json_encode($targetConditionObj));

        $couponCode = isset($data['coupon_code']) ? $data['coupon_code'] : '';
        $isAuto = isset($data['is_auto']) ? $data['is_auto'] : false;

        $isStopper = (bool) isset($data['is_stopper']) ? $data['is_stopper'] : false;
        $priority = isset($data['priority']) ? $data['priority'] : 0;

        $this->_id = $id;
        $this->_name = $name;
        $this->_as = $as;
        $this->_to = $to;
        $this->_value = $value;
        $this->_isPreTax = $isPreTax;
        $this->_isAuto = $isAuto;
        $this->_isStopper = $isStopper;
        $this->_priority = $priority;
        $this->_couponCode = $couponCode;
        $this->_items = $items;
        $this->_shipments = $shipments;
        $this->_targetConditionCompare = $preCondition;
        $this->_criteriaConditionCompare = $targetCondition;

        return $this;
    }

    /**
     * Import from stdClass
     *
     * @param stdClass
     * @param bool
     * @return Discount
     */
    public function importStdClass($obj, $reset = true)
    {
        if ($reset) {
            $this->reset();
        }

        $id = isset($obj->id) ? $obj->id : '';
        $key = ($id > 0) ? self::getKey($id) : '';
        $name = isset($obj->name) ? $obj->name : '';

        $as = isset($obj->as) ? $obj->as : '';
        $as = ($as == self::$asFlat) ? self::$asFlat : self::$asPercent;

        $to = isset($obj->to) ? $obj->to : '';
        if (!in_array($to, array(self::$toSpecified, self::$toItems, self::$toShipments))) {
            $to = self::$toItems;
        }

        $value = isset($obj->value) ? $obj->value : 0;
        $isPreTax = isset($obj->is_pre_tax) ? $obj->is_pre_tax : false;

        $toItems = isset($obj->items) ? $obj->items : array();
        $toShipments = isset($obj->shipments) ? $obj->shipments : array();

        $shipments = array();
        $items = array();

        if (count($toItems) > 0) {
            
            foreach($toItems as $key => $item) {
                $tmpItem = new Item();
                if ($item instanceof stdClass) {
                    $tmpItem->importStdClass($item);
                } else if (is_array($item)) {
                    $tmpItem->importJson(json_encode($item));
                }
                $items[$key] = $tmpItem->getQty();
            }
        }

        if (count($toShipments) > 0) {
            
            foreach($toShipments as $key => $shipment) {
                $tmpShipment = new Shipment();
                if ($shipment instanceof stdClass) {
                    $tmpShipment->importStdClass($shipment);
                } else if (is_array($shipment)) {
                    $tmpShipment->importJson(json_encode($shipment));
                }
                $shipments[$key] = $tmpShipment->toArray();
            }
        }

        $preConditionObj = isset($obj->pre_conditions) ? $obj->pre_conditions : new stdClass();
        $targetConditionObj = isset($obj->target_conditions) ? $obj->target_conditions : new stdClass();

        $preCondition = new DiscountConditionCompare();
        if ($preConditionObj instanceof stdClass) {
            $preCondition->importStdClass($preConditionObj);
        } else if (is_array($preConditionObj)) {
            $preCondition->importJson(json_encode($preConditionObj));
        }

        $targetCondition = new DiscountConditionCompare();
        if ($targetConditionObj instanceof stdClass) {
            $targetCondition->importStdClass($targetConditionObj);
        } else if (is_array($targetConditionObj)) {
            $targetCondition->importJson(json_encode($targetConditionObj));
        }

        $couponCode = isset($obj->coupon_code) ? $obj->coupon_code : '';
        $isAuto = isset($obj->is_auto) ? $obj->is_auto : false;

        $isStopper = (bool) isset($obj->is_stopper) ? $obj->is_stopper : false;
        $priority = isset($obj->priority) ? $obj->priority : 0;

        $this->_id = $id;
        $this->_name = $name;
        $this->_as = $as;
        $this->_to = $to;
        $this->_value = $value;
        $this->_isPreTax = $isPreTax;
        $this->_isAuto = $isAuto;

        $this->_isStopper = $isStopper;
        $this->_priority = $priority;

        $this->_couponCode = $couponCode;
        $this->_items = $items;
        $this->_shipments = $shipments;

        $this->_targetConditionCompare = $preCondition;
        $this->_criteriaConditionCompare = $targetCondition;

        return $this;
    }

    /**
     * Import from Entity
     *
     * @param object|mixed
     * @return Discount
     */
    public function importEntity($entity)
    {
        $id = $entity->getId();
        $name = $entity->getName();
        $as = $entity->getAs();
        $to = $entity->getTo();
        $value = $entity->getValue();
        $isPreTax = $entity->getIsPreTax();
        $isAuto = $entity->getIsAuto();

        $isStopper = $entity->getIsStopper();
        $priority = $entity->getPriority();

        $couponCode = $entity->getCouponCode();

        $toItems = array(); // won't know this until we validate conditions
        $toShipments = array(); // won't know this until we validate conditions

        $this->_id = $id;
        $this->_name = $name;
        $this->_as = $as;
        $this->_to = $to;
        $this->_value = $value;
        $this->_isPreTax = $isPreTax;
        $this->_isAuto = $isAuto;

        $this->_isStopper = $isStopper;
        $this->_priority = $priority;

        $this->_couponCode = $couponCode;
        $this->_items = $toItems;
        $this->_shipments = $toShipments;

        //TODO
        //$this->_criteriaConditionCompare = $entity->getBla();
        //$this->_targetConditionCompare = $entity->getBla();

        return $this;
    }

    /**
     * Reset object to defaults
     *
     * @return Discount
     */
    public function reset()
    {
        $this->_id = 0;
        $this->_name = '';
        $this->_as = self::$asFlat;
        $this->_to = self::$toItems;
        $this->_value = 0;
        $this->_isPreTax = false;
        $this->_isAuto = false;
        $this->_couponCode = '';
        $this->_items = array();
        $this->_shipments = array();

        $this->_isStopper = false;
        $this->_priority = 0;

        $this->_criteriaConditionCompare = null;
        $this->_targetConditionCompare = null;

        return $this;
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
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Setter
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * Getter
     */
    public function getCriteriaConditionCompare()
    {
        return $this->_criteriaConditionCompare;
    }

    /**
     * Setter
     */
    public function setCriteriaConditionCompare(DiscountConditionCompare $compare)
    {
        $this->_criteriaConditionCompare = $compare;
        return $this;
    }

    /**
     * Getter
     */
    public function getTargetConditionCompare()
    {
        return $this->_targetConditionCompare;
    }

    /**
     * Setter
     */
    public function setTargetConditionCompare(DiscountConditionCompare $compare)
    {
        $this->_targetConditionCompare = $compare;
        return $this;
    }

    /**
     * Getter
     */
    public function getAs()
    {
        return $this->_as;
    }

    /**
     * Setter
     */
    public function setAs($as)
    {
        $this->_as = $as;
        return $this;
    }

    /**
     * Getter
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Setter
     */
    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    /**
     * Getter
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * Setter
     */
    public function setTo($to)
    {
        $this->_to = $to;
        return $this;
    }

    /**
     * Getter
     */
    public function getIsPreTax()
    {
        return $this->_isPreTax;
    }

    /**
     * Setter
     */
    public function setIsPreTax($beforeTax)
    {
        $this->_isPreTax = $beforeTax;
        return $this;
    }

    /**
     * Getter
     */
    public function getIsAuto()
    {
        return $this->_isAuto;
    }

    /**
     * Setter
     */
    public function setIsAuto($isAuto)
    {
        $this->_isAuto = $isAuto;
        return $this;
    }

    /**
     * Getter
     */
    public function getCouponCode()
    {
        return $this->_couponCode;
    }

    /**
     * Setter
     */
    public function setCouponCode($couponCode)
    {
        $this->_couponCode = $couponCode;
        return $this;
    }

    // DEV NOTE:
    // Items and Shipments are only for specified discounts
    // set $this->_to = self::$toSpecified

    /**
     * Getter
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * Add an Item to this Discount
     *
     * @return Discount
     */
    public function addItem(Item $item, $qty = 1)
    {
        $key = Item::getKey($item->getId());
        $this->_items[$key] = $qty;
        return $this;
    }

    /**
     * Remove an Item from this Discount
     *
     * @return Discount
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
     *
     * @return Discount
     */
    public function setItemQty($key, $qty)
    {
        if (isset($this->_items[$key])) {
            $this->_items[$key] = $qty;
        }
        return $this;
    }

    /**
     * Export Shipments as one large array
     *
     * @return array
     */
    public function getShipmentsAsArray()
    {
        $shipments = array();
        if (!count($this->getShipments())) {
            return $shipments;
        }

        foreach($this->getShipments() as $key => $shipment) {
            if ($shipment instanceof Shipment) {
                $shipments[$key] = $shipment->toArray();
            } else {
                $tmpShipment = new Shipment();
                $shipments[$key] = $tmpShipment->importJson(json_encode($shipment))->toArray();
            }
        }

        return $shipments;
    }

    /**
     * Getter
     */
    public function getShipments()
    {
        return $this->_shipments;
    }

    /**
     * Add a Shipment to this Discount
     *
     * @return Discount
     */
    public function addShipment(Shipment $shipment)
    {
        $key = Shipment::getKey($shipment->getId());
        $this->_shipments[$key] = $shipment;
        return $this;
    }

    /**
     * Remove an Item from this Discount
     *
     * @return Discount
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

    /**
     * Getter
     */
    public function getIsStopper()
    {
        return $this->_isStopper;
    }

    /**
     * Setter
     */
    public function setIsStopper($isStopper)
    {
        $this->_isStopper = (bool) $isStopper;
        return $this;
    }

    /**
     * Getter
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * Setter
     */
    public function setPriority($priority)
    {
        $this->_priority = $priority;
        return $this;
    }

}