<?php

/**
 * Copyright © landofcoder.com All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Lofmobile\SocialLogin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Data extends AbstractHelper
{
    const MODULE_BASE_SETTING_XML_PATH = "lofmobilesociallogin";

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->_storeManager = $storeManager;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Return module config value by key and store
     *
     * @param string $key
     * @param \Magento\Store\Model\Store|int|string $store
     * @param string|int|mixed|null $default
     * @return string|null
     */
    public function getConfig($key, $store = null, $default = null)
    {
        $value = $this->getConfigData(self::MODULE_BASE_SETTING_XML_PATH . '/' . $key, $store);
        return $value != null && $value != "" ? $value : $default;
    }

    /**
     * Get config data
     * @param string $path
     * @param mixed|Object|int|null $store
     *
     * @return mixed|string|array|int|bool|null
     */
    public function getConfigData($path, $store = null)
    {
        $store = $this->_storeManager->getStore($store);
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Is module enabled
     * @param mixed|Object|int|null $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        return (bool)$this->getConfig("general/enabled", $store);
    }

    /**
     * encode object
     *
     * @param mixed|Object|array
     * @return string
     */
    public function encodeData($object)
    {
        return $this->serializer->serialize($object);
    }

    /**
     * decode string
     *
     * @param string
     * @return mixed|array|Object
     */
    public function decodeData($string)
    {
        return $this->serializer->unserialize($string);
    }
}
