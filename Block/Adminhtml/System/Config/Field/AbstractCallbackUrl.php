<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Block\Adminhtml\System\Config\Field;

use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\App\Config\ScopeConfigInterface;

abstract class AbstractCallbackUrl extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var string
     */
    const TEMPLATE = 'system/config/webhook_admin.phtml';

    /**
     * @var ApiHandlerService
     */
    private $apiHandler;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * Set the template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return $this;
    }

    /**
     * @param ApiHandlerService $apiHandler
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        ApiHandlerService $apiHandler,
        ScopeConfigInterface $scopeConfig,
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
        parent::__construct($context, $data, $secureRenderer);
        $this->secureRenderer = $secureRenderer;
        $this->apiHandler = $apiHandler;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Overridden method for rendering a field. In this case the field must be only for read.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        try {
            // Get the store code
            $storeCode = $this->_storeManager->getStore()->getCode();

            // Initialize the API handler
            $api = $this->apiHandler->init($storeCode);

            $callbackUrl= $this->getBaseUrl() . 'checkout_com/' . $this->getControllerUrl();
            $privateSharedKey = $this->scopeConfig->getValue(
                'settings/checkoutcom_configuration/private_shared_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // Retrieve all configured webhooks
            $webhooks = $api->checkoutApi->webhooks()->retrieve();
            $webhook = null;
            foreach ($webhooks->list as $list) {
                if ($list->url == $callbackUrl) {
                    $webhook = $list;
                }
            }

            // Get available webhook events
            $events = $api->checkoutApi->events()->types(['version' => '2.0']);
            $eventTypes = $events->list[0]->event_types;
            $headers = array_change_key_case($webhook->headers);

            if (!isset($webhook) || $webhook->event_types != $eventTypes || $headers['authorization'] != $privateSharedKey) {
                // Webhook not configured
                $element->setData('value', $callbackUrl);
                $element->setReadonly('readonly');

                $this->addData(
                    [
                        'element_html'      => $element->getElementHtml(),
                        'button_label'      => 'set webhooks',
                        'message'           => 'Attention, webhook not properly configured!',
                        'message_class'     => 'no-webhook',
                        'webhook_button'   => true
                    ]
                );
                return $this->_toHtml();
            } else {
                // Webhook configured
                $element->setData('value', $callbackUrl);
                $element->setReadonly('readonly');

                $this->addData(
                    [
                        'element_html'      => $element->getElementHtml(),
                        'message'           => 'Your webhook is all set!',
                        'message_class'     => 'webhook-set',
                        'webhook_button'   => false
                    ]
                );
                return $this->_toHtml();
            }
        } catch (\Exception $e) {
            // Invalid secret key
            $element->setData('value', $callbackUrl);
            $element->setReadonly('readonly');

            $this->addData(
                [
                    'element_html'      => $element->getElementHtml(),
                    'webhook_button'   => false
                ]
            );
            return $this->_toHtml();
        }
    }

    /**
     * Return ajax url for set webhook button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('checkoutcom_magento2/system_config/webhook');
    }

    /**
     * Generate set webhook button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'webhook_button',
                'label' => __('Set Webhooks'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * Returns the controller url.
     *
     * @return string
     */
    abstract public function getControllerUrl();
}
