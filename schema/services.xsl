<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    
    <xsl:template match="service">
        
        <!-- Output the caption -->
        <li>
        <a href='#{@id}'>
            
                <xsl:value-of select="name"/>
        </a>
        </li>

    </xsl:template>
    <!-- 
    <xsl:template match="service">
        <xsl:copy>
            <ul>
           <xsl:attribute name="onclick">myfunction()</xsl:attribute>
           <xsl:attribute name="id">serv</xsl:attribute>
           <xsl:apply-templates/>
            </ul>
        </xsl:copy > 
        
    </xsl:template>-->
    <!-- Do not output text nodes-->
    <xsl:template match="text()"/>

    <!-- Outout the records as a list of items-->
    <xsl:template match="/">
        <p>
            <b>Services Offered</b>
        </p>
        <ul>
        <xsl:attribute name="onclick">myfunction()</xsl:attribute>
        <xsl:apply-templates/> 
        </ul>
        
    </xsl:template>

</xsl:stylesheet>
