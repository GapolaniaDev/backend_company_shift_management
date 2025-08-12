<?php

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Type\Definition\ScalarType;

class JSON extends ScalarType
{
    public string $name = 'JSON';
    
    public ?string $description = 'A JSON string';

    /**
     * Serialize JSON data to be returned to client.
     *
     * @param mixed $value
     * @return string
     * @throws Error
     */
    public function serialize($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        if ($value === null) {
            return 'null';
        }

        throw new Error('Cannot serialize value as JSON: ' . var_export($value, true));
    }

    /**
     * Parse value received from client.
     *
     * @param mixed $value
     * @return mixed
     * @throws Error
     */
    public function parseValue($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Error('Cannot parse JSON: ' . json_last_error_msg());
            }
            return $decoded;
        }

        return $value;
    }

    /**
     * Parse literal value received from query.
     *
     * @param GraphQL\Language\AST\Node $valueNode
     * @param array|null $variables
     * @return mixed
     * @throws Error
     */
    public function parseLiteral(\GraphQL\Language\AST\Node $valueNode, array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            $decoded = json_decode($valueNode->value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Error('Cannot parse JSON literal: ' . json_last_error_msg());
            }
            return $decoded;
        }

        throw new Error('Cannot parse non-string value as JSON: ' . $valueNode->kind);
    }
}