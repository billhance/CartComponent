<?php

class Customer 
{
    /**
     * @var integer $id
     */
    protected $_id;

    /**
     * @var string $_firstName
     */
    protected $_firstName;

    /**
     * @var string $_lastName
     */
    protected $_lastName;

    /**
     * @var string $_email
     */
    protected $_email;

    /**
     * @var string $group
     */
    protected $_group;

    /**
     * @var string $_billingStreet
     */
    protected $_billingStreet;

    /**
     * @var string $_billingCity
     */
    protected $_billingCity;

    /**
     * @var string $_billingState
     */
    protected $_billingState;

    /**
     * @var string $_billingZipcode
     */
    protected $_billingZipcode;

    /**
     * @var boolean $_isShippingSame
     */
    protected $_isShippingSame;

    /**
     * @var string $_shippingStreet
     */
    protected $_shippingStreet;

    /**
     * @var string $_shippingCity
     */
    protected $_shippingCity;

    /**
     * @var string $_shippingState
     */
    protected $_shippingState;

    /**
     * @var string $_shippingZipcode
     */
    protected $_shippingZipcode;

    /**
     *
     */
    public function __construct()
    {

    }

    /**
     *
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     *
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     *
     */
    public function toArray()
    {
        return array(
            'id'               => $this->getId(),
            'group'            => $this->getGroup(),
            'email'            => $this->getEmail(),
            'first_name'       => $this->getFirstName(),
            'last_name'        => $this->getLastName(),
            'billing_street'   => $this->getBillingStreet(),
            'billing_city'     => $this->getBillingCity(),
            'billing_state'    => $this->getBillingState(),
            'billing_zipcode'  => $this->getBillingZipcode(),
            'is_shipping_same' => $this->getIsShippingSame(),
            'shipping_street'  => $this->getShippingStreet(),
            'shipping_city'    => $this->getShippingCity(),
            'shipping_state'   => $this->getShippingState(),
            'shipping_zipcode' => $this->getShippingZipcode(),
        );
    }

