<?php

namespace Swarming\SubscribePro\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class TestConnection extends Action
{
    /**
     * @var \SubscribePro\SdkFactory
     */
    protected $sdkFactory;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Platform
     */
    protected $configPlatform;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Swarming\SubscribePro\Model\Config\Platform $configPlatform
     * @param \SubscribePro\SdkFactory $sdkFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swarming\SubscribePro\Model\Config\Platform $configPlatform,
        \SubscribePro\SdkFactory $sdkFactory
    ) {
        $this->configPlatform = $configPlatform;
        $this->sdkFactory = $sdkFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = [
            'status' => 'fail',
            'message' => __('Invalid values.')
        ];

        $clientId = $this->getRequest()->getParam('client_id');
        $clientSecret = $this->getRequest()->getParam('client_secret');
        $website = $this->getRequest()->getParam('website');

        if (!empty($clientId) && !empty($clientSecret)) {
            $sdk = $this->createSdk($clientId, $clientSecret, $website);

            if ($sdk->getWebhookService()->ping()) {
                $response = [
                    'status' => 'success',
                    'message' => __('Connected Successfully.')
                ];
            } else {
                $response['message'] = __('Failed to connect to platform!');
            }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response);
        return $resultJson;
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $website
     * @return \SubscribePro\Sdk
     */
    protected function createSdk($clientId, $clientSecret, $website)
    {
        $sdk = $this->sdkFactory->create(['config' => [
            'client_id' => $clientId,
            'client_secret' => $this->updateEncryptedClientSecret($clientSecret, $website)
        ]]);
        return $sdk;
    }

    /**
     * @param string $clientSecret
     * @param string $website
     * @return string
     */
    protected function updateEncryptedClientSecret($clientSecret, $website)
    {
        $website = empty($website) ? false : $website;
        return $clientSecret == '******' ? $this->configPlatform->getClientSecret($website) : $clientSecret ;
    }
}