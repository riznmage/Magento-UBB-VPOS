<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * NITF XML Parser
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   XML
 * @package    XML_NITF
 * @author     Patrick O'Lone <polone@townnews.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: NITF.php,v 1.13 2005/12/09 14:51:04 polone Exp
 * @link       http://pear.php.net/package/XML_NITF/
 */

/**
 * Include the XML_Parser class as the base class
 */
require_once ('Parser.php');

// {{{ XML_NITF

/**
 * Simple NITF Parser
 *
 * This class provides basic NITF parsing. Many of the major elements of the NITF
 * standard are supported. This implementation is based off the NITF 3.1 DTD,
 * publicly available at the following URL:
 *
 * http://www.nitf.org/site/nitf-documentation/nitf-3-1.dtd
 *
 * Note that not all elements of this standard are not supported.
 *
 * <sample>
 * <?php
 * 
 * require_once("XML/NITF.php");
 * 
 * $oNITF =& new XML_NITF();
 * $oNITF->setInputFile("nitf.xml");
 * $xResult = $oNITF->parse();
 * if (PEAR::isError($xResult)) {
 *    die("Parsing failed: ".$xResult->getMessage());
 * }
 * 
 * echo $oNITF->getHeadline();
 * echo $oNITF->getByline();
 * 
 * ?>
 * </sample>
 * 
 * @category   XML
 * @package    XML_NITF
 * @author     Patrick O'Lone <polone@townnews.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: 1.0.2
 * @link       http://pear.php.net/package/XML_NITF
 */
class XML_NITF extends XML_Parser
{
    // {{{ properties
    
    /**
     * Meta tag properties retrieved from document head section
     * @see getMetaData()
     * @var array
     * @access private
     */
    var $m_kMeta = array();

    /**
     * Document Metadata
     * 
     * Container for metadata information about this particular document.
     * 
     * @see getDocData()
     * @var array
     * @access private
     */
    var $m_kDocData = array ('key-list' => array ());

    /**
     * Specific Publication Data
     * 
     * Information about specific instance of an item's publication. Contains
     * metadata about how the particular news object was used in a specific
     * instance.
     * 
     * @see getPubData()
     * @var array 
     * @access private
     */
    var $m_kPubData = array ();

    /**
     * Document Revisions
     * 
     * Information about the creative history of the document; also used as an
     * audit trail. Includes who made changes, when the changes were made, and
     * why. Each element of the array is a key-based array that corresponds to
     * the <revision-history> element.
     * 
     * @var array 
     * @see getRevision()
     * @access private
     */
    var $m_akRevisions = array ();

    /**
     * Document Headlines
     * 
     * The various headlines that were found in the document. The headlines are
     * keyed by the levels of HLX. The default hedline (if no level is found) is
     * HL1.
     * @var array
     * @see getHedlines()
     * @access private
     */
    var $m_kHedlines = array ('HL1' => null, 'HL2' => array ());

    /**
     * Abstract
     *  
     * Story abstract summary or synopsis of the contents of the document.
     * @var string
     * @access private
     */
    var $m_sAbstract = null;

    /**
     * @var string
     * Significant place mentioned in an article. Used to normalize locations.
     * The location in this variable is the place where the story's events will
     * or have unfolded.
     * @access private
     */
    var $m_sLocation = null;

    /**
     * @var string
     * Information distributor. May or may not be the owner or creator.
     * @access private
     */
    var $m_sDistributor = null;

    /**
     * @var string
     * The elements of the byline, including the author's name and title.
     * @see getByline()
     * @access private
     */
    var $m_kByline = array ('author' => null, 'title' => null);

    /**
     * @var array
     * An array of paragraphs extracted from the document
     * @see getLede(), getContent()
     * @access private
     */
    var $m_aContent = array ();

    /**
     * @var array
     * A list of media reference elements as found in the body section of the
     * document. Each element is an array itself with keyed properties related
     * to media element in question.
     * @see getMedia()
     * @access private
     */
    var $m_aMedia = array ();

