<?php
namespace Lofmobile\SocialLogin\Api;

/**
 * @api
 */
interface SocialLoginInterface
{
    /**
     * Social Login
     *
     * @param string $token
     * @param string $type
     * @return string Token created
     * @throws \Magento\Framework\Exception\AuthenticationException|Magento\Framework\Exception\State\InputMismatchException|Magento\Framework\Exception\CouldNotSaveException
     */
    public function login($token, $type);

    /**
     * Apple Login
     *
     * @param string $token
     * @param string|null $firstName
     * @param string|null $lastName
     * @return string Token created
     * @throws \Magento\Framework\Exception\AuthenticationException|Magento\Framework\Exception\State\InputMismatchException|Magento\Framework\Exception\CouldNotSaveException
     */
    public function appleLogin($token, $firstName = null, $lastName = null);
}
