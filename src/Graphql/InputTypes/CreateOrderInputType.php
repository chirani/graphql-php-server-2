<?php

namespace App\Graphql\InputTypes;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class CreateOrderInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'CreateOrderInput',
            'fields' => function () {
                return [
                    'currencyId' => Type::nonNull(Type::string()),
                    'total' => Type::nonNull(Type::float()),
                    'message' => Type::string(),
                    'items' => Type::listOf(new OrderItemInputType()),
                ];
            }
        ]);
    }
}
