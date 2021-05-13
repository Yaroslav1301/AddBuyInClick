<?php
namespace Kozar\AddBuyInClick\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_BUY_IN_CLICK = 'settings/';
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $customerResource;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quote;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var Quote
     */
    protected $quoteResource;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    protected $orderResource;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param Quote $quoteResource
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\Sales\Model\ResourceModel\Order $orderResource
    ) {
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->customerResource = $customerResource;
        $this->quoteResource = $quoteResource;
        $this->orderResource = $orderResource;
        parent::__construct($context);
    }

    /**
     * @param $orderInfo
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * Creating order in 1 click programmatically
     *
     */
    public function createOrder($orderInfo)
    {
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        /**
         *  load customer by email address
         */
        $customer->loadByEmail($orderInfo['email']);
        /**
         * Check if not registered customer than creat
         */
        if (!$customer->getId()) {
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderInfo['address']['firstname'])
                ->setLastname($orderInfo['address']['lastname'])
                ->setEmail($orderInfo['email'])
                ->setPassword($orderInfo['email']);
            $this->customerResource->save($customer);
        }
        $quote = $this->quote->create(); //Create object of quote
        $quote->setStore($store); //set store for our quote
        /**
         * For registered  customer
         */
        $customer = $this->customerRepository->getById($customer->getId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); //Assign quote to customer

        /**
         * Add items in quote
         */
        foreach ($orderInfo['items'] as $item) {
            $product = $this->productRepository->getById($item['product_id']);
                $quote->addProduct($product, (int)($item['qty']));
        }

        /**
         * Set Billing and shipping Address to quote
         */
        $quote->getBillingAddress()->addData($orderInfo['address']);
        $quote->getShippingAddress()->addData($orderInfo['address']);

        /**
         * Set shipping method
         */
        $payment_method = $this->getGeneralConfig("select_payment");
        $delivery_method = $this->getGeneralConfig("select_delivery");
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($delivery_method);
        $quote->setPaymentMethod($payment_method);
        $quote->setInventoryProcessed(false); //decrease item stock equal to qty
        $this->quoteResource->save($quote);

        /**
         * Set Sales Order Payment, We have taken check/money order
         */
        $quote->getPayment()->importData(['method' => $payment_method]);

        /**
         * Collect Quote Totals & Save
         */
        $quote->collectTotals()->save();
        // Create Order From Quote Object
        $order = $this->quoteManagement->submit($quote);
        /**
         * Send order email to customer email id
         */
        $this->orderSender->send($order);
        /**
         * Get order real id from order
         */
        $orderId = $order->getIncrementId();
        if ($orderId) {
            $order->setStatus('checking');
            $this->orderResource->save($order);
            $result['success'] = $orderId;
            $message = __('Your order id '.$orderId);
            $this->messageManager->addSuccessMessage($message);
        } else {
            $result = ['error' => true, 'message' => 'Something went wrong'];
        }
        return $result;
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {

        return $this->getConfigValue(self::XML_PATH_BUY_IN_CLICK .'general/'. $code, $storeId);
    }
}
