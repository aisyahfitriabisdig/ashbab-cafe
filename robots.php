<?php
/**
 * ASHBAB CAFE - Dynamic robots.txt for Google Search Console & Crawlers
 * File: robots.php (routed from /robots.txt)
 */
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$baseUrl = rtrim(APP_URL ?: 'https://ashbabcafe.my.id', '/');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Allow: /index.php\n";
echo "Allow: /menu.php\n";
echo "Allow: /tracking.php\n";
echo "Allow: /about.php\n";
echo "Allow: /assets/\n";
echo "Disallow: /admin/\n";
echo "Disallow: /cashier/\n";
echo "Disallow: /ajax/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /api/\n";
echo "Disallow: /database/\n";
echo "Disallow: /login.php\n";
echo "Disallow: /logout.php\n";
echo "Disallow: /cart.php\n";
echo "Disallow: /checkout.php\n";
echo "\n";
echo "Sitemap: {$baseUrl}/sitemap.xml\n";
