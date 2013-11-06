<?php
// {{{ license

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// $Id: result.php,v 1.15 2005/10/12 14:48:55 toggg Exp $

// }}}
// {{{ description

// Result class for the Xpath/DOM XML manipulation and query interface.

// }}}
// {{{ constants

define('XML_XPATH_SORT_TEXT_ASCENDING',     1);
define('XML_XPATH_SORT_NUMBER_ASCENDING',   2);
define('XML_XPATH_SORT_NATURAL_ASCENDING',  3);
define('XML_XPATH_SORT_TEXT_DESCENDING',    4);
define('XML_XPATH_SORT_NUMBER_DESCENDING',  5);
define('XML_XPATH_SORT_NATURAL_DESCENDING', 6);

// }}}

// {{{ class XML_XPath_result

/**
 * Interface for an XML_XPath result so that one can cycle through the result set and manipulate
 * the main tree with DOM methods using a seperate pointer then the original class.
 *
 * @version  Revision: 1.1
 * @author   Dan Allen <dan@mojavelinux.com>
 * @access   public
 * @since    PHP 4.2.1
 * @package  XML_XPath
 */

// }}}
class XML_XPath_result extends XML_XPath_common {
    // {{{ properties

    /**
     * original xpath query, stored when we need to sort
     * @var string $query
     */
    var $query;

    /**
     * determines if we have counted the first node of the result nodeset
     * @var boolean $isRewound
     */
    var $isRewound;
    
    /**
     * The type of result that the query generated
     * @var int $type
     */
    var $type;

    /**
     * either array of nodesets, string, boolean or number from xpath/DOM query
     * @var mixed $data
     */
    var $data;
    
    /**
     * xpath context object for the current domxml object
     * @var object $ctx
     */
    var $ctx;

    /**
     * domxml object, need for many common functions
     * @var object $xml
     */
    var $xml;
    // }}}
    // {{{ constructor

    function XML_XPath_result($in_data, $in_type, $in_query, &$in_ctx, &$in_xml) 
    {
        $this->query = $in_query;
        $this->type = $in_type;
        $this->data = $in_data;
        $this->ctx = &$in_ctx;
        $this->xml =&$in_xml;
        // move the pointer to the first node if at least one node in the result exists
        // for convience, just so we don't have to call nextNode() if we expect only one
        $this->rewind();
    }

    // }}}
    // {{{ mixed   getData()
    
    /**
     * Return the data from the xpath query.  This function will be used mostly for xpath
     * queries that result in scalar results, but in the case of nodesets, returns size
     *
     * @access public
     * @return mixed scalar result from xpath query or size of nodeset
     */
    function getData()
    {
        switch($this->type) {
            case XPATH_BOOLEAN:
                return $this->data ? true : false;
            break;

            case XPATH_NODESET:
                if (!$this->pointer) {
                    return null;
                }
                else {
                    return $this->pointer->node_type() == XML_ATTRIBUTE_NODE ? $this->pointer->value() : $this->substringData();
                }
            break;

            case XPATH_STRING:
            case XPATH_NUMBER:
                return $this->data;
            break;
        }
    }

    // }}}
    // {{{ int     resultType()

    /**
     * Retrieve the type of result that was returned by the xpath query.
     *
     * @access public
     * @return int code corresponding to the xpath result types constants
     */
    function resultType() 
    {
        return $this->type;
    }

    // }}}
    // {{{ int     numResults()

    /**
     * Return the number of nodes if the result is a nodeset or 1 for scalar results.
     * result (boolean, string, numeric) xpath queries
     *
     * @access public
     * @return int number of results returned by xpath query
     */
    function numResults() {
        return count($this->data);
    }

    // }}}
    // {{{ int     getIndex()

    /**
     * Return the index of the result nodeset.
     *
     * @access public
     * @return int current index of the result nodeset
     */
    function getIndex()
    {
        return key($this->data);
    }

    // }}}
    // {{{ boolean sort()

