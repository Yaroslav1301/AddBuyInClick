<?php

namespace Kozar\AddBuyInClick\Controller\OneClick;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Class Product
 * @package Kozar\AddBuyInClick\Controller\OneClick
 * Class add order have made by in click
 */

class Product extends Action
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerFactory;

    /**
     * @var \Kozar\AddBuyInClick\Helper\Data $helper
     */
    protected $helper;



    /**
     * Product constructor.
     * @param \Kozar\AddBuyInClick\Helper\Data $helper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository,
     * @param Context $context
     */

    public function __construct(
        \Kozar\AddBuyInClick\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        Context $context
    ) {
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    public function getRightSku($sku, $selectedOptions) : string
    {
        if (!empty($selectedOptions)) {
            $size = preg_replace('#(.+)([A-Z][a-z]+)#', "$1", $selectedOptions);
            $color = preg_replace('#(.+)([A-Z][a-z]+)#', "$2", $selectedOptions);
            return $sku."-".$size."-".$color;
        } else {
            return $sku;
        }
    }

    public function getFullCustomerData($email, $name, $idProduct, $qty, $number): array
    {
        $customerCollection = $this->customerFactory->create();
        $customerCollection->addFieldToFilter('email', ['eq' => $email]);
        if (!empty($customerCollection->getData())) {
            $customerData = $customerCollection->getData()[0];
            $firstName = $customerData['firstname'];
            $lastName = $customerData['lastname'];
        } else {
            $firstName = $name;
            $lastName = "$name";
        }
        return [
            'currency_id'  => 'USD',
            'email'        => $email,
            'address' =>[
                'firstname'  => $firstName,
                'lastname'   => $lastName,
                'prefix' => '',
                'suffix' => '',
                'street' => 'Times Square',
                'city' => 'New York',
                'country_id' => 'US',
                'region' => 'New York',
                'region_id' => '12',
                'postcode' => '10001',
                'telephone' => $number,
                'fax' => $number,
                'save_in_address_book' => 1
            ],
            'items'=>
                [
                    [
                        'product_id'=> (int)$idProduct,
                        'qty'=> (int)$qty
                    ]
                ]
        ];
    }

    public function execute()
    {
        $params = $this->_request->getParams();
        $number = $params['number'];
        $name = $params['name'];
        $email = $params['email'];
        $qty = $params['qty'];
        $selectedOptions = $params['selected'];
        $sku = $this->getRightSku($params['sku'], $selectedOptions);
        $product = $this->productRepository->get($sku);
        $productId = $product->getId();
        $dataOrder = $this->getFullCustomerData($email, $name, $productId, $qty, $number);
        $this->helper->createOrder($dataOrder);
    }
}
