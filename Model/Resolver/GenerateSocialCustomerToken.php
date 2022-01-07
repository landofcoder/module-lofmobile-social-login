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

class GenerateSocialCustomerToken implements ResolverInterface
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
            if (empty($args['social_token'])) {
                throw new GraphQlInputException(__('"social_token" value should be specified'));
            }
            $token = $this->repository->login($args['social_token'], $args['type']);
        }
        return ['token' => $token];
    }
}
