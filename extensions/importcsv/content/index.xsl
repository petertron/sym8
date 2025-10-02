<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="/">
<!--
        <h2>Import / Export CSV</h2>
-->
        <xsl:if test="data/@multilanguage">
            <ul class="importer-nav">
                <li>
                    <a href="#" rel="regular" class="active">Regular import/export</a>
                </li>
                <xsl:if test="data/@multilanguage">
                    <li>
                        <a href="#" rel="multilanguage">Multilingual field import/export</a>
                    </li>
                </xsl:if>
            </ul>
        </xsl:if>
        <div class="regular importer">
            <p>This is the default import/export tool. It allows you to import and export entire sections.</p>
            <div class="form-wrapper">
                <form class="form-left" method="post" enctype="multipart/form-data">
                    <h3>Import CSV</h3>
                    <p>Select a CSV file to upload:</p>
                    <label>
                        <input name="csv-file" type="file" accept=".csv,text/csv" required="required"/>
                    </label>
                    <p>Select a section as target:</p>
                    <label>
                        <select name="section" class="small" required="required">
                            <option value="" selected="selected" disabled="disabled">Please choose a section...</option>
                            <xsl:for-each select="data/sections/section">
                                <option value="{@id}">
                                    <xsl:value-of select="."/>
                                </option>
                            </xsl:for-each>
                        </select>
                    </label>
                    <p>In the next step, you can assign the fields and make various settings. <strong>Nothing is imported yet</strong>.</p>
                    <div class="actions">
                        <input name="import-step-2" type="submit" value="Next step"/>
                    </div>
                </form>
                <form class="form-right" method="post">
                    <h3>Export CSV</h3>
                    <p>Please choose a section you wish to export to a CSV file:</p>
                    <label>
                        <select name="section-export" class="small" required="required">
                            <option value="" selected="selected" disabled="disabled">Please choose a section...</option>
                            <xsl:for-each select="data/sections/section">
                                <option value="{@id}">
                                    <xsl:value-of select="."/>
                                </option>
                            </xsl:for-each>
                        </select>
                    </label>
                    <div class="actions">
                        <input name="export" type="submit" value="Export CSV"/>
                    </div>
                </form>
            </div>
        </div>
        <xsl:if test="data/@multilanguage = 'yes'">
            <div class="multilanguage importer">
                <p>It appears that you have the <a href="https://github.com/6ui11em/multilingual_field">multilingual field extension</a> installed. You can export and import the content of these fields individualy here.</p>
                <p>
                    This function is great if you need to export content to send as a CSV file to a translation agency. The ID of the entry is also stored in the CSV file to assure a correct import.
                </p>
                <div class="form-wrapper">
                    <form class="form-left" method="post" enctype="multipart/form-data">
                        <h3>Import CSV</h3>
                        <p>Select a CSV file to upload:</p>
                        <label>
                            <input name="csv-file-ml" type="file" accept=".csv,text/csv" required="required"/>
                        </label>
                        <p>
                            Please choose the field you wish to import into:
                        </p>
                        <label>
                            <select name="multilanguage-field-import" class="small" required="required">
                                <option value="" selected="selected" disabled="disabled">Please choose a section...</option>
                                <xsl:for-each select="data/multilanguage/field">
                                    <xsl:sort select="." />
                                    <option value="{@id}">
                                        <xsl:value-of select="."/>
                                    </option>
                                </xsl:for-each>
                            </select>
                        </label>
                        <div class="actions">
                            <input name="multilanguage-import" type="submit" value="Import CSV"/>
                        </div>
                    </form>
                    <form class="form-right" method="post">
                        <h3>Export CSV</h3>
                        <p>
                            Please choose the field you wish to export to a CSV file:
                        </p>
                        <label>
                            <select name="multilanguage-field-export" class="small" required="required">
                                <option value="" selected="selected" disabled="disabled">Please choose a section...</option>
                                <xsl:for-each select="data/multilanguage/field">
                                    <xsl:sort select="." />
                                    <option value="{@id}">
                                        <xsl:value-of select="."/>
                                    </option>
                                </xsl:for-each>
                            </select>
                        </label>
                        <div class="actions">
                            <input name="multilanguage-export" type="submit" value="Export CSV"/>
                        </div>
                    </form>
                </div>
            </div>
        </xsl:if>
    </xsl:template>

</xsl:stylesheet>
