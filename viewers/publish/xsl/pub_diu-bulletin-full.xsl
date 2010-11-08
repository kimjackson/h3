<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:param name="hBase"/>
  <!--
 this style renders standard html
 author  Maria Shvedova
 last updated 10/09/2007 ms
  -->
  <xsl:include href="helpers/creator.xsl"/>
  <xsl:template match="/">
    <!-- use the following bit of code to include the stylesheet to display it in Heurist publishing wizard
      otherwise it will be ommited-->
    <!-- begin including code -->
    <xsl:comment>
      <!-- name (desc.) that will appear in dropdown list -->
      [name]DIU Bulletin with extended text[/name]
      <!-- match the name of the stylesheet-->
      [output]pub_diu-bulletin-full[/output]
    </xsl:comment>
    <!-- end including code -->

    <html>
      <head>

        <style type="text/css">
          body {font-family:Verdana,Helvetica,Arial,sans-serif; font-size:11px; }
          td { vertical-align: top; }
          .reftype {
          color: #999999;

          }
        </style>
      </head>
      <body>
        <xsl:attribute name="pub_id">
          <xsl:value-of select="/hml/query[@pub_id]"/>
        </xsl:attribute>
          <xsl:apply-templates select="/hml/records/record"></xsl:apply-templates>
      </body>
    </html>

  </xsl:template>
  <!-- main template -->
  <xsl:template match="/hml/records/record">

      <!-- HEADER  -->
      <table style="margin-bottom: 20px; ">
          <tr>
            <td>
              <a target="_new"
                href="{$hBase}records/editrec/edit.html?bib_id={id}">
                <img style="border: none;"
                  src="{$hBase}common/images/edit_pencil_16x16.gif"/>
              </a>
              </td>
            <td >
              <b>
                <xsl:value-of select="title"/>
              </b>
            </td>
          </tr>


          <xsl:if test="url != ''">
            <tr>
              <td>
              </td>
              <td>
                <a href="{url}">
                  <xsl:choose>
                    <xsl:when test="string-length(url) &gt; 50">
                      <xsl:value-of select="substring(url, 0, 50)"/> ... </xsl:when>
                    <xsl:otherwise>
                      <xsl:value-of select="url"/>
                    </xsl:otherwise>
                  </xsl:choose>
                </a>
              </td>
            </tr>
          </xsl:if>

        <!-- DETAIL LISTING -->

        <!--put what is being grouped in a variable-->
        <xsl:variable name="details" select="detail"/>
        <!--walk through the variable-->

      <xsl:for-each select="detail">
        <xsl:if test="self::node()[@id= 303 or @id=191]">
            <tr>
              <td>
              </td>
              <!--revisit all-->
              <td>
                      <xsl:value-of select="."/>
              </td>
            </tr>
        </xsl:if>
        </xsl:for-each>


        <xsl:if test="woot !=''">
       <tr>

         <td>
         </td>
         <td>
           <xsl:call-template name="woot_content"></xsl:call-template>
         </td>
       </tr>
        </xsl:if>
      </table>
    <!--/xsl:element-->

  </xsl:template>

 <!-- helper templates -->
  <xsl:template name="logo">
    <xsl:param name="id"></xsl:param>
    <xsl:if test="self::node()[@id =$id]">
      <xsl:element name="a">
        <xsl:attribute name="href"><xsl:value-of select="self::node()[@id =$id]/url"/></xsl:attribute>
        <xsl:element name="img">
          <xsl:attribute name="src"><xsl:value-of select="self::node()[@id =$id]/thumbURL"/></xsl:attribute>
          <xsl:attribute name="border">0</xsl:attribute>
        </xsl:element>
      </xsl:element>
    </xsl:if>
  </xsl:template>
  <xsl:template name="file">
    <xsl:param name="id"></xsl:param>
    <xsl:if test="self::node()[@id =$id]">
      <xsl:element name="a">
        <xsl:attribute name="href"><xsl:value-of select="self::node()[@id =$id]/url"/></xsl:attribute>
        <xsl:value-of select="origName"/>
      </xsl:element>  [<xsl:value-of select="size"/>]
    </xsl:if>
  </xsl:template>
  <xsl:template name="start-date" match="detail[@id=177]">
    <xsl:if test="self::node()[@id =177]">
      <xsl:value-of select="self::node()[@id =177]/year"/>
    </xsl:if>
  </xsl:template>
  <xsl:template name="url">
    <xsl:param name="key"></xsl:param>
    <xsl:param name="value"></xsl:param>
    <xsl:element name="a">
      <xsl:attribute name="href"><xsl:value-of select="$key"/></xsl:attribute>
      <xsl:value-of select="$value"/>
    </xsl:element>
  </xsl:template>
  <xsl:template name="woot_content">
    <xsl:if test="woot">
		<xsl:copy-of select="woot"/>
    </xsl:if>
  </xsl:template>


</xsl:stylesheet>