<?php
namespace Vendor\CartWebhook\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class AddToCartObserver implements ObserverInterface
{
    protected $curl;
    protected $logger;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $quoteItem = $observer->getEvent()->getQuoteItem();
            $product = $quoteItem->getProduct();

            $data = [
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'qty' => $quoteItem->getQty(),
                'price' => $product->getPrice()
            ];

            $url = 'https://webhook.site/38d648bb-e0ea-4c96-bb8b-f765984ebf02';

            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, json_encode($data));

        } catch (\Exception $e) {
            $this->logger->error('Webhook error: ' . $e->getMessage());
        }
    }
}

