<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<!--
 this style renders standard html
 author  Maria Shvedova
 last updated 10/09/2007 ms

 Modified by Maria to the DIU Bulletin format in November 2008

 Mofified further for events archive 12/12/2008 by Ian

  -->

	<xsl:template name="creator" match="detail/record" mode="creator">
		<xsl:choose>
			<xsl:when test="contains(title,',') ">
				<!-- display initials instead of a full first name, if applicable-->
				<xsl:variable name="lname">
					<xsl:value-of select="substring-before(title, ',')"/>
				</xsl:variable>
				<xsl:variable name="fname">
					<xsl:value-of select="substring-after(title, ', ')"/>
				</xsl:variable>
				<xsl:value-of select="$lname"/>&#xa0; <xsl:choose>
					<xsl:when test="contains($fname,' ') or contains($fname, '.')">
						<xsl:choose>
							<xsl:when test="string-length($fname) &gt; 4">
								<xsl:value-of select="substring($fname, 1, 1)"/>. </xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$fname"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="substring($fname, 1, 1)"/>. </xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="title"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="/">
		<!-- use the following bit of code to include the stylesheet to display it in Heurist publishing wizard
		  otherwise it will be ommited-->
		<!-- begin including code -->
		<xsl:comment>
		<!-- name (desc.) that will appear in dropdown list -->
		[name]DIU Events Archive[/name]
		<!-- match the name of the stylesheet-->
		[output]DIU_events_archive[/output]
		</xsl:comment>
		<!-- end including code -->

		<xsl:apply-templates select="/hml/records/record"></xsl:apply-templates>

	</xsl:template>

	<!-- main template -->
	<xsl:template match="/hml/records/record">
		<!-- HEADER  -->
		<div id="{id}" class="record">
			<p style="margin-bottom: 5px; ">
				<!--output the author(s)-->
				<xsl:for-each select="detail">
					<xsl:if test="self::node()[@id= 249]"> <!--  249 = person reference -->
						<!--revisit all-->
						<xsl:value-of select="record/title"/>
						<xsl:text> </xsl:text>
					</xsl:if>
					</xsl:for-each>
				<!--output the seminar title-->
					<i><b>
						<xsl:value-of select="title"/>
					</b></i>
			</p>

			<p style="margin-bottom: 5px; ">

			<xsl:if test="url != ''">
				<xsl:text> Files in Sydney eScholarship repository: </xsl:text>
					<a href="{url}">
					<xsl:choose>
						<xsl:when test="string-length(url) &gt; 50">
						<xsl:value-of select="substring(url, 0, 50)"/> ... </xsl:when>
						<xsl:otherwise>
						<xsl:value-of select="url"/>
						</xsl:otherwise>
					</xsl:choose>
					</a>
				</xsl:if>
			</p>

			<p style="margin-bottom: 5px; ">
			<!-- DETAIL LISTING -->
			<!--put what is being grouped in a variable-->
			<xsl:variable name="details" select="detail"/>
			<!--walk through the variable-->
			<xsl:for-each select="detail">
					<xsl:if test="self::node()[@id= 303 or @id=191]">
					<!--revisit all-->
					<xsl:value-of select="."/>
			</xsl:if>
			</xsl:for-each>
			</p>
		</div>
	</xsl:template>

	<!-- helper templates -->
	<xsl:template name="logo">
		<xsl:param name="id"></xsl:param>
		<xsl:if test="self::node()[@id =$id]">
			<xsl:element name="a">
				<xsl:attribute name="href"><xsl:value-of select="self::node()[@id =$id]/url"/></xsl:attribute>
				<xsl:element name="img">
				<xsl:attribute name="src"><xsl:value-of select="self::node()[@id =$id]/thumbUrl"/></xsl:attribute>
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

</xsl:stylesheet>