    /**
     * Sort the nodeset in this result.  The sort can be either ascending or descending, and
     * the comparisons can be text, number or natural (see the constants above). The sort
     * axis is provided as an xpath query and is the location path relative to the node given.
     * For example, so sort on an attribute, you would provide '@foo' and it will look at the
     * attribute for each node.
     *
     * NOTE: If the axis is not found, the node will comes first in the sort order for ascending 
     * order and at the end for descending orde.
     *
     * @param  string $in_sortXpath relative xpath query location to each node in nodeset
     * @param  int $in_order either XML_XPATH_SORT_TEXT_[DE|A]SCENDING, 
     *                              XML_XPATH_SORT_NUMBER_[DE|A]SCENDING,
     *                              XML_XPATH_SORT_NATURAL_[DE|A]SCENDING
     *
     * @access public
     * @return boolean success (return false if nothing to sort)
     */
    function sort($in_sortXpath = '.', $in_order = XML_XPATH_SORT_TEXT_ASCENDING, $in_permanent = false) 
    {
        // make sure we are dealing with a result that is a nodeset
        if ($this->type != XPATH_NODESET || !$this->numResults()) {
            return false;
        }

        $data = array();

        // we don't need to run it again if we are soring on the current node values
        if ($in_sortXpath == '' || $in_sortXpath == '.') {
            foreach ($this->data as $index => $node) {
                $data[] = $node->get_content();
            }
        }
        // we need to run the query again
        else {
            // we never actually ran the query, but we can rebuild it...and we will do that now
            // this is for DOM queries, such as childNodes() and getElementsByTagName()
            if (is_array($this->query)) {
                $this->query = $this->getNodePath(reset($this->query)) . end($this->query);
            }

            // here I am reissuing the query, but with the sort path appended followed by the
            // node in a logical 'OR'.  The trick here is that I can keep the original nodes
            // in sorted order and then just weed out the nodes I used to sort.
            $xpathResult = @$this->ctx->xpath_eval($this->query . '/' . $in_sortXpath . '|' . $this->query);
            if (!$xpathResult || empty($xpathResult->nodeset)) {
                return PEAR::raiseError(null, XML_XPATH_INVALID_QUERY, null, E_USER_NOTICE, "Query {$this->query}/$in_sortXpath", 'XML_XPath_Error', true);
            }

            // Sorting Process: 
            // The reason we did a double query is so that we could line up the original nodes
            // with the original data set, fill in any parts of the sorted nodeset that have
            // missing sort nodes, sort the sorted nodeset and then reindex the original data array

            $origIndex = 0;
            $sortIndex = 0;
            while(isset($this->data[$origIndex])) {
                // make sure we are lined up on original nodes, then we can proceed to check
                // the next node in each nodeset to determine of the sort node was found
                if ($this->data[$origIndex] == $xpathResult->nodeset[$sortIndex]) {
                    $origIndex++;
                    $sortIndex++;
                    // make sure we have not advanced beyond the end of the sort nodeset
                    if (isset($xpathResult->nodeset[$sortIndex])) {
                        // if the values of the next two indices of the sort nodeset and the
                        // original nodeset are the same, we had a missing node
                        if (isset($this->data[$origIndex]) && $this->data[$origIndex] == $xpathResult->nodeset[$sortIndex]) {
                            $data[] = '';
                        }
                        // okay, they were different, we found a sort node, get its value
                        else {
                            $data[] = $xpathResult->nodeset[$sortIndex]->get_content();
                        }
                    }
                    // the last sort nodeset element is missing, which means the sort nodeset
                    // was missing the last sort node
                    else {
                        $data[] = '';
                    }
                }
                else {
                    $sortIndex++;
                }
            }
        }

        switch ($in_order) {
            case XML_XPATH_SORT_TEXT_ASCENDING:
                asort($data, SORT_STRING);
            break;

            case XML_XPATH_SORT_NUMBER_ASCENDING:
                asort($data, SORT_NUMERIC);
            break;

            case XML_XPATH_SORT_NATURAL_ASCENDING:
                natsort($data);
            break;

            case XML_XPATH_SORT_TEXT_DESCENDING:
                arsort($data, SORT_STRING);
            break;

            case XML_XPATH_SORT_NUMBER_DESCENDING:
                arsort($data, SORT_NUMERIC);
            break;

            case XML_XPATH_SORT_NATURAL_DESCENDING:
                natsort($data);
                $data = array_reverse($data, true);
            break;

            default:
                asort($data);
            break;
        }

        $dataReordered = array();
        // this is NOT just array_values, we need to use the keys to put the values
        // in the correct order
        foreach ($data as $reindex => $value) {
            $dataReordered[] = $this->data[$reindex];
        }

        $this->data = $dataReordered;

        // if this is a permanent sort, make the change to the main tree
        if ($in_permanent && $parent = $this->data[0]->parent_node()) {
            // nix all the children by overwriting node and fixing attributes
            $attributes = $parent->has_attributes() ? $parent->attributes() : array();
            $parent->replace_node($clone = $parent->clone_node());
            $parent = $clone;

            foreach($attributes as $attributeNode) {
               // waiting on set_attribute_node() to work here
               $parent->set_attribute($attributeNode->node_name(), $attributeNode->value());
            }
            
            foreach ($this->data as $key => $sortedNode) {
                $this->data[$key] = $parent->append_child($sortedNode);
            }
        }

        // rewind to the beginning of the data set
        $this->rewind();

        return true;
    }
     