    /**
     * @var array
     * A list of tags that were parsed (in order) denoting the current sequence
     * of tags that were parsed. This is array is used for parsing the document
     * elements in a particular order (if needed).
     * @see StartHandler(), EndHandler(), cdataHandler()
     * @access private
     */
    var $m_aParentTags = array ();

    /**
     * A byline at the end of a story. Example: Stuart Myles contributed to this
     * article.
     * @var string
     * @see getTagline()
     * @access private
     */
    var $m_sTagline = null;

    /**
     * Free-form bibliographic data. Used to elaborate on the source of
     * information.
     * @var string
     * @see getBibliography()
     * @access private
     */
    var $m_sBibliography = null;

    // }}}
    // {{{ getDocData()

    /**
     * Access all or specific elements of the <docdata> block
     *
     * @param string $sProperty  The property of the <docdata> block to return, the
     *                           most common being:
     *                            +"doc-id" - a unique identifier of this document
     *                            (string)
     *                            +"key-list" - a list of keywords provided with
     *                            the document (array)
     *                            +"copyright" - the copyright holder (string)
     *                            +"series" - if the document is part of series
     *                            (string)
     *                            +"urgency" - a number between 1 (urgent) and 8
     *                            (not urgent) (integer)
     *                            +"date.issue" - date the document was issued
     *                            (UNIX timestamp)
     *                            +"date.release" - date the document is publicly
     *                            available (UNIX timestamp)
     *                            +"date.expires" - date the document is no longer
     *                            valid (UNIX timestamp)
     *
     * @return mixed  All of the elements from the <docdata> block will be returned
     *                if a specific property is not provided. If a specific property
     *                is requested and is found in the docdata block, then that
     *                property will be returned. If the property cannot be found,
     *                null is returned.
     *               
     * @see getDocDataElement()
     * @access public
     */
    function getDocData($sProperty = null)
    {
        if (!empty ($sProperty)) {

            $sProperty = strtolower($sProperty);
            if (isset ($this->m_kDocData[$sProperty])) {

                return $this->m_kDocData[$sProperty];

            }
            return null;

        }
        return $this->m_kDocData;
    }

    // }}}
    // {{{ getMetaData()
    
    /**
     * Retrieve meta data from the NITF file
     * @return array Returns an array of key/value pairs from the meta section
     * @access public
     */
    function getMetaData()
    {
        return $this->m_kMeta;
    }
    
    // }}}
    // {{{ getPubData()

    /**
     * Returns all elements or a specific element from the <pubdata> block
     *
     * @param string $sProperty The publication property being retrieved
     * @return mixed Returns string, numeric, or array values depending on the
     *         property being accessed from the <pubdata> block.
     *
     * @access public
     */
    function getPubData($sProperty = null)
    {
        if (!empty ($sProperty)) {

            $sProperty = strtolower($sProperty);
            if (isset ($this->m_kPubData[$sProperty])) {

                return $this->m_kPubData[$sProperty];

            }
            return null;

        }

        return $this->m_kPubData;
    }

    // }}}
    // {{{ getRevision()

    /**
     * Get the revision history
     *
     * @return array An array containing key-value arrays. The properties of each
     *               array element in this array are:
     *
     *                 +"comment" - Reason for the revision
     *                 +"function" - Job function of individual performing revision
     *                 +"name" - Name of the person who made the revision
     *                 +"norm" - Date of the revision
     * @access public
     */
    function getRevision()
    {
        return $this->m_akRevisions;
    }

    // }}}
    // {{{ getHeadline()

    /**
     * Retrieve all headlines or a single headline denoted by key
     *
     * @param integer $nLevel  The key value corresponding to the headline to be
     *                         retrieved
     * @return mixed  Returns an array if no specific headline element is requested,
     *                or a string if the specific headline element requested exists
     * @access public
     */
    function getHeadline($nLevel = 1)
    {
        return $this->m_kHedlines["HL$nLevel"];
    }

