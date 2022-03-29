<?xml version="1.0" ?>

<!-- IdentityTransform -->
    
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    <!-- Prefix caption with Our -->
    <xsl:template match="caption_">
        <xsl:copy>
            
            <!-- Apply templates to all any child node--> 
            Our<xsl:value-of select="text()" />
        </xsl:copy > 
    </xsl:template>  
    
    <xsl:template match="tagline">
        <textarea>
           <xsl:attribute name="onclick">mutall_data_edit()</xsl:attribute>
           <xsl:attribute name="id">tag</xsl:attribute>
            <xsl:apply-templates select="@* | node()" />
        </textarea>
        
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