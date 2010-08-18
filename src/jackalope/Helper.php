<?php

class jackalope_Helper {
    /**
     * Returns an attribute casted to boolean
     * @param DOMElement node to fetch from
     * @param string attribute to fetch
     * @return bool the value converted to bool
     */
    public static function getBoolAttribute($node, $attribute) {
        if ('false' === $node->getAttribute($attribute)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Normalizes a path according to JCR's spec (3.4.5)
     * 
     * <ul>
     *   <li>All self segments(.) are removed.</li>
     *   <li>All redundant parent segments(..) are collapsed.</li>
     *   <li>If the path is an identifier-based absolute path, it is replaced by a root-based 
     *       absolute path that picks out the same node in the workspace as the identifier it replaces.</li>
     * </ul>
     *
     * @param   string  $path   The path to normalize
     * @return  string  The normalized path
     */
    public static function normalizePath($path) {
        // UUDID is HEX_CHAR{8}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{12}
        if (1 === preg_match('/^\[([[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12})\]$/', $path)) {
            // TODO: replace by a root-based absolute path of the given item
            $uuid = $path[1];
            throw new jackalope_NotImplementedException('Normalizing identifier-based absolute paths not implemented');
        } else {
            $finalPath = array();
            $abs = ($path && $path[0] == '/');
            $parts = explode('/', $path);
            foreach ($parts as $pathPart) {
                switch ($pathPart) {
                    case '.':
                    case '':
                        break;
                    case '..':
                        array_pop($finalPath);
                        break;
                    default:
                        array_push($finalPath, $pathPart);
                        break;
                }
            }
            $finalPath = implode('/', $finalPath);
            if ($abs) {
              $finalPath = '/'.$finalPath;
            }
            return $finalPath;
        }
    }

    /**
     * Creates an absolute path from a root and a relative path
     * and then normalizes it
     *
     * If root is missing or does not start with a slash, a slash will be prepended
     *
     * @param string Root path to append the relative
     * @param string Relative path
     * @return string Absolute and normalized path
     */
    public static function absolutePath($root, $relPath) {

        $root = trim($root, '/');
        $concat = $root;
        if (strlen($root)) {
            $concat = "/$root/";
        } else {
            $concat = '/';
        }
        $concat .= ltrim($relPath, '/');

        // TODO: maybe this should be required explicitly and not called from within this method...
        return self::normalizePath($concat);
    }

    public static function isAbsolutePath($path) {
        return $path && $path[0] == '/';
    }

    /**
     * Whether the path conforms to the JCR Specs (see paragraph 3.2)
     * 
     * TODO: only minimal check performed atm, not full specs
     */ 
    public static function isValidPath($path) {
        return (strpos($path, '//') === false && preg_match('/^[\w{}\/#:^+~*\[\]\.-]*$/i', $path));
    }

}