    // }}}
    // {{{ getByline()

    /**
     * Return information about the author of a document
     *
     * @param string $sProperty The field of the byline to retrieve.
     * @return string The entire byline as we found in the document
     * @access public
     */
    function getByline($sProperty = 'author')
    {
        $sProperty = strtolower($sProperty);
        if (isset ($this->m_kByline[$sProperty])) {

            return $this->m_kByline[$sProperty];

        }

        return null;
    }

    // }}}
    // {{{ getMedia()

    /**
     * Query for a list of related media elements
     *
     * @param string $sProperty  If supplied, only this property will be returned
     *                           for each element of the media reference array.
     * @return array Returns an array of all media reference data, or an array of
     *               select media reference data determined by the property
     *               parameter passed.
     * @access public
     */
    function getMedia($sProperty = null)
    {
        if (empty ($sProperty)) {

            return $this->m_aMedia;

        } else {

            $aMediaRefs = array ();
            foreach ($this->m_aMedia as $aMediaElem) {

                if (isset ($aMediaElem[$sProperty])) {

                    array_push($aMediaRefs, $aMediaElem[$sProperty]);

                }
            }

            return $aMediaRefs;

        }
    }

    // }}}
    // {{{ getLede()

    /**
     * Returns the lede (sometimes called lead) paragraph
     *
     * @return string Returns the lede paragraph if it is defined, or null otherwise
     * @access public
     */
    function getLede()
    {
        if (isset ($this->m_aContent[0])) {

            return $this->m_aContent[0];

        }
        return null;
    }

    // }}}
    // {{{ getContent()

    /**
     * Returns the paragraphs of content
     *
     * @return array An array of elements that represent a single paragraph each
     * @access public
     */
    function & getContent()
    {
        return $this->m_aContent;
    }

    // }}}
    // {{{ getTagLine()

    /**
     * Returns the tag line (if one exists)
     *
     * @return string The tag line extracted from the NITF data source
     * @access public
     */
    function getTagline()
    {
        return $this->m_sTagline;
    }

    // }}}
    // {{{ getBibliography()

    /**
     * Returns the free-form bibliographic data
     *
     * @return string The bibliography (if one exists) is returned
     * @access public
     */
    function getBibliography()
    {
        return $this->m_sBibliography;
    }

    // }}}
    // {{{ toString()

    /**
     * Get a string version of the article
     *
     * @param string  $sCRLF  The character(s) used to separate each article
     *                        element in the string that is returned - often
     *                        referred to as the CRLF.
     * @return string A string representing the main headline, author, content,
     *                and tagline.
     * @access public
     */
    function & toString($sCRLF = "\n")
    {
        $sArticle = "{$this->m_kHedlines['HL1']}$sCRLF";

        if (!empty ($this->m_kByline['author'])) {

            $sArticle .= "{$this->m_kByline['author']}$sCRLF";

        }

        if (!empty ($this->m_sLocation)) {

            $sArticle .= "{$this->m_sLocation} - ";

        }

        $sArticle .= join($sCRLF, $this->m_aContent);

        if (!empty ($this->m_sTagline)) {

            $sArticle .= "$sCRLF{$this->m_sTagline}";

        }

        return $sArticle;
    }

    // }}}
    // {{{ StartHandler()

