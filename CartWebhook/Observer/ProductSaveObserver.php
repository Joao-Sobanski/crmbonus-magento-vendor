<?php
namespace Vendor\CartWebhook\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;

class ProductSaveObserver implements ObserverInterface
{
    protected $curl;
    protected $logger;
    protected $storeManager;
    protected $adminSession;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        AdminSession $adminSession
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->adminSession = $adminSession;
    }

    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();

            // Informações da loja
            $store = $this->storeManager->getStore();

            // Informações do admin
            $user = $this->adminSession->getUser();
            $adminData = [
                'username' => $user ? $user->getUsername() : null,
                'email'    => $user ? $user->getEmail() : null
            ];

            // Dados do produto
            $data = [
                'action'         => $product->isObjectNew() ? 'create' : 'edit',
                'product_id'     => $product->getId(),
                'sku'            => $product->getSku(),
                'name'           => $product->getName(),
                'price'          => $product->getPrice(),
                'status'         => $product->getStatus(),
                'type_id'        => $product->getTypeId(),
                'description'    => $product->getDescription(),
                'categories'     => $product->getCategoryIds(),
                'store_id'       => $store->getId(),
                'store_name'     => $store->getName(),
                'base_url'       => $store->getBaseUrl(),
                'admin_username' => $adminData['username'],
                'admin_email'    => $adminData['email'],
                'datetime'       => date('c')
            ];

            // URL diferente para criação ou edição (opcional)
            if ($product->isObjectNew()) {
                $url = 'https://webhook.site/38d648bb-e0ea-4c96-bb8b-f765984ebf02'; // criação
            } else {
                $url = 'https://webhook.site/38d648bb-e0ea-4c96-bb8b-f765984ebf02'; // edição (pode mudar)
            }

            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, json_encode($data));
        } catch (\Exception $e) {
            $this->logger->error('Product webhook error: ' . $e->getMessage());
        }
    }
}