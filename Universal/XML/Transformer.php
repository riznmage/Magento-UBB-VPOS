<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                                |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                         Kristian Köhntopp <kris@koehntopp.de>.            |
// +---------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,           |
// | that is available at http://www.php.net/license/3_0.txt.                  |
// | If you did not receive a copy of the PHP license and are unable to        |
// | obtain it through the world-wide-web, please send a note to               |
// | license@php.net so we can mail you a copy immediately.                    |
// +---------------------------------------------------------------------------+
//
// $Id: Transformer.php,v 1.137 2004/11/20 08:23:51 sebastian Exp $
//

require_once 'Transformer/CallbackRegistry.php';
require_once 'Util.php';

/**
 * XML Transformations in PHP.
 *
 * With this class one can easily bind PHP functionality to XML tags,
 * thus transforming an XML input tree into another XML tree without
 * the need for XSLT.
 *
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian Köhntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian Köhntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer {
    // {{{ Members

    /**
    * @var    object
    * @access private
    */
    var $_callbackRegistry = NULL;

    /**
    * If TRUE, XML attribute and element names will be
    * case-folded.
    * 
    * @var    boolean
    * @access private
    * @see    $_caseFoldingTo
    */
    var $_caseFolding = FALSE;

    /**
    * Can be set to either CASE_UPPER or CASE_LOWER
    * and sets the target case for the case-folding.
    *
    * @var    integer
    * @access private
    * @see    $_caseFolding
    */
    var $_caseFoldingTo = CASE_UPPER;

    /**
    * When set to TRUE empty XML tags (<foo></foo>) are
    * collapsed to their short-tag (<foo/>) equivalent.
    *
    * @var    boolean
    * @access private
    */
    var $_collapseEmptyTags = FALSE;

    /**
    * Collapse mode
    *
    * @var    int
    * @access private
    */
    var $_collapseEmptyTagsMode = XML_UTIL_COLLAPSE_ALL;

    /**
    * If TRUE, debugging information will be sent to
    * the error.log.
    *
    * @var    boolean
    * @access private
    * @see    $_debugFilter
    */
    var $_debug = FALSE;

    /**
    * If not empty, debugging information will only be generated
    * for XML elements whose names are in this array.
    *
    * @var    array
    * @access private
    * @see    $_debug
    */
    var $_debugFilter = array();

    /**
    * Specifies the target to which error messages and
    * debugging messages are sent.
    *
    * @var    string
    * @access private
    * @see    $_debug
    */
    var $_logTarget = 'error_log';

    /**
    * @var    array
    * @access private
    */
    var $_attributesStack = array();

    /**
    * @var    array
    * @access private
    */
    var $_cdataStack = array('');

    /**
    * @var    array
    * @access private
    */
    var $_elementStack = array();

    /**
    * @var    integer
    * @access private
    */
    var $_level = 0;

    /**
    * @var    string
    * @access private
    */
    var $_lastProcessed = '';

    /**
    * @var    boolean
    * @access public
    */
    var $_secondPassRequired = FALSE;

    /**
    * @var    integer
    * @access private
    */
    var $_depth = 0;

    // }}}
    // {{{ function XML_Transformer($parameters = array())

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    function XML_Transformer($parameters = array()) {
        // Parse parameters array.

        if (isset($parameters['debug'])) {
            $this->setDebug($parameters['debug']);
        }

        $this->_caseFolding           = isset($parameters['caseFolding'])           ? $parameters['caseFolding']           : FALSE;
        $this->_collapseEmptyTags     = isset($parameters['collapseEmptyTags'])     ? $parameters['collapseEmptyTags']     : FALSE;
        $this->_collapseEmptyTagsMode = isset($parameters['collapseEmptyTagsMode']) ? $parameters['collapseEmptyTagsMode'] : XML_UTIL_COLLAPSE_ALL;
        $this->_caseFoldingTo         = isset($parameters['caseFoldingTo'])         ? $parameters['caseFoldingTo']         : CASE_UPPER;
        $this->_lastProcessed         = isset($parameters['lastProcessed'])         ? $parameters['lastProcessed']         : '';
        $this->_logTarget             = isset($parameters['logTarget'])             ? $parameters['logTarget']             : 'error_log';

        $autoload                     = isset($parameters['autoload'])              ? $parameters['autoload']              : FALSE;
        $overloadedNamespaces         = isset($parameters['overloadedNamespaces'])  ? $parameters['overloadedNamespaces']  : array();
        $recursiveOperation           = isset($parameters['recursiveOperation'])    ? $parameters['recursiveOperation']    : TRUE;

        // Initialize callback registry.

        if (!isset($parameters['callbackRegistry'])) {
            $this->_callbackRegistry = new XML_Transformer_CallbackRegistry($recursiveOperation);
        } else {
            $this->_callbackRegistry = &$parameters['callbackRegistry'];
        }

        foreach ($overloadedNamespaces as $namespacePrefix => $object) {
            $this->overloadNamespace(
              $namespacePrefix,
              $object
            );
        }

        if ($autoload !== FALSE) {
            $this->_autoload($autoload);
        }
    }

    // }}}
    // {{{ function canonicalize($target)

    /**
    * Canonicalizes a given attributes array or element name.
    *
    * @param  mixed
    * @return mixed
    * @access public
    */
    function canonicalize($target) {
        if ($this->_caseFolding) {
            if (is_string($target)) {
                return ($this->_caseFoldingTo == CASE_UPPER) ? strtoupper($target) : strtolower($target);
            } else {
                return array_change_key_case(
                  $target,
                  $this->_caseFoldingTo
                );
            }
        }

        return $target;
    }

    // }}}
    // {{{ function overloadNamespace($namespacePrefix, &$object, $recursiveOperation = '')

    /**
    * Overloads an XML Namespace.
    *
    * @param  string
    * @param  object
    * @param  boolean
    * @access public
    */
    function overloadNamespace($namespacePrefix, &$object, $recursiveOperation = '') {
        if (empty($namespacePrefix) ||
            $namespacePrefix == '&MAIN') {
            $namespacePrefix = '&MAIN';
        } else {
            $namespacePrefix = $this->canonicalize($namespacePrefix);
        }

        $result = $this->_callbackRegistry->overloadNamespace(
          $namespacePrefix,
          $object,
          $recursiveOperation
        );

        if ($result === TRUE) {
            if ($object->secondPassRequired) {
                $this->_secondPassRequired = TRUE;
            }

            // Call initObserver() on the object, if it exists.

            if (method_exists($object, 'initObserver')) {
                $object->initObserver(
                  $namespacePrefix,
                  $this
                );
            }
        } else {
            $this->sendMessage(
              $result,
              $this->_logTarget
            );
        }
    }

    // }}}
    // {{{ function unOverloadNamespace($namespacePrefix)

    /**
    * Reverts overloading of a given XML Namespace.
    *
    * @param  string
    * @access public
    */
    function unOverloadNamespace($namespacePrefix) {
        $this->_callbackRegistry->unOverloadNamespace($namespacePrefix);
    }

    // }}}
    // {{{ function isOverloadedNamespace($namespacePrefix)

    /**
    * Returns TRUE if a given namespace is overloaded,
    * FALSE otherwise.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function isOverloadedNamespace($namespacePrefix) {
        return $this->_callbackRegistry->isOverloadedNamespace(
          $this->canonicalize($namespacePrefix)
        );
    }

    // }}}
    // {{{ function sendMessage($message, $target = 'error_log')

    /**
    * Sends a message to a given target.
    *
    * @param  string
    * @param  string
    * @access public
    */
    function sendMessage($message, $target = 'error_log') {
        switch ($target) {
            case 'echo':
            case 'print': {
                print $message;
            }
            break;

            default: {
                error_log($message);
            }
        }
    }

    // }}}
    // {{{ function setCaseFolding($caseFolding)

    /**
    * Sets the XML parser's case-folding option.
    *
    * @param  boolean
    * @param  integer
    * @access public
    */
    function setCaseFolding($caseFolding, $caseFoldingTo = CASE_UPPER) {
        if (is_bool($caseFolding) &&
            ($caseFoldingTo == CASE_LOWER || $caseFoldingTo == CASE_UPPER)) {
            $this->_caseFolding   = $caseFolding;
            $this->_caseFoldingTo = $caseFoldingTo;
        }
    }

    // }}}
    // {{{ function setCollapsingOfEmptyTags($collapseEmptyTags, $mode = XML_UTIL_COLLAPSE_ALL)

    /**
    * Sets the collapsing of empty tags.
    *
    * @param  boolean
    * @param  integer
    * @access public
    */
    function setCollapsingOfEmptyTags($collapseEmptyTags, $mode = XML_UTIL_COLLAPSE_ALL) {
        if (is_bool($collapseEmptyTags) &&
            ($mode == XML_UTIL_COLLAPSE_ALL || $mode == XML_UTIL_COLLAPSE_XHTML_ONLY)) {
            $this->_collapseEmptyTags     = $collapseEmptyTags;
            $this->_collapseEmptyTagsMode = $mode;
        }
    }

    // }}}
    // {{{ function setDebug($debug)

    /**
    * Enables or disables debugging information.
    *
    * @param  mixed
    * @access public
    */
    function setDebug($debug) {
        if (is_array($debug)) {
            $this->_debug       = TRUE;
            $this->_debugFilter = array_flip($debug);
        }

        else if (is_bool($debug)) {
            $this->_debug = $debug;
        }
    }

    // }}}
    // {{{ function setLogTarget($logTarget)

    /**
    * Sets the target to which error messages and
    * debugging messages are sent.
    *
    * @param  string
    * @access public
    */
    function setLogTarget($logTarget) {
        $this->_logTarget = $logTarget;
    }

    // }}}
    // {{{ function setRecursiveOperation($recursiveOperation)

    /**
    * Enables or disables the recursive operation.
    *
    * @param  boolean
    * @access public
    */
    function setRecursiveOperation($recursiveOperation) {
        $this->_callbackRegistry->setRecursiveOperation($recursiveOperation);
    }

    // }}}
    // {{{ function stackdump()

    /**
    * Returns a stack dump as a debugging aid.
    *
    * @return string
    * @access public
    */
    function stackdump() {
        $stackdump = sprintf(
          "Stackdump (level: %s) follows:\n",
          $this->_level
        );

        for ($i = $this->_level; $i >= 0; $i--) {
          $stackdump .= sprintf(
            "level=%d\nelement=%s:%s\ncdata=%s\n\n",
            $i,
            isset($this->_elementStack[$i])    ? $this->_elementStack[$i]                                  : '',
            isset($this->_attributesStack[$i]) ? XML_Util::attributesToString($this->_attributesStack[$i]) : '',
            isset($this->_cdataStack[$i])      ? $this->_cdataStack[$i]                                    : ''
          );
        }

        return $stackdump;
    }

    // }}}
    // {{{ function transform($xml)

    /**
    * Transforms a given XML string using the registered
    * PHP callbacks for overloaded tags.
    *
    * @param  string
    * @return string
    * @access public
    */
    function transform($xml) {
        // Do not process input when it contains no XML elements.

        if (strpos($xml, '<') === FALSE) {
            return $xml;
        }

        // Replace all occurrences of the '&' character that are not directly
        // followed by 'amp;' with the '&amp;' entity.

        $xml = preg_replace('/&(?!amp;)/i', '&amp;', $xml);

        // Create XML parser, set parser options.

        $parser = xml_parser_create();

        xml_set_object($parser, $this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, $this->_caseFolding);

        // Register SAX callbacks.

        xml_set_element_handler($parser, '_startElement', '_endElement');
        xml_set_character_data_handler($parser, '_characterData');
        xml_set_default_handler($parser, '_characterData');

        // Parse input.

        if (!xml_parse($parser, $xml, TRUE)) {
            $line = xml_get_current_line_number($parser);

            $errorMessage = sprintf(
              "Transformer: XML Error: %s at line %d:%d\n",
              xml_error_string(xml_get_error_code($parser)),
              $line,
              xml_get_current_column_number($parser)
            );

            $exml = preg_split('/\n/', $xml);

            $start = ($line - 3 > 0)             ? $line - 3 : 0;
            $end   = ($line + 3 < sizeof($exml)) ? $line + 3 : sizeof($exml);

            for ($i = $start; $i < $end; $i++) {
                $errorMessage .= sprintf(
                  "line %d: %s\n",
                  $i+1,
                  $exml[$i]
                );
            }

            $this->sendMessage(
              $errorMessage . "\n" . $this->stackdump(),
              $this->_logTarget
            );

            return '';
        }

        $result = $this->_cdataStack[0];

        // Clean up.

        xml_parser_free($parser);

        $this->_attributesStack = array();
        $this->_cdataStack      = array('');
        $this->_elementStack    = array();
        $this->_level           = 0;
        $this->_lastProcessed   = '';

        // Perform second transformation pass, if required.

        $secondPassRequired = $this->_secondPassRequired;

        if ($secondPassRequired) {
            $this->_depth++;
            $this->_secondPassRequired = FALSE;
            $result = $this->transform($result);
            $this->_depth--;
        }

        if ($this->_collapseEmptyTags &&
            $this->_depth == 0) {
            $result = XML_Util::collapseEmptyTags(
              $result,
              $this->_collapseEmptyTagsMode
            );
        }

        $this->_secondPassRequired = $secondPassRequired;

        // Return result of the transformation.

        return $result;
    }

    // }}}
    // {{{ function _startElement($parser, $element, $attributes)

    /**
    * SAX callback for 'startElement' event.
    *
    * @param  resource
    * @param  string
    * @param  array
    * @access private
    */
    function _startElement($parser, $element, $attributes) {
        $attributes = $this->canonicalize($attributes);
        $element    = $this->canonicalize($element);
        $qElement   = XML_Util::splitQualifiedName($element, '&MAIN');
        $process    = $this->_lastProcessed != $element;

        // Push element's name and attributes onto the stack.

        $this->_level++;
        $this->_elementStack[$this->_level]    = $element;
        $this->_attributesStack[$this->_level] = $attributes;

        if ($this->_checkDebug($element)) {
            $this->sendMessage(
              sprintf(
                'startElement[%d]: %s %s',
                $this->_level,
                $element,
                XML_Util::attributesToString($attributes)
              )
            );
        }

        if ($process &&
            isset($this->_callbackRegistry->overloadedNamespaces[$qElement['namespace']]['active'])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_callbackRegistry->overloadedNamespaces[$qElement['namespace']]['object']->startElement(
              $qElement['localPart'],
              $attributes
            );
        } else {
            // No callback was registered for this element's
            // opening tag, copy it.

            $cdata = sprintf(
              '<%s%s>',
              $element,
              XML_Util::attributesToString($attributes)
            );
        }

        $this->_cdataStack[$this->_level] = $cdata;
    }

    // }}}
    // {{{ function _endElement($parser, $element)

    /**
    * SAX callback for 'endElement' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    function _endElement($parser, $element) {
        $cdata     = $this->_cdataStack[$this->_level];
        $element   = $this->canonicalize($element);
        $qElement  = XML_Util::splitQualifiedName($element, '&MAIN');
        $process   = $this->_lastProcessed != $element;
        $recursion = FALSE;

        if ($process &&
            isset($this->_callbackRegistry->overloadedNamespaces[$qElement['namespace']]['active'])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $result = $this->_callbackRegistry->overloadedNamespaces[$qElement['namespace']]['object']->endElement(
              $qElement['localPart'],
              $cdata
            );

            if (is_array($result)) {
                $cdata   = &$result[0];
                $reparse = $result[1];
            } else {
                $cdata   = &$result;
                $reparse = TRUE;
            }

            $recursion = $reparse &&
                         isset($this->_elementStack[$this->_level-1]) &&
                         $this->_callbackRegistry->overloadedNamespaces[$qElement['namespace']]['recursiveOperation'];
        } else {
            // No callback was registered for this element's
            // closing tag, copy it.

            $cdata .= '</' . $element . '>';
        }

        if ($recursion) {
            // Recursively process this transformation's result.

            if ($this->_checkDebug('&RECURSE')) {
                $this->sendMessage(
                  sprintf(
                    'start recursion[%d]: %s',
                    $this->_level,
                    $cdata
                  )
                );
            }

            $transformer = new XML_Transformer(
              array(
                'callbackRegistry' => &$this->_callbackRegistry,
                'caseFolding'      => $this->_caseFolding,
                'caseFoldingTo'    => $this->_caseFoldingTo,
                'lastProcessed'    => $element
              )
            );

            $cdata = substr($transformer->transform("<_>$cdata</_>"), 3, -4);

            if ($this->_checkDebug('&RECURSE')) {
                $this->sendMessage(
                  sprintf(
                    'end recursion[%d]: %s',
                    $this->_level,
                    $cdata
                  )
                );
            }
        }

        if ($this->_checkDebug($element)) {
            $this->sendMessage(
              sprintf(
                'endElement[%d]: %s (with cdata=%s)',
                $this->_level,
                $element,
                $this->_cdataStack[$this->_level]
              )
            );
        }

        // Move result of this transformation step to
        // the parent's CDATA section.

        $this->_cdataStack[--$this->_level] .= $cdata;
    }

    // }}}
    // {{{ function _characterData($parser, $cdata)

    /**
    * SAX callback for 'characterData' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    function _characterData($parser, $cdata) {
      if ($this->_checkDebug('&CDATA')) {
          $this->sendMessage(
            sprintf(
              'cdata [%d]: %s + %s',
              $this->_level,
              $this->_cdataStack[$this->_level],
              $cdata
            )
          );
      }

      $this->_cdataStack[$this->_level] .= $cdata;
    }

    // }}}
    // {{{ function _autoload($namespaces)

    /**
    * Loads either all (TRUE) or a selection of namespace
    * handlers from XML/Transformer/Namespace/.
    *
    * @param  mixed
    * @access private
    */
    function _autoload($namespaces) {
        $path = dirname(__FILE__) . '/Transformer/Namespace/';

        if ($namespaces === TRUE) {
            $namespaces = array();

            if ($dir = @opendir($path)) {
                while (($file = @readdir($dir)) !== FALSE) {
                    if (strstr($file, '.php')) {
                        $namespaces[] = $this->canonicalize(
                          strtolower(
                            substr($file, 0, -4)
                          )
                        );
                    }
                }
            }
        }

        else if (is_string($namespaces)) {
            $namespaces = array($namespaces);
        }

        foreach ($namespaces as $namespace) {
            if (@include_once($path . $namespace . '.php')) {
                $className = 'XML_Transformer_Namespace_' . $namespace;
                $object    = new $className;

                $this->overloadNamespace(
                  !empty($object->defaultNamespacePrefix) ? $object->defaultNamespacePrefix : $namespace,
                  $object
                );
            }
        }
    }

    // }}}
    // {{{ function _checkDebug($currentElement = '')

    /**
    * Checks whether a debug message should be printed
    * for the current event.
    *
    * @param  string
    * @return boolean
    * @access private
    */
    function _checkDebug($currentElement = '') {
        if ($this->_debug &&
            (empty($this->_debugFilter) ||
             isset($this->_debugFilter[$currentElement]))) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
