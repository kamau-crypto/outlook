<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    
    <xsl:template match="record">
        
        <!-- Output the caption -->
        
        <li><a href='#{@id}'>
            
                <xsl:value-of select="caption_"/>
        </a>
        </li>
        
        
    </xsl:template>

    <!-- Output the records as a list of items-->
    <xsl:template match="/">
        <ul id ="definer-list" style="list-style-type:none" class="definer">
        <xsl:apply-templates/> 
        <a href ="javascript:void(0);" class ="icon" onclick ="page_home.menuFunction()"
        >
            <img src="pictures/menu_icon.png" height="10" width="10"/>
        </a>
        </ul>
        
    </xsl:template>

</xsl:stylesheet>
