<?php

/**
 * Copyright © landofcoder.com All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Lofmobile\SocialLogin\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Lofmobile\SocialLogin\Api\SocialLoginInterface;

class GenerateAppleCustomerToken implements ResolverInterface
{
    /**
     * @var SocialLoginInterface
     */
    private $repository;

    /**
     * @param SocialLoginInterface $repository
     */
    public function __construct(
        SocialLoginInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $token = "";
        if ($args) {
            if (empty($args['apple_token'])) {
                throw new GraphQlInputException(__('"apple_token" value should be specified'));
            }
            $first_name = isset($args['first_name']) ? $args['first_name'] : "";
            $last_name = isset($args['last_name']) ? $args['last_name'] : "";
            $token = $this->repository->appleLogin($args['apple_token'], $first_name, $last_name);
        }
        return ['token' => $token];
    }
}
