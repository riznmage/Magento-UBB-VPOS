<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:saxon="http://icl.com/saxon"
  xmlns:func="http://exslt.org/functions"
  xmlns:str="http://exslt.org/strings"
  xmlns:cssml="http://pear.php.net/cssml/1.0"
  extension-element-prefixes="func saxon"
  exclude-result-prefixes="func saxon str cssml">

  <xsl:output method="xml" indent="no"/>

  <!-- default parameters -->
  <xsl:param name="output">STDOUT</xsl:param>
  <xsl:param name="filter"></xsl:param>
  <xsl:param name="browser"></xsl:param>
  <xsl:param name="comment"></xsl:param>

  <xsl:template match="/">
    <xsl:choose>
      <xsl:when test="$output = 'STDOUT'">
        <output>
          <xsl:if test="$comment"><xsl:value-of select="concat('/* ', $comment, ' */&#010;')"/></xsl:if>
          <xsl:apply-templates select="cssml:CSSML/style"/>
        </output>
      </xsl:when>
      <xsl:otherwise>
        <saxon:output file="{$output}" omit-xml-declaration="yes" indent="no">
          <xsl:if test="$comment"><xsl:value-of select="concat('/* ', $comment, ' */&#010;')"/></xsl:if>
          <xsl:apply-templates select="cssml:CSSML/style"/>
        </saxon:output>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="style">
    <xsl:if test="str:isIncluded(@filterInclude, $filter) = 'true' and str:isIncluded(@browserInclude, $browser) = 'true'">
      <xsl:for-each select="selector">
        <!-- we have to differentiate here since domxml_dumpmem messes up formatting -->
        <xsl:choose>
          <xsl:when test="$output = 'STDOUT'">
            <xsl:value-of select="."/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of disable-output-escaping="yes" select="."/>
          </xsl:otherwise>
        </xsl:choose>
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

  <func:function name="str:isIncluded">
    <xsl:param name="include-list"/>
    <xsl:param name="subject"/>
    <func:result>
      <xsl:choose>
        <!-- if the subject is empty, then just don't filter -->
        <xsl:when test="not($subject)">
          <xsl:value-of select="true()"/>
        </xsl:when>
        <!-- if there is no include list, use default 'all' and return true  -->
        <xsl:when test="not(normalize-space($include-list))">
          <xsl:value-of select="true()"/>
        </xsl:when>
        <!-- if the include list exists and begins with a not, the subject must not be present -->
        <xsl:when test="starts-with($include-list,'not(')">
          <xsl:choose>
            <!-- if the subject is in this include list, then return false -->
            <xsl:when test="contains(concat(' ',substring-after(substring-before($include-list,')'),'not('),' '),concat(' ',$subject,' '))">
              <xsl:value-of select="false()"/>
            </xsl:when>
            <!-- if the subject was not in the include list, assume included and return true -->
            <xsl:otherwise>
              <xsl:value-of select="true()"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <!-- if the include list exists and it is a postive list, the subject must be included -->
        <xsl:otherwise>
          <xsl:choose>
            <!-- if the subject is present, return true -->
            <xsl:when test="contains(concat(' ',$include-list,' '),concat(' ',$subject,' '))">
              <xsl:value-of select="true()"/>
            </xsl:when>
            <!-- subject is not present, return false -->
            <xsl:otherwise>
              <xsl:value-of select="false()"/>
            </xsl:otherwise>
          </xsl:choose> 
        </xsl:otherwise>
      </xsl:choose>
    </func:result>
  </func:function>

</xsl:stylesheet>
