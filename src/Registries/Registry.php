<?php
    /*/
	 * Project Name:    Wingman — Verix — Registry
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Registries namespace.
    namespace Wingman\Verix\Registries;

    # Import the following classes to the current scope.
    use Wingman\Verix\TypeClassLocator;

    /**
     * Represents the global schema registry.
     * @package Wingman\Verix\Registries
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Registry {
        /**
         * The name of the default registry.
         * @var string
         */
        public const DEFAULT_NAME = "default";

        /**
         * The location of the default types directory.
         * @var string
         */
        public const DEFAULT_TYPES_LOCATION = __DIR__ . "/../Types";

        /**
         * The cache that holds registries by name.
         * @var array<string, static>
         */
        protected static array $cache = [];

        /**
         * The name of a registry.
         * @var string|null
         */
        protected ?string $name = null;

        /**
         * The configuration array for a registry.
         * @var array
         */
        protected array $config = [];

        /**
         * The class registry.
         * @var ClassRegistry|null
         */
        protected ?ClassRegistry $classRegistry = null;

        /**
         * The primitive registry.
         * @var PrimitiveRegistry|null
         */
        protected ?PrimitiveRegistry $primitiveRegistry = null;

        /**
         * The schema registry.
         * @var SchemaRegistry|null
         */
        protected ?SchemaRegistry $schemaRegistry = null;

        /**
         * The type file locator.
         * @var TypeClassLocator|null
         */
        protected ?TypeClassLocator $typeClassLocator = null;

        /**
         * Creates a new registry.
         * @param string|null $name The name of the registry (optional).
         * @param array|null $config The configuration array (optional).
         */
        public function __construct (?string $name = null, array $config = []) {
            if ($name !== null) {
                $this->name = $name;
                static::$cache[$name] = $this;
            }
            $this->config = $config;
        }

        /**
         * Gets a registry by name, or the default registry if no name is provided.
         * @param string|null $name The name of the registry (optional).
         * @return static The registry instance.
         */
        public static function get (?string $name = null) : static {
            $name = $name ?? self::DEFAULT_NAME;
            if (!isset(static::$cache[$name])) new static($name);
            return static::$cache[$name];
        }

        /**
         * Registers the default primitive types.
         */
        protected function registerDefaults () : void {
            $this->registerTypes(self::DEFAULT_TYPES_LOCATION);
        }

        /**
         * Gets the class registry.
         * @return ClassRegistry The class registry.
         */
        public function getClassRegistry () : ClassRegistry {
            if ($this->classRegistry === null) {
                $this->classRegistry = new ClassRegistry();
            }
            return $this->classRegistry;
        }

        /**
         * Gets the primitive registry.
         * @return PrimitiveRegistry The primitive registry.
         */
        public function getPrimitiveRegistry () : PrimitiveRegistry {
            if ($this->primitiveRegistry === null) {
                $this->primitiveRegistry = new PrimitiveRegistry();
                $this->registerDefaults();
            }
            return $this->primitiveRegistry;
        }

        /**
         * Gets the schema registry.
         * @return SchemaRegistry The schema registry.
         */
        public function getSchemaRegistry () : SchemaRegistry {
            if ($this->schemaRegistry === null) {
                $this->schemaRegistry = new SchemaRegistry();
            }
            return $this->schemaRegistry;
        }

        /**
         * Registers all type classes in a given directory.
         * @param string $directory The directory to search.
         * @param string|null $namespace The namespace to use (optional).
         */
        public function registerTypes (string $directory, ?string $namespace = null) : void {
            if ($this->typeClassLocator === null) {
                $this->typeClassLocator = new TypeClassLocator($this->config);
            }
            foreach ($this->typeClassLocator->locate($directory, $namespace) as $class) {
                $this->getPrimitiveRegistry()->register($class);
            }
        }
    }
?>