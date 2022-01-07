<?php
/**
 * Landofcoder
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * https://landofcoder.com/terms
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category   Landofcoder
 * @package    Lof_DeliveryPerson
 * @copyright  Copyright (c) 2021 Landofcoder (https://www.landofcoder.com/)
 * @license    https://landofcoder.com/terms
 */

declare(strict_types=1);

namespace Lofmobile\SocialLogin\Model;

use Lofmobile\SocialLogin\Api\SocialLoginInterface;
use Lofmobile\SocialLogin\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Customer\Api\Data\CustomerExtensionFactory;

class SocialLoginRepository implements SocialLoginInterface
{
    /**
     * Token Model
     *
     * @var TokenModelFactory
     */
    private $tokenModelFactory;

    /**
     * @type StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @type CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerDataFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var CustomerExtensionFactory
     */
    protected $customerExtensionFactory;

    /**
     * @var Data
     *
     */
    protected $helperData;

    /**
     * Social constructor.
     * @param CustomerFactory $customerFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param TokenModelFactory $tokenModelFactory
     * @param Data $helperData
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        TokenModelFactory $tokenModelFactory,
        CustomerExtensionFactory $customerExtensionFactory,
        Data $helperData
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->storeManager = $storeManager;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->helperData = $helperData;
    }

    /**
     * jwt decode
     *
     * @param string $token
     * @return mixed|array|string|null
     */
    private function jwtDecode($token)
    {
        $splitToken = explode(".", $token);
        if (!isset($splitToken[1])) {
            return null;
        }
        $payloadBase64 = $splitToken[1]; // Payload is always the index 1
        $decodedPayload = json_decode(urldecode(base64_decode($payloadBase64)), true);
        return $decodedPayload;
    }

    /**
     * @inheritdoc
     */
    public function login($token, $type)
    {
        if (!$this->helperData->isEnabled()) {
            throw new CouldNotSaveException(__(
                'Could not call the login feature'
            ));
        }
        if ($type == "facebook") {
            $fields = "id,name,first_name,last_name,email,picture.type(large)";
            $url = 'https://graph.facebook.com/me/?fields='.$fields.'&access_token=' . $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result["email"])) {
                $firstName = $result["first_name"];
                $lastName = $result["last_name"];
                $email = $result["email"];
                $avatar = isset($result["picture"]) &&  isset($result["picture"]["data"]) ? $result["picture"]["data"]["url"] : "";
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
            } else {
                throw new InputMismatchException(
                    __("Your 'token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.")
                );
            }
        } elseif ($type == "google") {
            $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result["email"])) {
                $firstName = $result["given_name"];
                $lastName = $result["family_name"];
                $email = $result["email"];
                $avatar = $result["picture"];
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
            } else {
                throw new InputMismatchException(
                    __("Your 'token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Google app.")
                );
            }
        } elseif ($type == "sms") {
            $url = 'https://graph.accountkit.com/v1.3/me/?access_token=' . $token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result["phone"])) {
                $firstName = $result["phone"]["country_prefix"];
                $lastName = $result["phone"]["national_number"];
                $email = $result["phone"]["national_number"]."@landofcoder.com";
                $avatar = "";
                return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
            } else {
                throw new InputMismatchException(
                    __("Your 'token' did not return phone of the user. Without 'phone' user can't be logged in or registered. Get user phone extended permission while joining the app.")
                );
            }
        } elseif ($type == "firebase_sms") {
            $firstName = $token;
            $lastName = "landofcoder";
            $email = $token."@landofcoder.com";
            $avatar = "";
            return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
        } elseif ($type == "apple") {
            $decoded = $this->jwtDecode($token);
            if (!$decoded) {
                throw new InputMismatchException(
                    __("Your 'token' is invalid.")
                );
            }
			$email = $decoded["email"];
			$firstName = explode("@", $email)[0];
			$lastName = "user";
			$avatar = "";
            return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
        }
    }

    /**
     * @inheritdoc
     */
    public function appleLogin($token, $firstName, $lastName)
    {
        if (!$this->helperData->isEnabled()) {
            throw new CouldNotSaveException(__(
                'Could not call the login feature'
            ));
        }
        $decoded = $this->jwtDecode($token);
        if (!$decoded) {
            throw new InputMismatchException(
                __("Your 'token' is invalid.")
            );
        }
		$email = $decoded["email"];
		$firstName = isset($firstName) && $firstName != null && $firstName != "" ? $firstName : explode("@", $email)[0];
        $lastName = isset($lastName) && $lastName != null && $lastName != "" ? $lastName : "user";
        $avatar = "";
        return $this->createSocialLogin($firstName, $lastName, $email, $avatar);
    }

    /**
     * Create social login
     *
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $avatar
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createSocialLogin($firstName, $lastName, $email, $avatar = "")
    {
        $customer = $this->customerDataFactory->create();
        $customer->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setEmail($email)
                    ->setCustomAttribute('customer_avatar', $avatar);
        try {
            // If customer exists existing hash will be used by Repository
            $customer = $this->customerRepository->save($customer);
            $objectManager = ObjectManager::getInstance();
            $mathRandom = $objectManager->get('Magento\Framework\Math\Random');
            $newPasswordToken = $mathRandom->getUniqueHash();
            $accountManagement = $objectManager->get('Magento\Customer\Api\AccountManagementInterface');
            $accountManagement->changeResetPasswordLinkToken($customer, $newPasswordToken);
            $token = $this->tokenModelFactory->create()->createCustomerToken($customer->getId())->getToken();
            return $token;
        } catch (AlreadyExistsException $e) {
            //email is exist
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
            $customer->loadByEmail($email);
            $token = $this->tokenModelFactory->create()->createCustomerToken($customer->getId())->getToken();
            return $token;
        } catch (\Exception $e) {
            if ($customer->getId()) {
                $this->_registry->register('isSecureArea', true, true);
                $this->customerRepository->deleteById($customer->getId());
            }
            throw $e;
        }
    }
}
