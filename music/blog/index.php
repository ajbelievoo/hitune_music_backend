<?php
/**
 * HiTune Music - Blog Page
 * music.hitune.in/blog
 */

// Share session across subdomains before BOF loads
if (!headers_sent()) {
    ini_set('session.cookie_domain', '.hitune.in');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '.hitune.in',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

require_once( dirname(__FILE__) . "/api/app/config.php" );
require_once( bof_root . "/loader.php" );
require_once( root . "/app/client/loader.php" );

// Get theme assets
$theme_url = web_address . 'themes/shady/';

// Sample blog posts data
$blog_posts = [
    [
        'id' => 1,
        'title' => 'How to Get Your Music on Spotify',
        'excerpt' => 'A complete guide to distributing your music to Spotify and other major streaming platforms. Learn the best practices for metadata, artwork, and release strategies.',
        'image' => 'https://images.unsplash.com/photo-1614680376593-902f74cf0d41?w=800',
        'category' => 'Distribution',
        'date' => '2024-04-15',
        'read_time' => '5 min read'
    ],
    [
        'id' => 2,
        'title' => 'Understanding Music Royalties',
        'excerpt' => 'Everything you need to know about performance royalties, mechanical royalties, and how to collect all your earnings as an independent artist.',
        'image' => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=800',
        'category' => 'Royalties',
        'date' => '2024-04-10',
        'read_time' => '8 min read'
    ],
    [
        'id' => 3,
        'title' => 'Music Marketing Strategies for 2024',
        'excerpt' => 'Discover the latest marketing techniques to grow your fanbase, increase streams, and build a sustainable music career in the digital age.',
        'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800',
        'category' => 'Marketing',
        'date' => '2024-04-05',
        'read_time' => '6 min read'
    ],
    [
        'id' => 4,
        'title' => 'The Importance of ISRC Codes',
        'excerpt' => 'Why ISRC codes matter for tracking your music and ensuring you get paid for every play across all platforms worldwide.',
        'image' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800',
        'category' => 'Guide',
        'date' => '2024-03-28',
        'read_time' => '4 min read'
    ],
    [
        'id' => 5,
        'title' => 'Building Your Artist Brand',
        'excerpt' => 'Tips for creating a memorable artist identity, from visual aesthetics to social media presence and storytelling.',
        'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800',
        'category' => 'Branding',
        'date' => '2024-03-20',
        'read_time' => '7 min read'
    ],
    [
        'id' => 6,
        'title' => 'Playlists: Your Gateway to Discovery',
        'excerpt' => 'How to pitch your music to playlist curators and increase your chances of getting featured on editorial playlists.',
        'image' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800',
        'category' => 'Promotion',
        'date' => '2024-03-15',
        'read_time' => '5 min read'
    ]
];

// Get current category filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;

// Filter posts if category is selected
if ($category_filter) {
    $blog_posts = array_filter($blog_posts, function($post) use ($category_filter) {
        return strtolower($post['category']) === strtolower($category_filter);
    });
}

// Get unique categories
$categories = array_unique(array_map(function($post) {
    return $post['category'];
}, $blog_posts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - HiTune Music</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
            font-weight: 800;
            font-size: 24px;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-icon i {
            font-size: 24px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .nav-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: #ff6b6b;
        }
        .nav-links a.active {
            color: #ff6b6b;
        }
        
        /* Main Content */
        .main-content {
            padding-top: 70px;
        }
        
        /* Hero */
        .blog-hero {
            padding: 80px 30px 60px;
            text-align: center;
            background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .blog-hero h1 {
            font-size: clamp(36px, 5vw, 56px);
            font-weight: 800;
            margin-bottom: 15px;
        }
        .blog-hero p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.6);
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Filter Bar */
        .filter-bar {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .filter-btn {
            padding: 10px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .filter-btn:hover {
            background: rgba(255, 107, 107, 0.1);
            border-color: rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
        }
        .filter-btn.active {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            border-color: transparent;
            color: #fff;
        }
        
        /* Blog Grid */
        .blog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 30px 80px;
        }
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        /* Blog Card */
        .blog-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .blog-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 107, 107, 0.3);
            box-shadow: 0 20px 40px rgba(255, 107, 107, 0.1);
        }
        .blog-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .blog-card-content {
            padding: 25px;
        }
        .blog-card-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .blog-category {
            padding: 5px 12px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .blog-date {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }
        .blog-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .blog-card p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        .blog-card-footer {
            padding: 0 25px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .read-time {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .read-more {
            color: #ff6b6b;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Footer */
        .footer {
            background: #0a0a0a;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px 30px 30px;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
        }
        .footer-brand p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            line-height: 1.6;
            margin-top: 15px;
        }
        .footer-section h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .footer-section a {
            display: block;
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 10px;
            transition: color 0.3s;
        }
        .footer-section a:hover {
            color: #ff6b6b;
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 40px auto 0;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .blog-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-content {
                grid-template-columns: 1fr 1fr;
            }
            .nav-links {
                display: none;
            }
        }
        @media (max-width: 600px) {
            .blog-grid {
                grid-template-columns: 1fr;
            }
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="logo">
                <div class="logo-icon">
                    <i class="mdi mdi-music"></i>
                </div>
                HiTune
            </a>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="/discover">Discover</a>
                <a href="/blog" class="active">Blog</a>
                <a href="/help">Help</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <section class="blog-hero">
            <h1>HiTune <span class="gradient-text">Blog</span></h1>
            <p>Insights, tips, and stories for independent artists and music lovers</p>
        </section>

        <div class="filter-bar">
            <a href="/blog.php" class="filter-btn <?php echo !$category_filter ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/blog.php?category=<?php echo urlencode($cat); ?>" class="filter-btn <?php echo $category_filter === $cat ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="blog-container">
            <div class="blog-grid">
                <?php foreach ($blog_posts as $post): ?>
                    <article class="blog-card">
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-card-image">
                        <div class="blog-card-content">
                            <div class="blog-card-meta">
                                <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                                <span class="blog-date"><?php echo date('M j, Y', strtotime($post['date'])); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                            <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        </div>
                        <div class="blog-card-footer">
                            <span class="read-time">
                                <i class="mdi mdi-clock-outline"></i>
                                <?php echo htmlspecialchars($post['read_time']); ?>
                            </span>
                            <span class="read-more">
                                Read <i class="mdi mdi-arrow-right"></i>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="/" class="logo">
                    <div class="logo-icon">
                        <i class="mdi mdi-music"></i>
                    </div>
                    HiTune
                </a>
                <p>Your music, everywhere. Stream millions of songs from independent artists worldwide.</p>
            </div>
            <div class="footer-section">
                <h4>Discover</h4>
                <a href="/">New Releases</a>
                <a href="/top">Top Charts</a>
                <a href="/genres">Genres</a>
                <a href="/artists">Artists</a>
            </div>
            <div class="footer-section">
                <h4>Resources</h4>
                <a href="/blog">Blog</a>
                <a href="/help">Help Center</a>
                <a href="https://web.hitune.in">Distribution</a>
            </div>
            <div class="footer-section">
                <h4>Company</h4>
                <a href="https://web.hitune.in/about">About</a>
                <a href="https://web.hitune.in/careers">Careers</a>
                <a href="https://web.hitune.in/contact">Contact</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> HiTune Music. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
