<?php

namespace SafeCharge\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use SafeCharge\Api\Exception\ConfigurationException;
use SafeCharge\Api\Exception\ConnectionException;
use SafeCharge\Api\Exception\ResponseException;
use SafeCharge\Api\Exception\ValidationException;
use SafeCharge\Api\Service\PaymentService;

/**
 * Class PaymentServiceTest
 * @package SafeCharge\Tests
 */
class PaymentServiceTest extends TestCase
{
    /**
     * @var PaymentService
     */
    private $service;

    /**
     * PaymentServiceTest constructor.
     * @throws ConfigurationException
     */
    public function __construct()
    {
        parent::__construct();

        $this->service = new PaymentService(TestCaseHelper::getClient());
    }

    /**
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     */
    public function testCreatePayment()
    {
        $response = $this->service->createPayment([
            'currency'       => SimpleData::getCurrency(),
            'amount'         => SimpleData::getAmount(),
            'userTokenId'    => TestCaseHelper::getUserTokenId(),
            'paymentOption'  => [
                'card' => SimpleData::getCardData()
            ],
            'billingAddress' => SimpleData::getBillingAddress(),
            'deviceDetails'  => SimpleData::getDeviceDetails()
        ]);
        $this->assertEquals('SUCCESS', $response['status']);
    }

    /**
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     */
    public function testInitPayment()
    {
        $response = $this->service->initPayment([
            'currency'       => SimpleData::getCurrency(),
            'amount'         => SimpleData::getAmount(),
            'userTokenId'    => TestCaseHelper::getUserTokenId(),
            'paymentOption'  => [
                'card' => SimpleData::getCardData()
            ],
            'billingAddress' => SimpleData::getBillingAddress(),
            'deviceDetails'  => SimpleData::getDeviceDetails()
        ]);
        $this->assertEquals('SUCCESS', $response['status']);
    }

    /**
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     * @run ./vendor/phpunit/phpunit/phpunit --filter testAuthorize3d ./tests/PaymentServiceTest.php
     */
    public function testAuthorize3d()
    {
        $paramsAuthorize3d = $paramsInitPayment = [
            'currency'       => SimpleData::getCurrency(),
            'amount'         => SimpleData::getAmount(),
            'paymentOption'  => [
                'card' => SimpleData::getCardData()
            ],
            'billingAddress' => SimpleData::getBillingAddress(),
            'deviceDetails'  => SimpleData::getDeviceDetails(),
        ];

        $paramsInitPayment['userTokenId'] = TestCaseHelper::getUserTokenId();
        $initPaymentResponse = $this->service->initPayment($paramsInitPayment);

        $paramsAuthorize3d['relatedTransactionId'] = $initPaymentResponse['transactionId'];
        $paramsAuthorize3d['sessionToken'] = $initPaymentResponse['sessionToken'];
        $authorize3dResponse = $this->service->authorize3d($paramsAuthorize3d);

        $this->assertEquals('SUCCESS', $authorize3dResponse['status']);
    }

    /**
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     * @run ./vendor/phpunit/phpunit/phpunit --filter testVerify3d ./tests/PaymentServiceTest.php
     */
    public function testVerify3d()
    {
        $paramsVerify3d = $paramsAuthorize3d = $paramsInitPayment = [
            'currency'       => SimpleData::getCurrency(),
            'amount'         => SimpleData::getAmount(),
            'paymentOption'  => [
                'card' => SimpleData::getCardDataVerify3d()
            ],
            'billingAddress' => SimpleData::getBillingAddress(),
        ];

        $paramsInitPayment['userTokenId'] = TestCaseHelper::getUserTokenId();
        $paramsInitPayment['deviceDetails'] = SimpleData::getDeviceDetails();
        $initPaymentResponse = $this->service->initPayment($paramsInitPayment);

        $paramsAuthorize3d['relatedTransactionId'] = $initPaymentResponse['transactionId'];
        $paramsAuthorize3d['deviceDetails'] = SimpleData::getDeviceDetails();
        $paramsAuthorize3d['sessionToken'] = $initPaymentResponse['sessionToken'];
        $paramsAuthorize3d['paymentOption']['card']['threeD'] = SimpleData::getCardThreeD();
        $authorize3dResponse = $this->service->authorize3d($paramsAuthorize3d);

        $paramsVerify3d['relatedTransactionId'] = $authorize3dResponse['transactionId'];
        $paramsVerify3d['sessionToken'] = $authorize3dResponse['sessionToken'];
        $verify3dResponse = $this->service->verify3d($paramsVerify3d);

        $this->assertEquals('SUCCESS', $verify3dResponse['status']);
    }

