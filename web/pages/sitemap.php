<?php
/**
 * HiTune Music Distribution - Dynamic XML Sitemap
 * Served at /sitemap.xml via .htaccess rewrite
 */
header('Content-Type: application/xml; charset=UTF-8');

$base    = 'https://web.hitune.in';
$lastmod = date('Y-m-d', filemtime(__FILE__));

$urls = [
    // [path, changefreq, priority]
    ['/',                  'weekly',  '1.0'],
    ['/index.php?q=pricing',  'weekly',  '0.9'],
    ['/index.php?q=stores',   'monthly', '0.8'],
    ['/index.php?q=sell',     'monthly', '0.8'],
    ['/index.php?q=services', 'monthly', '0.8'],
    ['/index.php?q=about',    'monthly', '0.7'],
    ['/index.php?q=help',     'monthly', '0.8'],
    ['/index.php?q=contact',  'monthly', '0.7'],
    ['/index.php?q=careers',  'monthly', '0.7'],
    ['/index.php?q=isrc',     'monthly', '0.7'],
    ['/index.php?q=publishing','monthly','0.7'],
    ['/index.php?q=privacy',  'monthly', '0.7'],
    ['/index.php?q=terms',    'monthly', '0.7'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as [$path, $changefreq, $priority]): ?>
    <url>
        <loc><?php echo htmlspecialchars($base . $path, ENT_XML1); ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq><?php echo $changefreq; ?></changefreq>
        <priority><?php echo $priority; ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