    /**
     * Handle start XML elements and attributes
     *
     * @param object $oParser The XML parser object instance that was inherited
     *                        from the XML_Parser class
     * @param string $sName   A tag element from the XML data stream
     * @param array $kAttrib  An array of XML attributes associated with the given
     *                        tag supplied
     * @return void
     * @access private
     */
    function StartHandler($oParser, $sName, $kAttrib)
    {
        // Push the element into the stack of XML elements already visited

        array_push($this->m_aParentTags, $sName);

        // Handle the attributes of the XML tags

        switch ($sName) {

            case 'HL2' :
                $this->_sHedline = null;
                break;

            case 'P' :
                if (!empty ($kAttrib['LEDE']) && ($kAttrib['LEDE'] == 'true')) {

                    $this->_bIsLede = true;

                }
                $this->_sContent = null;
                break;

            case 'DOC.COPYRIGHT' :
                $this->m_kDocData['copyright'] = $kAttrib['HOLDER'];
                break;

            case 'MEDIA' :
                $this->_kMedia = array ();
                if (!empty ($kAttrib['MEDIA-TYPE'])) {

                    $this->_kMedia['type'] = $kAttrib['MEDIA-TYPE'];

                } else {

                    $this->_kMedia['type'] = 'other';

                }

                $this->_kMedia['source'] = null;
                $this->_kMedia['mime-type'] = null;
                $this->_kMedia['caption'] = null;
                $this->_kMedia['data'] = null;
                $this->_kMedia['encoding'] = null;
                $this->_kMedia['producer'] = null;
                $this->_kMedia['meta'] = array ();
                break;

            case 'MEDIA-REFERENCE' :
                if (!empty ($kAttrib['SOURCE'])) {

                    $this->_kMedia['source'] = $kAttrib['SOURCE'];

                    // Compatibility with the AP Usenet feed - note that this is a non
                    // standard attribute and is NOT a part of NITF standards

                }
                elseif (!empty ($kAttrib['DATA-LOCATION'])) {

                    $this->_kMedia['source'] = $kAttrib['DATA-LOCATION'];

                }

                $this->_kMedia['mime-type'] = $kAttrib['MIME-TYPE'];
                break;

            case 'MEDIA-OBJECT' :
                $this->_kMedia['encoding'] = $kAttrib['ENCODING'];
                break;

            case 'MEDIA-METADATA' :
                if (!empty ($kAttrib['NAME'])) {

                    $this->_kMedia[$kAttrib['NAME']] = $kAttrib['VALUE'];

                }
                break;

            case 'PUBDATA' :
                foreach ($kAttrib as $sKey => $sValue) {

                    $this->m_kPubData[strtolower($sKey)] = $sValue;

                }
                break;

            case 'DOC-ID' :
                $this->m_kDocData['doc-id'] = $kAttrib['ID-STRING'];
                break;

                // NITF 3.0 extension - added per request by Lars Schenk
                // (info@lars-schenk.de). Document urgency status information.

            case 'URGENCY' :
                $this->m_kDocData['urgency'] = $kAttrib['ED-URG'];
                break;

                // The list of keywords or phrases are just added to the array of
                // keywords.

            case 'KEYWORD' :
                if (empty ($this->m_kDocData['key-list'])) {

                    $this->m_kDocData['key-list'] = array ();

                }

                array_push($this->m_kDocData['key-list'], $kAttrib['KEY']);
                break;

                // The release, expiration, and issuing dates of this article. The
                // ISO-8601 time stamp settings are preserved, but you can use the
                // magic function strtotime() to convert these to time stamp values.

            case 'DATE.RELEASE' :
            case 'DATE.EXPIRE' :
            case 'DATE.ISSUE' :
                if (!empty ($kAttrib['NORM'])) {

                    $sName = strtolower($sName);
                    $this->m_kDocData[$sName] = $kAttrib['NORM'];

                }
                break;

            case 'REVISION-HISTORY' :
                array_push($this->m_akRevisions, array_change_key_case($kAttrib, CASE_LOWER));
                break;
                
            case 'META':
                if (!empty($kAttrib['NAME']) && isset($kAttrib['CONTENT'])) {
                    $sName = strtolower($kAttrib['NAME']);
                    $this->m_kMeta[$sName] = $kAttrib['CONTENT'];
                }
                break;

        }

    }

    // }}}
    // {{{ EndHandler()

