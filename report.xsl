<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/">
<html>
<head>
    <title>AidLink Report</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f8fb; color: #102033; padding: 32px; }
        h1 { font-size: 36px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 14px; border: 1px solid #dce6ef; text-align: left; }
        th { background: #0f766e; color: white; }
    </style>
</head>
<body>
    <h1>Aid Request Report</h1>
    <p>Generated from AidLink donation and volunteer coordination records.</p>
    <table>
        <tr>
            <th>Recipient</th>
            <th>Category</th>
            <th>Need</th>
            <th>Urgency</th>
            <th>Location</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <xsl:for-each select="aidRequests/request">
            <tr>
                <td><xsl:value-of select="fullname" /></td>
                <td><xsl:value-of select="category" /></td>
                <td><xsl:value-of select="location" /></td>
                <td><xsl:value-of select="status" /></td>
                <td><xsl:value-of select="created_at" /></td>
            </tr>
        </xsl:for-each>
    </table>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
