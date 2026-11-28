<?php
/**
 * ASHBAB CAFE - Dynamic XML Sitemap for Google Search Console
 * File: sitemap.php (routed from /sitemap.xml)
 */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$baseUrl = rtrim(APP_URL ?: 'https://ashbabcafe.my.id', '/');
$today   = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($baseUrl . '/', ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?= htmlspecialchars($baseUrl . '/menu.php', ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= htmlspecialchars($baseUrl . '/about.php', ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= htmlspecialchars($baseUrl . '/tracking.php', ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>
</urlset>
