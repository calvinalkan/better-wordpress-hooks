<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Functions {

        use Closure;
        use Illuminate\Support\Arr;
        use Illuminate\Support\Str;
        use ReflectionFunction;

        /**
         * Return the underlying dispatcher instance in the container
         *
         */
        function hasTrait($trait, $class)
        {

            $class_name = is_object($class) ? get_class($class) : splitAtSign($class);

            return class_exists($class_name) && in_array($trait, class_uses($class_name));

        }

        /**
         * Accepts a string that contains and @ and returns the part before the @.
         *
         * @param $string
         *
         * @return string|string[]
         */
        function splitAtSign(string $string)
        {

            return str_replace('@', '', explode('@', $string)[0]);


        }

        /**
         * Accepts a string that contains and @ and returns the part before the @.
         *
         * param mixed
         *
         * @return bool
         */
        function isClosure($object) : bool
        {

            return $object instanceof Closure;

        }

        /**
         * Accepts a string that contains and @ and returns the part before the @.
         *
         * param mixed
         *
         * @return bool
         */
        function isInitializedClass($object) : bool
        {

            return is_object($object) && ! isClosure($object);


        }

        /**
         * Accepts a string that contains and @ and returns the part before the @.
         *
         * param mixed
         *
         * @param        $callback
         * @param  null  $default
         *
         * @return bool
         */
        function normalizeClassMethod($callback, $default = null)
        {

            $first_element = Arr::first($callback);

            if (is_string($first_element) && Str::contains($first_element, '@')) {

                return $first_element;

            }

            $class = is_string($first_element) ? $first_element : get_class($first_element);

            $method = $callback[1] ?? $default;

            return "{$class}@{$method}";

        }

        /**
         *
         * Inspect the closure of the listener
         * so that we can get information about the static arguments used.
         *
         * @param  ReflectionFunction  $reflection_function
         *
         * @return mixed
         */
        function getStaticClosureArguments(ReflectionFunction $reflection_function)
        {


            return Arr::first($reflection_function->getStaticVariables());


        }

        /**
         *
         * Accepts an object or string and returns the classname
         *
         * @param  object|string  $class_name
         *
         * @return mixed
         */
        function classNameIfClassExists($class_name)
        {

            if (is_object($class_name)) {

                return get_class($class_name);

            }

            return $class_name;


        }

        /**
         *
         * Returns the first value of an array.
         * If a string is provided its wrapped in an array first.
         *
         * @param  string|array  $array
         *
         * @return mixed
         */
        function arrayFirst($array)
        {


            return array_values(Arr::wrap($array))[0];


        }

        function resolveListenerFromClosure(Closure $callable) : array
        {

            $listener = arrayFirst(getStaticClosureArguments(new ReflectionFunction($callable)));

            return $listener->toArray();


        }

        function classExists($class_name_or_object) : bool
        {

            if (is_object($class_name_or_object)) {
                return true;
            }

            return class_exists($class_name_or_object);

        }


    }
	

	