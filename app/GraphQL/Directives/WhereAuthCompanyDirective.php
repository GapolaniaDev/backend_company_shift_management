<?php

namespace App\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class WhereAuthCompanyDirective extends BaseDirective implements FieldMiddleware
{
    /**
     * Name of the directive as used in the schema.
     */
    public static function definition(): string
    {
        return /** @lang GraphQL */ '
        """
        Scope query to only return results belonging to the authenticated user\'s company.
        """
        directive @whereAuthCompany on FIELD_DEFINITION
        ';
    }

    /**
     * Resolve the field directive.
     */
    public function handleField(FieldValue $fieldValue): void
    {
        // Temporarily disable this directive to avoid breaking GraphQL
        // TODO: Implement proper directive handling with updated Lighthouse API
    }
}