    /**
     * Handle XML tag closing state
     *
     * @param object $oParser  The parser object parsing the XML data
     * @param string $sName    The name of the tag element that has just ended
     * @return void
     * @access private
     */
    function EndHandler($oParser, $sName)
    {
        switch ($sName) {

            case 'HL1' :
                $this->m_kHedlines['HL1'] = trim($this->m_kHedlines['HL1']);
                break;

            case 'HL2' :
                array_push($this->m_kHedlines['HL2'], trim($this->_sHedline));
                unset ($this->_sHedline);
                break;

            case 'P' :
                if (isset ($this->_bIsLede)) {

                    array_unshift($this->m_aContent, trim($this->_sContent));
                    unset ($this->_bIsLede);

                } else {

                    array_push($this->m_aContent, trim($this->_sContent));

                }
                unset ($this->_sContent);
                break;

            case 'MEDIA' :
                array_push($this->m_aMedia, $this->_kMedia);
                unset ($this->_kMedia);
                break;

        }

        array_pop($this->m_aParentTags);
    }

    // }}}
    // {{{ cdataHandler()

    /**
         * Parses CDATA chunks
         *
         * @param object $oParser  The XML parser instance inherited from the
         *                         XML_Parser class
         * @param string $sData    The data chunk to be processed from the parser
         * @return void
         * @access private
         */
    function cdataHandler($oParser, $sData)
    {
        if (!in_array('MEDIA-OBJECT', $this->m_aParentTags)) {

            $sData = preg_replace('#\s+#', ' ', $sData);

        }

        // Elements that can be found in the BODY.HEAD section of the NITF
        // document are defined in this handler.

        if (in_array('BODY.HEAD', $this->m_aParentTags)) {

            // We don't care if they use other attribute items, we just want the
            // textual version of the byline. Other attributes are appended to
            // the byline data.

            if (in_array('BYLINE', $this->m_aParentTags)) {

                if (in_array('BYTTL', $this->m_aParentTags)) {

                    $this->m_kByline['title'] .= $sData;
                    return;

                }

                $this->m_kByline['author'] .= $sData;
                return;

            }

            // Generally, the distributor is the same as the company supplying
            // the content. However, this is not always the case (the AP, for
            // example).

            if (in_array('DISTRIBUTOR', $this->m_aParentTags)) {

                $this->m_sDistributor .= $sData;
                return;

            }

            // The location where the story pertains too.

            if (in_array('DATELINE', $this->m_aParentTags)) {

                if (in_array('LOCATION', $this->m_aParentTags)) {

                    $this->m_sLocation .= $sData;

                }
                return;
            }

            // There are only two possibilities for hedlines, the main headline
            // or a subheadline.

            if (in_array('HEDLINE', $this->m_aParentTags)) {

                if (in_array('HL2', $this->m_aParentTags)) {

                    $this->_sHedline .= $sData;

                } else {

                    $this->m_kHedlines['HL1'] .= $sData;

                }

            }
            return;

        }

        // The article content, including the lead and following paragraphs, can
        // be found in this section of the XML document.

        if (in_array('BODY.CONTENT', $this->m_aParentTags)) {

            if (in_array('MEDIA', $this->m_aParentTags)) {

                // The media caption for the currently selected media element.

                if (in_array('MEDIA-CAPTION', $this->m_aParentTags)) {

                    $this->_kMedia['caption'] .= $sData;
                    return;

                }

                if (in_array('MEDIA-OBJECT', $this->m_aParentTags)) {

                    $this->_kMedia['data'] .= $sData;
                    return;

                }

            }

            // A paragraph element was found.

            if (in_array('P', $this->m_aParentTags)) {

                $this->_sContent .= $sData;
                return;

            }

        }

        // The <body.end> tag has two primary elements, <taglines> and the free
        // form <bibliography> tags.

        if (in_array('BODY.END', $this->m_aParentTags)) {

            if (in_array('TAGLINE', $this->m_aParentTags)) {

                $this->m_sTagline .= $sData;
                return;

            }

            if (in_array('BIBLIOGRAPHY', $this->m_aParentTags)) {

                $this->m_sBibliography .= $sData;

            }

        }

    }

    // }}}

}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
 
?>