    /**
     *
     */
    public function importJson($json)
    {
        $data = @ (array) json_decode($json);

        $id = isset($data['id']) ? $data['id'] : '';
        $group = isset($data['group']) ? $data['group'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $firstName = isset($data['first_name']) ? $data['first_name'] : '';
        $lastName = isset($data['last_name']) ? $data['last_name'] : '';
        $billingStreet = isset($data['billing_street']) ? $data['billing_street'] : '';
        $billingCity = isset($data['billing_city']) ? $data['billing_city'] : '';
        $billingState = isset($data['billing_state']) ? $data['billing_state'] : '';
        $billingZipcode = isset($data['billing_zipcode']) ? $data['billing_zipcode'] : '';
        $isShippingSame = isset($data['is_shipping_same']) ? $data['is_shipping_same'] : '';
        $shippingStreet = isset($data['shipping_street']) ? $data['shipping_street'] : '';
        $shippingCity = isset($data['shipping_city']) ? $data['shipping_city'] : '';
        $shippingState = isset($data['shipping_state']) ? $data['shipping_state'] : '';
        $shippingZipcode = isset($data['shipping_zipcode']) ? $data['shipping_zipcode'] : '';

        $this->setId($id)
             ->setGroup($group)
             ->setEmail($email)
             ->setFirstName($firstName)
             ->setLastName($lastName)
             ->setBillingStreet($billingStreet)
             ->setBillingCity($billingCity)
             ->setBillingState($billingState)
             ->setBillingZipcode($billingZipcode)
             ->setIsShippingSame($isShippingSame)
             ->setShippingStreet($shippingStreet)
             ->setShippingCity($shippingCity)
             ->setShippingState($shippingState)
             ->setShippingZipcode($shippingZipcode)
             ;

        return $this;
    }

    /**
     *
     */
    public function importEntity($entity)
    {
        $id = $entity->getId();
        $group = $entity->getGroup();
        $firstName = $entity->getFirstName();
        $lastName = $entity->getLastName();
        $billingStreet = $entity->getBillingStreet();
        $billingCity = $entity->getBillingCity();
        $billingState = $entity->getBillingState();
        $billingZipcode = $entity->getBillingZipcode();
        $isShippingSame = $entity->getIsShippingSame();
        $shippingStreet = $entity->getShippingStreet();
        $shippingCity = $entity->getShippingCity();
        $shippingState = $entity->getShippingState();
        $shippingZipcode = $entity->getShippingZipcode();
        
        $this->setId($id)
             ->setGroup($group)
             ->setEmail($email)
             ->setFirstName($firstName)
             ->setLastName($lastName)
             ->setBillingStreet($billingStreet)
             ->setBillingCity($billingCity)
             ->setBillingState($billingState)
             ->setBillingZipcode($billingZipcode)
             ->setIsShippingSame($isShippingSame)
             ->setShippingStreet($shippingStreet)
             ->setShippingCity($shippingCity)
             ->setShippingState($shippingState)
             ->setShippingZipcode($shippingZipcode)
             ;

        return $this;
    }

    /**
     *
     */
    public function isValidCondition(DiscountCondition $condition)
    {
        switch($condition->getSourceField()) {
            case 'group':
                $condition->setSourceValue($this->getGroup());
                break;
            case 'billing_state':
                $condition->setSourceValue($this->getBillingState());
                break;
            case 'shipping_state':
                $condition->setSourceValue($this->getShippingState());
                break;
            default:
                //no-op
                break;
        }

        $condition->isValid();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set id
     *
     * @param int
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * Setter
     *
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->_firstName = $firstName;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->_firstName;
    }

    /**
     * Setter
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->_lastName = $lastName;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->_lastName;
    }

    /**
     * Setter
     *
     * @param string $billingStreet
     */
    public function setBillingStreet($billingStreet)
    {
        $this->_billingStreet = $billingStreet;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getBillingStreet()
    {
        return $this->_billingStreet;
    }

    /**
     * Setter
     *
     * @param string $billingCity
     */
    public function setBillingCity($billingCity)
    {
        $this->_billingCity = $billingCity;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getBillingCity()
    {
        return $this->_billingCity;
    }

    /**
     * Setter
     *
     * @param string $billingState
     */
    public function setBillingState($billingState)
    {
        $this->_billingState = $billingState;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getBillingState()
    {
        return $this->_billingState;
    }

    /**
     * Setter
     *
     * @param string $billingZipcode
     */
    public function setBillingZipcode($billingZipcode)
    {
        $this->_billingZipcode = $billingZipcode;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getBillingZipcode()
    {
        return $this->_billingZipcode;
    }

    /**
     * Setter
     *
     * @param boolean $isShippingSame
     */
    public function setIsShippingSame($isShippingSame)
    {
        $this->_isShippingSame = $isShippingSame;
        return $this;
    }

    /**
     * Getter
     *
     * @return boolean 
     */
    public function getIsShippingSame()
    {
        return $this->_isShippingSame;
    }

    /**
     * Setter
     *
     * @param string $shippingStreet
     */
    public function setShippingStreet($shippingStreet)
    {
        $this->_shippingStreet = $shippingStreet;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getShippingStreet()
    {
        return $this->_shippingStreet;
    }

    /**
     * Setter
     *
     * @param string $shippingCity
     */
    public function setShippingCity($shippingCity)
    {
        $this->_shippingCity = $shippingCity;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getShippingCity()
    {
        return $this->_shippingCity;
    }

    /**
     * Setter
     *
     * @param string $shippingState
     */
    public function setShippingState($shippingState)
    {
        $this->_shippingState = $shippingState;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getShippingState()
    {
        return $this->_shippingState;
    }

    /**
     * Setter
     *
     * @param string $shippingZipcode
     */
    public function setShippingZipcode($shippingZipcode)
    {
        $this->_shippingZipcode = $shippingZipcode;
        return $this;
    }

    /**
     * Getter
     *
     * @return string 
     */
    public function getShippingZipcode()
    {
        return $this->_shippingZipcode;
    }

    /**
     * Setter
     *
     * @param text $email
     */
    public function setEmail($email)
    {
        $this->_email = $email;
        return $this;
    }

    /**
     * Getter
     *
     * @return text 
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * Setter
     *
     * @param text $group
     */
    public function setGroup($group)
    {
        $this->_group = $group;
        return $this;
    }

    /**
     * Getter
     *
     * @return text 
     */
    public function getGroup()
    {
        return $this->_group;
    }
    
}
