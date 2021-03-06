<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template name="organisation" match="record[type/@id=53]">
<div id="{id}" class="record  L{@depth}">
                <a target="_new">
                    <xsl:attribute name="href">rectype_renderer/<xsl:value-of select="id"/></xsl:attribute>
                    <xsl:value-of select="title"/>
                </a>
                <br/>
                <xsl:for-each select="detail[@id=203]"><!-- Organisation type -->
                    <xsl:value-of select="."/>
                    <xsl:if test="position() != last()">,
                        </xsl:if>
                </xsl:for-each>
                <br/>
                <xsl:for-each select="detail[@id=181]"><!-- Location -->
                    <xsl:value-of select="."/>
                    <xsl:if test="position() != last()">,
                        </xsl:if>
                </xsl:for-each>
</div>
</xsl:template>
</xsl:stylesheet>
