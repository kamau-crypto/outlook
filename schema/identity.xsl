<?xml version="1.0" ?>

<!-- IdentityTransform -->
    
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
   <!-- match any node with a record element with an onclick attribute if set
    parameter is met
    <xsl:template match="record">
        <xsl:param name="admin" />
        <xsl:copy>
            <xsl:attribute name="onclick">${website}.select_page(this)</xsl:attribute>      
            <xsl:apply-templates select="@* | node()" />
        </xsl:copy > 
    </xsl:template> -->
    
    <!-- match any node with a content element to have a tag p
    <xsl:template match="content">
        <xsl:copy>
            <xsl:attribute name="onclick">mutall_data.select_content(this)</xsl:attribute>      
            <xsl:apply-templates select="@* | node()" />
        </xsl:copy > 
    </xsl:template> -->
    
    <!-- match any node with a picture element to be placed inside a div with a tag img-->
    <xsl:template match="picture">
        <div>
            <img src="pictures/{@name}"/>
        </div>
    </xsl:template> 
    
    <!-- match any node with a icon element to have a tag img-->
    <xsl:template match="icon">
        <img src="pictures/{@name}"/>
    </xsl:template>
    <!-- match any service item to have a button with an anchor a tag -->
    <xsl:template match="service_item">
        <button>
            <xsl:apply-templates select="@* | node()" />
        </button>
    </xsl:template>
    
    <!-- Match any node, viz, root, attribute or element. Does this include the
    processing instructions?-->
    <xsl:template match="/ | @* | node()">
        <!-- Copy the current node -->
        <xsl:copy>
            <!-- Apply templates to all any child node--> 
            <xsl:apply-templates select="@* | node()" />
        </xsl:copy>
    </xsl:template>
</xsl:stylesheet>