<?php
    /**
     * Project Name:    Wingman Verix - Type Normaliser
     * Created by:      Angel Politis
     * Creation Date:   Dec 22 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Processors namespace.
    namespace Wingman\Verix\Processors;

    # Import the following classes to the current scope.
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\Nodes\IntersectionNode;
    use Wingman\Verix\Nodes\KeyedStructNode;
    use Wingman\Verix\Nodes\SchemaRefNode;
    use Wingman\Verix\Nodes\StructNode;
    use Wingman\Verix\Nodes\TupleNode;
    use Wingman\Verix\Nodes\UnionNode;
    use Wingman\Verix\Registries\SchemaRegistry;

    /**
     * Responsible for normalising types by merging unions and intersections.
     * @package Wingman\Verix\Processors
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypeNormaliser {
        /**
         * The schema registry of a type normaliser.
         * @var SchemaRegistry
         */
        protected SchemaRegistry $schemaRegistry;

        /**
         * Creates a new type normaliser.
         * @param SchemaRegistry $registry The schema registry.
         */
        public function __construct (SchemaRegistry $registry) {
            $this->schemaRegistry = $registry;
        }
    
        /**
         * Removes duplicate types from an array of type nodes.
         * @param Node[] $types The array of type nodes.
         */
        protected function dedupe (array $types) : array {
            $seen = [];
            $out = [];
            foreach ($types as $type) {
                $key = $type->serialise();
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $out[] = $type;
                }
            }
            return $out;
        }

        /**
         * Normalises an intersection type by merging structs and flattening nested intersections.
         * @param IntersectionNode $intersection The intersection type to normalise.
         * @return Node The normalised type node.
         */
        protected function normaliseIntersection (IntersectionNode $intersection) : Node {
            # (1) Flatten nested intersections.
            $types = [];
            foreach ($intersection->getTypes() as $type) {
                $type = $this->normalise($type);
                if ($type instanceof IntersectionNode) array_push($types, ...$type->getTypes());
                else $types[] = $type;
            }
    
            $structs = [];
            $others  = [];
    
            # (2) Distinguish structs from other types.
            foreach ($types as $type) {
                if ($type instanceof StructNode) $structs[] = $type;
                else $others[] = $type;
            }
    
            # (3) Merge structs if there are multiple.
            if (count($structs) >= 2) {
                $merged = array_shift($structs);
                foreach ($structs as $struct) $merged = StructMerger::merge($merged, $struct);
                $others[] = $merged;
            }

            # (4) Merge all nodes back together.
            else $others = array_merge($others, $structs);
    
            return count($others) === 1 ? $others[0] : new IntersectionNode($others);
        }
    
        /**
         * Normalises a keyed struct type by normalising its fields and key/value types.
         * @param KeyedStructNode $struct The keyed struct type to normalise.
         * @return KeyedStructNode The normalised keyed struct node.
         */
        protected function normaliseKeyedStruct (KeyedStructNode $struct) : KeyedStructNode {
            # (1) Normalise each field's type.
            $newFields = [];
            foreach ($struct->getFields() as $name => $field) {
                $newFields[$name] = $field->withType($this->normalise($field->getType()));
            }

            # (2) Normalise the rest field's type, if any.
            $rest = $struct->getRest();
            $rest = $rest ? $rest->withType($this->normalise($rest->getType())) : null;

            # (3) Normalise key and value types.
            $keyType = $struct->getKeyType();
            $keyType = $keyType ? $this->normalise($keyType) : null;
            $valueType = $struct->getValueType();
            $valueType = $valueType ? $this->normalise($valueType) : null;

            return new KeyedStructNode(
                fields: $newFields,
                exact: $struct->isExact(),
                rest: $rest,
                keyType: $keyType,
                valueType: $valueType,
                keyOptional: $struct->isKeyOptional(),
                keyReadonly: $struct->isKeyReadonly()
            );
        }
    
        /**
         * Normalises a schema reference by retrieving the referenced schema and normalising it.
         * @param SchemaRefNode $ref The schema reference node.
         * @return Node The normalised type node.
         */
        protected function normaliseSchemaRef (SchemaRefNode $ref) : Node {
            $schema = $this->schemaRegistry->get($ref->getName());
            return $this->normalise($schema);
        }
    
        /**
         * Normalises a struct type by normalising its fields.
         * @param StructNode $struct The struct type to normalise.
         * @return StructNode The normalised struct node.
         */
        protected function normaliseStruct (StructNode $struct) : StructNode {
            # (1) Normalise each field's type.
            $newFields = [];
            foreach ($struct->getFields() as $name => $field) {
                $newFields[$name] = $field->withType($this->normalise($field->getType()));
            }

            # (2) Normalise the rest field's type, if any.
            $rest = $struct->getRest();
            $rest = $rest ? $rest->withType($this->normalise($rest->getType())) : null;

            return new StructNode($newFields, $struct->isExact(), $rest);
        }
    
        /**
         * Normalises a tuple type by normalising its items and rest type.
         * @param TupleNode $tuple The tuple type to normalise.
         * @return TupleNode The normalised tuple node.
         */
        protected function normaliseTuple (TupleNode $tuple) : TupleNode {
            # (1) Normalise each item type.
            $items = array_map(fn ($type) => $this->normalise($type), $tuple->getItems());

            # (2) Normalise the rest type, if any.
            $restType = $tuple->getRestType();
            $restType = $restType ? $this->normalise($restType) : null;
            
            return new TupleNode($items, $restType);
        }

        /**
         * Normalises a union type by flattening nested unions and removing duplicates.
         * @param UnionNode $union The union type to normalise.
         * @return Node The normalised type node.
         */
        protected function normaliseUnion (UnionNode $union) : Node {
            # (1) Flatten nested unions.
            $types = [];
            foreach ($union->getTypes() as $type) {
                $type = $this->normalise($type);
                if ($type instanceof UnionNode) array_push($types, ...$type->getTypes());
                else $types[] = $type;
            }

            # (2) Remove duplicate types.
            $types = $this->dedupe($types);

            return sizeof($types) === 1 ? $types[0] : new UnionNode($types);
        }

        /**
         * Normalises a type node.
         * @param Node $type The type node to normalise.
         * @return Node The normalised type node.
         */
        public function normalise (Node $type) : Node {
            return match (true) {
                $type instanceof UnionNode => $this->normaliseUnion($type),
                $type instanceof IntersectionNode => $this->normaliseIntersection($type),
                $type instanceof KeyedStructNode => $this->normaliseKeyedStruct($type),
                $type instanceof StructNode => $this->normaliseStruct($type),
                $type instanceof TupleNode => $this->normaliseTuple($type),
                $type instanceof SchemaRefNode => $this->normaliseSchemaRef($type),
                default => $type
            };
        }
    }
?>