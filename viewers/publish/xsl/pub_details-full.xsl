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
      [name]Details (full, incl. text and thumbnail)[/name]
      <!-- match the name of the stylesheet-->
      [output]details-full[/output]
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
        <!--<script type="text/javascript">

          function displayResults(){

         document.getElementById('div-loading').style.display = 'none';
          var elts = document.getElementsByName("div-results");

          for (var i = 0; i &lt; elts.length; ++i) {
            var e = elts[i];
            e.style.display ='block';
            }
          }
        </script>-->
      </head>
      <body>
        <xsl:attribute name="pub_id">
          <xsl:value-of select="/hml/query[@pub_id]"/>
        </xsl:attribute>
        <!--<div id="div-loading" style="display:block;">Loading.. please wait </div>-->


          <xsl:apply-templates select="/hml/records/record"></xsl:apply-templates>


      </body>
    </html>

  </xsl:template>
  <!-- main template -->
  <xsl:template match="/hml/records/record">
   <!-- <xsl:element name="div">
      <xsl:attribute name="id">div-results</xsl:attribute>
      <xsl:attribute name="name">div-results</xsl:attribute>
      <xsl:attribute name="style">display:none;</xsl:attribute>

      <xsl:choose>
        <xsl:when test="position() = //rowcount">
          <script >
            displayResults();
          </script>
        </xsl:when>
        <xsl:otherwise>
          <script>
            document.getElementById('div-loading').style.display = 'block';
          </script>
        </xsl:otherwise>
      </xsl:choose>-->

      <!-- HEADER  -->
      <table>
          <tr>
            <td colspan="2" >
              <b><xsl:value-of select="id"/>: &#160;
                <img src="{$hBase}common/images/reftype-icons/{type/@id}.png" >
                  <xsl:attribute name="align">absbottom</xsl:attribute>
                </img>

                &#160;
                <xsl:value-of select="title"/>
              </b>
            </td>
          </tr>
          <tr>
            <td class="reftype">
              <nobr>Reference type</nobr>
            </td>
            <td>
              <xsl:value-of select="type"/>
            </td>
          </tr>
    <xsl:if test="modified !=''">
          <tr>
            <td class="reftype">
              <nobr>Last Updated</nobr>
            </td>
            <td>
              <xsl:value-of select="modified"/>
            </td>
          </tr>
    </xsl:if>
          <xsl:if test="url != ''">
            <tr>
              <td class="reftype">URL</td>
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

            <!--act on the first in document order-->
            <xsl:if test="generate-id(.)=
              generate-id($details[@id=current()/@id][1]) and self::node()[@id!= 249]">
              <tr>
                <td class="reftype" width="150">
                  <xsl:choose>
                    <xsl:when test="@name !=''">
                      <xsl:value-of select="@name"/>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:value-of select="@type"/>
                    </xsl:otherwise>
                  </xsl:choose>
                </td>
                   <!--revisit all-->
                <td>
              <xsl:for-each select="$details[@id=current()/@id]">
                <xsl:sort select="."/>
              <xsl:choose>
                <xsl:when test="self::node()[@id!= 222 and @id!= 221 and @id!=177 and @id != 223 and @id != 231 and @id != 268 and @id !=256 and @id!=304 and @id != 224]">
                  <xsl:value-of select="."/>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:if test="self::node()[@id= 177]">
                    <xsl:call-template name="start-date"></xsl:call-template>
                  </xsl:if>
                  <xsl:if test="self::node()[@id= 222 or @id= 223 or  @id= 224]">
                    <xsl:call-template name="logo">
                      <xsl:with-param name="id"><xsl:value-of select="@id"/></xsl:with-param>
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="self::node()[@id= 231 or @id=221]">
                    <xsl:call-template name="file">
                      <xsl:with-param name="id"><xsl:value-of select="@id"/></xsl:with-param>
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="self::node()[@id= 268 or @id=304]">
                    <xsl:call-template name="url">
                      <xsl:with-param name="key"><xsl:value-of select="."/></xsl:with-param>
                      <xsl:with-param name="value"><xsl:value-of select="."/></xsl:with-param>
                    </xsl:call-template>
                  </xsl:if>
                  <xsl:if test="self::node()[@id= 256]">
                    <xsl:call-template name="url">
                      <xsl:with-param name="key"><xsl:value-of select="."/></xsl:with-param>
                      <xsl:with-param name="value"><xsl:value-of select="."/></xsl:with-param>
                    </xsl:call-template>
                  </xsl:if>
                </xsl:otherwise>
              </xsl:choose><br/>
              </xsl:for-each>
                </td>
              </tr></xsl:if>
          </xsl:for-each>
    <!-- POINTER LISTING -->
          <xsl:variable name="pointer" select="detail"/>
          <!--walk through the variable-->
          <xsl:for-each select="detail">

            <!--act on the first in document order-->
            <xsl:if test="generate-id(.)=
              generate-id($pointer[@id=current()/@id][1]) and self::node()[@id= 249]">
              <tr>
                <td class="reftype" width="150">
                  <xsl:choose>
                    <xsl:when test="@name !=''">
                      <xsl:value-of select="@name"/>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:value-of select="@type"/>
                    </xsl:otherwise>
                  </xsl:choose>
                </td>
                <td>
              <!--revisit all-->
              <xsl:for-each select="$pointer[@id=current()/@id]">
              <xsl:choose>
                <xsl:when test="self::node()[@id=158]">
                  <xsl:apply-templates select="." mode="creator"/>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:value-of select="record/title"/>
                </xsl:otherwise>
              </xsl:choose>
                <br/>
              </xsl:for-each>
                </td>
              </tr>
            </xsl:if>
          </xsl:for-each>
    <!-- RELATED LISTING -->
    <xsl:variable name="relation" select="relationships"/>
    <!--walk through the variable-->
    <xsl:for-each select="relationships">

      <!--act on the first in document order-->
      <xsl:if test="generate-id(.)=
        generate-id($relation[@type=current()/@type][1])">
        <tr>
          <td class="reftype" width="150">
            <xsl:value-of select="@type"/>
          </td>
          <td>
            <!--revisit all-->
            <xsl:for-each select="$relation[@type=current()/@type]">

              <xsl:value-of select="record/title"/>
              <br/>
            </xsl:for-each>
          </td>
        </tr>
      </xsl:if>
    </xsl:for-each>
        <xsl:if test="woot !=''">
       <tr>
         <td  class="reftype">
           WYSIWIG Text
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
        <xsl:attribute name="href"><xsl:value-of select="self::node()[@id =$id]/file/url"/></xsl:attribute>
        <xsl:element name="img">
          <xsl:attribute name="src"><xsl:value-of select="self::node()[@id =$id]/file/thumbURL"/></xsl:attribute>
          <xsl:attribute name="border">0</xsl:attribute>
        </xsl:element>
      </xsl:element>
    </xsl:if>
  </xsl:template>
  <xsl:template name="file">
    <xsl:param name="id"></xsl:param>
    <xsl:if test="self::node()[@id =$id]">
      <xsl:element name="a">
        <xsl:attribute name="href"><xsl:value-of select="self::node()[@id =$id]/file/url"/></xsl:attribute>
        <xsl:value-of select="file/origName"/>
      </xsl:element>  [<xsl:value-of select="file/size"/>]
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