    // }}}
    // {{{ boolean rewind()

    /**
     * Reset the result index back to the beginning, if this is an XPATH_NODESET
     *
     * @access public
     * @return boolean success
     */
    function rewind()
    {
        if (is_array($this->data)) {
            $this->pointer = reset($this->data);
            $this->isRewound = true;
            return true;
        }
        
        return false;
    }

    // }}}
    // {{{ boolean next()

    /**
     * Move to the next node in the nodeset of results.  This can be used inside of a
     * while loop, so that it is possible to step through the nodes one by one.
     * It is important to note that the first call to next will put the pointer at
     * the first index and not the second...this is just a more convenient way of
     * handling the logic.  If you rewind() the data and then call next() as the conditional
     * on a while loop, you can work through each of the results from the first to the last.
     *
     * @access public
     * @return boolean success node found and pointer advanced
     */
    function next()
    {
        if (is_array($this->data)) {
            if ($this->isRewound) {
                $this->isRewound = false;
                $seekFunction = 'reset';
            }
            else {
                $seekFunction = 'next';
            }

            if ($node = $seekFunction($this->data)) {
                $this->pointer = $node;
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ boolean nextByNodeName()

    /**
     * Move to the next node in the nodeset of results where the node has the name provided.
     * This can be used inside of a while loop, so that it is possible to step through the 
     * nodes one by one.
     *
     * @param  string $in_name name of node to find
     *
     * @access public
     * @return boolean next node existed and pointer moved
     */
    function nextByNodeName($in_name)
    {
        if (is_array($this->data)) {
            if ($this->isRewound) {
                $this->isRewound = false;
                if (($node = reset($this->data)) && $node->node_name() == $in_name) {
                    $this->pointer = $node;
                    return true;
                }
            }

            while ($node = next($this->data)) {
                if ($node->node_name() == $in_name) {
                    $this->pointer = $node;
                    return true;
                }
            }
        }

        return false;
    }

    // }}}
    // {{{ boolean nextByNodeType()

    /**
     * Move to the next node in the nodeset of results where the node has the type provided.
     * This can be used inside of a while loop, so that it is possible to step through the 
     * nodes one by one.
     *
     * @param  int  $in_type type of node to find
     *
     * @access public
     * @return boolean next node existed and pointer moved
     */
    function nextByNodeType($in_type)
    {
        if (is_array($this->data)) {
            if ($this->isRewound) {
                $this->isRewound = false;
                if (($node = reset($this->data)) && $node->node_type() == $in_type) {
                    $this->pointer = $node;
                    return true;
                }
            }

            while ($node = next($this->data)) {
                if ($node->node_type() == $in_type) {
                    $this->pointer = $node;
                    return true;
                }
            }
        }

        return false;
    }

    // }}}
    // {{{ object  current()

    /**
     * Retrieve current pointer
     *
     * If the result is a nodeset (which is the most common use of the result object) than
     * this function returns the current pointer in the result array.
     *
     * @return object XML_XPath pointer
     * @access public
     */
    function current()
    {
        if (is_array($this->data)) {
            return current($this->data);
        }
        
        return false;
    }

    // }}}
    // {{{ boolean end()

    /**
     * Move to last result node, if this is an XPATH_NODESET
     *
     * @access public
     * @return boolean success
     */
    function end()
    {
        if (is_array($this->data)) {
            $this->pointer = end($this->data);
            return true;
        }

        return false;
    }

    // }}}
    // {{{ void    free()

    /**
     * Free the result object in order to save memory.
     *
     * @access public
     * @return void
     */
    function free()
    {
        $this->data = null; 
        $this->ctx = null; 
        $this->xml = null; 
    }

    // }}}
}
?>