    /**
     * @return mixed
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     */
    public function testOpenOrder()
    {
        $params = [
            'userTokenId'       => TestCaseHelper::getUserTokenId(),
            'clientUniqueId'    => '',
            'clientRequestId'   => '',
            'currency'          => SimpleData::getCurrency(),
            'amount'            => SimpleData::getAmount(),
            'amountDetails'     => SimpleData::getAmountDetails(),
            'items'             => SimpleData::getItems(),
            'deviceDetails'     => SimpleData::getDeviceDetails(),
            'userDetails'       => SimpleData::getUserDetails(),
            'shippingAddress'   => SimpleData::getShippingAddress(),
            'billingAddress'    => SimpleData::getBillingAddress(),
            'dynamicDescriptor' => SimpleData::getDynamicDescriptor(),
            'merchantDetails'   => SimpleData::getMerchantDetails(),
            'addendums'         => SimpleData::getAddEndUms(),
        ];

        $response = $this->service->openOrder($params);
        $this->assertContains('orderId', $response);
        return $response['orderId'];
    }

    /**
     * @throws Exception
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     */
    public function testSettleTransaction()
    {

        $transactionData = TestCaseHelper::createAndReturnTransaction(true);

        $dynamicDescriptor = SimpleData::getDynamicDescriptor();

        $params = [
            'clientRequestId'         => '100',
            'clientUniqueId'          => '12345',
            'amount'                  => "9.0",
            'currency'                => SimpleData::getCurrency(),
            'relatedTransactionId'    => $transactionData['transactionId'],
            'authCode'                => $transactionData['authCode'],
            'descriptorMerchantName'  => $dynamicDescriptor['merchantName'],
            'descriptorMerchantPhone' => $dynamicDescriptor['merchantPhone'],
            'comment'                 => 'some comment',
            'urlDetails'              => SimpleData::getUrlDetails(true),
        ];

        $response = $this->service->settleTransaction($params);
        $this->assertEquals('SUCCESS', $response['status']);
    }


    /**
     * @throws Exception
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     */
    public function testRefundTransaction()
    {
        $transactionData = TestCaseHelper::createAndReturnTransaction(false);

        $params = [
            'clientRequestId'      => '100',
            'clientUniqueId'       => '12345',
            'amount'               => SimpleData::getAmount(),
            'currency'             => SimpleData::getCurrency(),
            'relatedTransactionId' => $transactionData['transactionId'],
            'authCode'             => $transactionData['authCode'],
            'comment'              => 'some comment',
            'urlDetails'           => SimpleData::getUrlDetails(true),
        ];

        $response = $this->service->refundTransaction($params);
        $this->assertEquals('SUCCESS', $response['status']);
    }

    /**
     * @throws Exception
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     */
    public function testVoidTransaction()
    {
        $transactionData = TestCaseHelper::createAndReturnTransaction(false);

        $dynamicDescriptor = SimpleData::getDynamicDescriptor();

        $params = [
            'clientRequestId'         => '100',
            'clientUniqueId'          => '12345',
            'amount'                  => $transactionData['partialApprovalDetails']['amountInfo']['processedAmount'],
            'currency'                => SimpleData::getCurrency(),
            'relatedTransactionId'    => $transactionData['transactionId'],
            'authCode'                => $transactionData['authCode'],
            'descriptorMerchantName'  => $dynamicDescriptor['merchantName'],
            'descriptorMerchantPhone' => $dynamicDescriptor['merchantPhone'],
            'comment'                 => 'some comment',
            'urlDetails'              => SimpleData::getUrlDetails(true),
        ];

        $response = $this->service->voidTransaction($params);
        $this->assertEquals('SUCCESS', $response['status']);
    }

    /**
     * @throws Exception
     * @throws ConnectionException
     * @throws ResponseException
     * @throws ValidationException
     * @run ./vendor/phpunit/phpunit/phpunit --filter testGetPaymentStatus ./tests/PaymentServiceTest.php
     */
    public function testGetPaymentStatus()
    {
        $createPayment = $this->service->createPayment([
            'currency'       => SimpleData::getCurrency(),
            'amount'         => SimpleData::getAmount(),
            'userTokenId'    => TestCaseHelper::getUserTokenId(),
            'paymentOption'  => [
                'card' => SimpleData::getCardData()
            ],
            'billingAddress' => SimpleData::getBillingAddress(),
            'deviceDetails'  => SimpleData::getDeviceDetails()
        ]);

        $params = [
            'sessionToken' => $createPayment['sessionToken']
        ];

        $response = $this->service->getPaymentStatus($params);
        $this->assertEquals('SUCCESS', $response['status']);
    }
}
