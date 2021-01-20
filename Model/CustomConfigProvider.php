<?php
namespace Cardinity\Magento\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class CustomConfigProvider implements ConfigProviderInterface
{       
    protected $_objectManager;
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;        
    }
    
    public function getConfig()
    {
        $config = [];
        $config['customData'] = 'My Custom Data text.';
        

        $configData = $this->_objectManager->get('Cardinity\Magento\Helper\Data');        
        $config['externalEnabled'] = $configData->getConfig('payment/cardinity/external_enabled');

        return $config;
    }
}