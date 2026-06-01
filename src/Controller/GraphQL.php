<?php

namespace App\Controller;

use App\Graphql\InputTypes\CreateOrderInputType;
use App\Graphql\ObjectTypes\CategoryType;
use App\Graphql\ObjectTypes\MessageType;
use App\Graphql\ObjectTypes\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\GraphQL as GraphQLBase;
use RuntimeException;
use Throwable;

class GraphQL
{
    public static function buildSchema(EntityManager $entityManager): Schema
    {

        $categoryRepo = new CategoryRepository($entityManager);
        $productRepo = new ProductRepository($entityManager);
        $orderRepo = new OrderRepository($entityManager);

        // Reminder for myself : Complex types must be defined here::
        $categoryType = new CategoryType();
        $productType = new ProductType();

        $queryType = new ObjectType([
            "name" => "Query",
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'resolve' => fn() => 'Heyyooo!'
                ],
                'categories' => [
                    'type' => Type::listOf($categoryType),
                    'resolve' => function () use ($categoryRepo) {
                        return $categoryRepo->findAll();
                    }
                ],
                'product' => [
                    "type" => Type::listOf($productType),
                    'args' => [
                        'productId' => Type::string()
                    ],
                    'resolve' => function ($_, $args) use ($productRepo) {
                        return $productRepo->findById($args["productId"]);
                    }
                ],
                "products" => [
                    "type" => Type::listOf($productType),
                    'args' => [
                        'category' => Type::string()
                    ],
                    'resolve' => function ($_, $args) use ($productRepo) {
                        if ($args["category"] === "all") {
                            return $productRepo->findAll();
                        } else {
                            return $productRepo->findByCategory($args["category"]);
                        }
                    }
                ]
            ]
        ]);

        $messageType = new MessageType();

        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                'setMessage' => [
                    'type' => Type::string(),
                    'args' => [
                        'message' => Type::string(),
                    ],
                    'resolve' => function ($_, $args) {
                        return "Message received: " . $args['message'];
                    }
                ],
                'createOrder' => [
                    'type' => $messageType,
                    'args' => [
                        'input' => Type::nonNull(new CreateOrderInputType()),
                    ],
                    'resolve' => function ($_, $args) use ($orderRepo) {
                        try {
                            $orderData = $args['input'];

                            $cartItems = $orderData['items'] ?? null;
                            $email = $orderData['email'] ?? null;
                            $name = $orderData['name'] ?? null;
                            $address = $orderData['address'] ?? null;
                            $currencyId = $orderData['currencyId'] ?? null;

                            // --- Validation ---
                            if (!$cartItems || !is_array($cartItems) || count($cartItems) === 0) {
                                throw new UserError('Cart items are required and cannot be empty.', 400);
                            }
                            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                throw new UserError('Invalid email address.', 400);
                            }
                            if (!$name) {
                                throw new UserError('Name is required.', 400);
                            }
                            if (!$address) {
                                throw new UserError('Address is required.', 400);
                            }
                            if (!$currencyId) {
                                throw new UserError('Currency ID is required.', 400);
                            }

                            $orderRepo->createOrder($cartItems, $name, $email, $address, $currencyId);

                            return [
                                'message' => 'Order made',
                            ];
                        } catch (UserError $e) {

                            throw $e;
                        } catch (\Throwable $e) {

                            error_log($e->getMessage());

                            throw new UserError('Internal server error. Please try again later.', 400);
                        }
                    }
                ]
            ],
        ]);

        return new Schema(['query' => $queryType, 'mutation' => $mutationType]);
    }

    static public function handle(Schema $schema)
    {

        try {

            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                throw new RuntimeException('Failed to get php://input');
            }

            $input = json_decode($rawInput, true);
            $query = $input['query'];
            $variableValues = $input['variables'] ?? null;

            $rootValue = ['prefix' => 'You said: '];
            $result = GraphQLBase::executeQuery($schema, $query, $rootValue, null, $variableValues);
            $output = $result->toArray();
        } catch (Throwable $e) {
            $output = [
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ];
        }

        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output);
    }
}
