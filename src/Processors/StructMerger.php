<?php
    /*/
	 * Project Name:    Wingman — Verix — Struct Merger
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 22 2025
	 * Last Modified:   Dec 24 2025
    /*/

    # Use the Verix.Processors namespace.
    namespace Wingman\Verix\Processors;

    # Import the following classes to the current scope.
    use Wingman\Verix\Nodes\IntersectionNode;
    use Wingman\Verix\Nodes\KeyedStructNode;
    use Wingman\Verix\Nodes\StructNode;
    use Wingman\Verix\Specs\RestField;
    use Wingman\Verix\Specs\StructField;

    /**
     * Responsible for merging two struct types.
     * @package Wingman\Verix\Processors
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class StructMerger {
        /**
         * Merge two structs (StructType or KeyedStructNode) into one.
         * Fixed fields are merged using intersections if they exist in both.
         * Dynamic keys and rest types are also merged if present.
         */
        public static function merge (StructNode $a, StructNode $b) : StructNode {
            # (1) Merge the fixed fields of the structs.
            $fields = $a->getFields();
            foreach ($b->getFields() as $name => $fieldB) {
                if (!isset($fields[$name])) {
                    $fields[$name] = $fieldB;
                }
                else {
                    $fieldA = $fields[$name];
                    $fields[$name] = new StructField(
                        type: new IntersectionNode([$fieldA->getType(), $fieldB->getType()]),
                        optional: $fieldA->isOptional() && $fieldB->isOptional(),
                        readonly: $fieldA->isReadonly() || $fieldB->isReadonly(),
                        default: $fieldB->getDefault() ?? $fieldA->getDefault()
                    );
                }
            }

            # (2) Merge dynamic key types if both structs are keyed.
            $keyType = $a instanceof KeyedStructNode ? $a->getKeyType() : null;
            $valueType = $a instanceof KeyedStructNode ? $a->getValueType() : null;
            $keyOptional = $a instanceof KeyedStructNode ? $a->isKeyOptional() : false;

            if ($b instanceof KeyedStructNode && $b->getKeyType() && $b->getValueType()) {
                # Merge the key types.
                $keyType = $keyType ? new IntersectionNode([$keyType, $b->getKeyType()]) : $b->getKeyType();
                
                # Merge the value types.
                $valueType = $valueType ? new IntersectionNode([$valueType, $b->getValueType()]) : $b->getValueType();

                # The key must be optional if either is optional.
                $keyOptional = $keyOptional || $b->isKeyOptional();
            }

            # (3) Merge rest types if both structs have them.
            $rest = $a instanceof KeyedStructNode ? $a->getRest() : null;
            if ($b instanceof KeyedStructNode && $b->getRest()) {
                $restType = $rest
                    ? new IntersectionNode([$rest->getType(), $b->getRest()->getType()])
                    : $b->getRest()->getType();

                $optional = $rest
                    ? $rest->isOptional() || $b->getRest()->isOptional()
                    : $b->getRest()->isOptional();
                
                $rest = new RestField($restType, $optional);
            }

            # (4) Decide the kind of struct to return.
            if ($keyType || $valueType || ($restType ?? null)) {
                return new KeyedStructNode(
                    fields: $fields,
                    keyType: $keyType,
                    valueType: $valueType,
                    keyOptional: $keyOptional,
                    rest: $rest
                );
            }

            return new StructNode($fields);
        }
    }
?>