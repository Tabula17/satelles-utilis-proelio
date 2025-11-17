<?php

namespace Tabula17\Satelles\Utilis\Array;

use SimpleXMLElement;

/**
 * Utility class to perform various operations on arrays, such as getting,
 * setting, and unsetting values by path, as well as converting XML to arrays.
 */
class ArrayUtilities
{

    /**
     * Set a value in an array using a dot-separated path.
     *
     * @param $array
     * @param $path
     * @param $value
     * @return void
     */
    public static function setArrayValueByPath(&$array, $path, $value): void
    {
        //  echo 'SET ARRAY VALUE BY PATH -> ', $path, PHP_EOL;
        // Convert string path to an array if necessary
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $current = &$array; // Start at the root of the array

        foreach ($path as $key) {
            // If a key in the path doesn't exist or isn't an array, initialize it
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key]; // Move deeper into the array
        }

        $current = $value; // Set the final value
    }

    /**
     * Get a value from an array using a dot-separated path.
     *
     * @param $array
     * @param $path
     * @return mixed
     */
    public static function getArrayValueByPath($array, $path): mixed
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        }
        $current = &$array;
        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                return null;
            }
            $current = &$current[$key];
        }
        return $current;
    }

    /**
     * Unset a value from an array using a dot-separated path.
     *
     * @param $array
     * @param $path
     * @return bool True if the value was successfully unset, false otherwise
     */
    public static function unsetByPath(&$array, $path): bool
    {
        $keys = explode('.', $path); // Split the path into individual keys
        $temp = &$array; // Create a reference to the array to modify it directly

        // Traverse the array until the second-to-last key in the path
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];
            if (!isset($temp[$key]) || !is_array($temp[$key])) {
                // Path does not exist or is not an array at an intermediate step
                return false;
            }
            $temp = &$temp[$key]; // Move deeper into the array
        }

        // Unset the final element
        $finalKey = end($keys);
        if (isset($temp[$finalKey])) {
            unset($temp[$finalKey]);
            return true;
        }

        return false; // Element at the path was not found
    }

    public static function getArrayPathsByKey(array $array, string $keyToFind): array
    {
        $found = [];
        function search($value, $key, &$found = [], $path = ''): void
        {
            foreach ($value as $k => $v) {
                if ($key === $k) {
                    if (!empty($path)) {
                        $path .= '.' . $k;
                    }
                    $found[] = $path;
                    $path = '';
                }
                if (is_array($v)) {
                    if (!empty($path)) {
                        $path .= '.' . $k;
                    } else {
                        $path = (string)$k;
                    }
                    search($v, $key, $found, $path); // Key found in a nested array
                    $path = '';
                }
            }
        }
        search($array, $keyToFind, $found, '');
        return $found;
    }

    public static function getArrayPathsByValue(array $array, $searchValue, $currentPath = [], $strict = true, &$found = []): int|array|string|null
    {
        foreach ($array as $key => $value) {
            $newPath = array_merge($currentPath, [$key]); // Add current key to path
            if (is_array($value)) {
                self::getArrayPathsByValue($value, $searchValue, $newPath, $strict, $found);
            } else if ($strict ? $value === $searchValue : str_contains($value, $searchValue)) {
                $found[] = implode('.', $newPath);
            }
        }
        return $found; // Value not found in this branch
    }

    /**
     * Converts a SimpleXMLElement object to an associative array.
     */
    public static function simpleXmlToArray(?SimpleXMLElement $xml): array|string
    {
        if ($xml === null) {
            return [];
        }

        $result = [];
        foreach ($xml->attributes() as $attrName => $attrValue) {
            $result['@attributes'][$attrName] = (string)$attrValue;
        }

        foreach ($xml->children() as $childName => $childElement) {
            $childArray = self::simpleXmlToArray($childElement);

            if (!isset($result[$childName])) {
                $result[$childName] = $childArray;
                continue;
            }

            if (!is_array($result[$childName]) || !array_is_list($result[$childName])) {
                $result[$childName] = [$result[$childName]];
            }

            $result[$childName][] = $childArray;
        }

        $textContent = trim((string)$xml);
        if (empty($result)) {
            return $textContent;
        }

        if ($textContent !== '') {
            $result['@value'] = $textContent;
        }

        return $result;
    }
}