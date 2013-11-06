<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
     xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
     xmlns:saxon="http://icl.com/saxon"
     xmlns:func="http://www.exslt.org/functions"
     xmlns:str="http://exslt.org/strings"
     xmlns:cssml="http://pear.php.net/cssml/1.0"
     extension-element-prefixes="func saxon"
     exclude-result-prefixes="func saxon str cssml">

  <func:script implements-prefix="str" language="javascript"><![CDATA[
function isIncluded(haystack, needle) 
{
    // if the needle is empty, return true
    if (!needle) {
        return true;
    }

    // if the haystack is a node, get the value
    if (typeof(haystack) == "object") {
        haystack = haystack.item(0);
        if (typeof(haystack) == 'undefined') {
            return true;
        }

        if (haystack.nodeType == haystack.ATTRIBUTE_NODE) {
            haystack = haystack.value;
        }
        else if (haystack.nodeType == haystack.ELEMENT_NODE) {
            haystack = haystack.nodeValue;
        }
    }

    // if the haystack is empty, return true
    if (!haystack) {
        return true;
    }

    var needleRegExp = new RegExp("\\b"+needle+"\\b");

    if (haystack.match(/^not\((.*?)\)$/)) {
        if (RegExp.$1.match(needleRegExp)) {
            return false;
        }
        else {
            return true;
        }
    }
    else if (haystack.match(needleRegExp)) {
        return true;
    }
    else {
        return false;
    }
}
    ]]>
    <xsl:fallback>
      <xsl:text>Javascript is not configured properly with Sablotron</xsl:text>
    </xsl:fallback>
  </func:script>

  <xsl:output 
    method="text"
    indent="no"
  />

  <!-- default parameters -->
  <xsl:param name="filter"></xsl:param>
  <xsl:param name="browser">ie</xsl:param>
  <xsl:param name="comment"></xsl:param>

  <xsl:template match="/">
    <xsl:if test="$comment"><xsl:value-of select="concat('/* ', $comment, ' */&#010;')"/></xsl:if>
    <xsl:apply-templates select="cssml:CSSML/style"/>
  </xsl:template>

  <xsl:template match="style">
    <xsl:if test="str:isIncluded(@filterInclude, $filter) = 'true' and str:isIncluded(@browserInclude, $browser) = 'true'">
      <xsl:for-each select="selector">
        <!-- we have to differentiate here since domxml_dumpmem messes up formatting -->
        <xsl:value-of disable-output-escaping="yes" select="."/>
        <xsl:choose>
          <xsl:when test="@class">
            <xsl:value-of select="concat('.', @class)"/>
          </xsl:when>
          <xsl:when test="@id">
            <xsl:value-of select="concat('#', @id)"/>
          </xsl:when>
        </xsl:choose>
        <xsl:if test="@pseudoclass">
          <xsl:value-of select="concat(':', @pseudoclass)"/>
        </xsl:if>
        <xsl:if test="position() != last()">
          <xsl:value-of select="', '"/>
        </xsl:if>
      </xsl:for-each>
      <xsl:value-of select="' {&#010;'"/>
      <xsl:for-each select="declaration">
        <xsl:if test="str:isIncluded(@browserInclude, $browser) = 'true'">
          <xsl:value-of select="concat('  ', @property, ': ', text(), ';&#010;')"/>
        </xsl:if>
      </xsl:for-each>
      <xsl:value-of select="'}&#010;'"/>
    </xsl:if>
  </xsl:template>
  
</xsl:stylesheet>
