<?php
    /*/
     * Project Name:    Wingman — Verix — Type Class Locator
     * Created by:      Angel Politis
     * Creation Date:   Feb 19 2026
     * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix namespace.
    namespace Wingman\Verix;

    # Import the following classes to the current scope.
    use FilesystemIterator;
    use ReflectionClass;
    use Wingman\Verix\Types\Type;

    /**
     * Locates type classes in a given directory and namespace.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypeClassLocator {
        /**
         * The default cache location for compiled type files.
         * @var string
         */
        public const DEFAULT_CACHE_LOCATION = __DIR__ . "/../cache";

        /**
         * The default cache file for compiled type files.
         * @var string
         */
        public const DEFAULT_CACHE_FILE = self::DEFAULT_CACHE_LOCATION . "/types.php";

        /**
         * The configuration key for auto-refreshing the cache.
         * @var string
         */
        public const AUTO_REFRESH_CACHE = "autoRefreshCache";

        /**
         * The configuration key for auto-refreshing the cache.
         * @var string
         */
        public const ALLOW_CACHING = "allowCaching";

        /**
         * The configuration array for a type class locator.
         * @var array
         */
        protected array $config = [];

        /**
         * Creates a new type class locator.
         * @param array $config The configuration array (optional).
         */
        public function __construct (array $config = []) {
            $this->config = $config;
        }

        /**
         * Refreshes the cache by re-scanning the directory for type classes and updating the cache file.
         * @param string $directory The directory to search.
         * @param string|null $namespace The namespace to use (optional).
         * @param string $path The path to the cache file.
         * @return array An array of type class names.
         */
        protected function refresh (string $directory, ?string $namespace, string $path) : array {
            if (!($this->config[self::AUTO_REFRESH_CACHE] ?? true)) {
                return $this->getClassesFromCache($directory);
            }
            $classes = $this->findTypeClasses($directory, $namespace);
            $this->cache($classes, $path);
            return $classes;
        }
        
        /**
         * Caches the given type classes to a file.
         * @param array $classes An array of type class names to cache.
         * @param string|null $targetPath The target path for the cache file (optional).
         * @return static Returns the current instance for chaining.
         */
        public function cache (array $classes, ?string $targetPath = null) : static {
            if (!($this->config[self::ALLOW_CACHING] ?? true)) {
                return $this;
            }
            $targetPath = $targetPath ?? self::DEFAULT_CACHE_FILE;
            $data = "<?php\n\nreturn " . var_export($classes, true) . ';';
            file_put_contents($targetPath, $data);
            return $this;
        }

        /**
         * Finds all type classes in a given directory.
         * @param string $directory The directory to search.
         * @param string|null $namespace The namespace to use (optional).
         * @return array An array of type class names.
         */
        public function findTypeClasses (string $directory, ?string $namespace = null) : array {
            if (!is_dir($directory)) return [];

            $classes = [];

            $typesNamespace = $namespace
                ? str_replace(['.', '/'], '\\', $namespace)
                : (new ReflectionClass(Type::class))->getNamespaceName();

            $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $file) {
                if ($file->getExtension() !== "php") continue;

                $className = "$typesNamespace\\" . $file->getBasename(".php");

                if (!class_exists($className)) continue;
                
                # Reject abstract classes and classes that are not subclasses of Type.
                $reflection = new ReflectionClass($className);
                if (!$reflection->isSubclassOf(Type::class) || $reflection->isAbstract()) continue;

                $classes[] = $className;
            }

            return $classes;
        }

        /**
         * Retrieves the cache file path for a given directory.
         * @param string|null $directory The directory to derive the cache file from (optional).
         * @return string The cache file path.
         */
        public function getCachePath (?string $directory = null) : string {
            return $directory
                ? self::DEFAULT_CACHE_LOCATION . DIRECTORY_SEPARATOR . md5(trim($directory, "/ \n\r\t\v\0")) . ".php"
                : self::DEFAULT_CACHE_FILE;
        }

        /**
         * Retrieves cached type classes from a cache file.
         * @param string|null $directory The directory to derive the cache file from (optional).
         * @return array An array of cached type class names.
         */
        public function getClassesFromCache (?string $directory = null) : array {
            $cachePath = $this->getCachePath($directory);
            if (!file_exists($cachePath)) return [];
            return include $cachePath;
        }

        /**
         * Locates type classes in a given directory and namespace, using caching for performance.
         * @param string|null $directory The directory to search.
         * @param string|null $namespace The namespace to use (optional).
         * @return array An array of type class names.
         */

        public function locate (?string $directory = null, ?string $namespace = null) : array {
            $cachePath = $this->getCachePath($directory);
            if (file_exists($cachePath)) {
                if ($this->config[static::AUTO_REFRESH_CACHE] ?? false) {
                    if (filemtime($directory) > filemtime($cachePath)) {
                        return $this->refresh($directory, $namespace, $cachePath);
                    }
                }
                return $this->getClassesFromCache($directory);
            }
            return $this->refresh($directory, $namespace, $cachePath);
        }
    }
?>