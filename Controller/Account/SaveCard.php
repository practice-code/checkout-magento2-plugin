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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Controller\Account;

use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Class SaveCard
 */
class SaveCard extends Action
{
    /**
     * $messageManager field
     *
     * @var ManagerInterface $messageManager
     */
    public $messageManager;
    /**
     * $jsonFactory field
     *
     * @var JsonFactory $jsonFactory
     */
    public $jsonFactory;
    /**
     * $urlInterface field
     *
     * @var UrlInterface $urlInterface
     */
    public $urlInterface;
    /**
     * $vaultHandler field
     *
     * @var VaultHandlerService $vaultHandler
     */
    public $vaultHandler;

    /**
     * SaveCard constructor
     *
     * @param Context             $context
     * @param ManagerInterface    $messageManager
     * @param JsonFactory         $jsonFactory
     * @param UrlInterface        $urlInterface
     * @param VaultHandlerService $vaultHandler
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        JsonFactory $jsonFactory,
        UrlInterface $urlInterface,
        VaultHandlerService $vaultHandler
    ) {
        parent::__construct($context);

        $this->messageManager = $messageManager;
        $this->jsonFactory    = $jsonFactory;
        $this->urlInterface   = $urlInterface;
        $this->vaultHandler   = $vaultHandler;
    }

    /**
     * Handles the controller method.
     *
     * @return Json
     * @throws Exception
     */
    public function execute()
    {
        // Prepare the parameters
        $success        = false;
        $url            = $this->urlInterface->getUrl('vault/cards/listaction');
        $requestContent = explode("=", $this->getRequest()->getContent());
        if (isset($requestContent[1])) {
            $ckoCardToken = $requestContent[1];
        }

        // Process the request
        if ($this->getRequest()->isAjax() && !empty($ckoCardToken)) {
            // Save the card
            $result = $this->vaultHandler->setCardToken($ckoCardToken)
                ->setCustomerId()
                ->setCustomerEmail()
                ->authorizeTransaction();

            // Test the 3DS redirection case
            if (isset($result->response->_links['redirect']['href'])) {
                return $this->jsonFactory->create()->setData([
                    'success' => true,
                    'url'     => $result->response->_links['redirect']['href']
                ]);
            } else {
                // Try to save the card
                $success = $result->saveCard();
            }
        }

        // Prepare the response UI message
        if ($success) {
            $this->messageManager->addSuccessMessage(
                __('The payment card has been stored successfully.')
            );
        } else {
            $this->messageManager->addErrorMessage(
                __('The card could not be saved.')
            );
        }

        // Build the AJAX response
        return $this->jsonFactory->create()->setData([
            'success' => $success,
            'url'     => $url
        ]);
    }
}
