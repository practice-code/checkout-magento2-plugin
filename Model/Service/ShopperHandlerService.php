<?php

namespace CheckoutCom\Magento2\Model\Service;

class ShopperHandlerService
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */        
    protected $customerRepository;

    /**
     * ShopperHandlerService constructor
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    )
    {
        $this->customerSession = $customerSession;
        $this->customerRepository  = $customerRepository;
    }

    public function getCustomerData($filters = []) {
        if (isset($filters['id'])) {
            return $this->customerRepository->getById($filters['id']);
        }
        else if (isset($filters['email'])) {
            return $this->customerRepository->get(
                filter_var($filters['email'],
                FILTER_SANITIZE_EMAIL)
            );
        }
        else {
            $customerId = $this->customerSession->getCustomer()->getId();
            return $this->customerRepository->getById($customerId);
        }
    }

    public function isLoggedIn() {
        return $this->customerSession->isLoggedIn();
    }
}