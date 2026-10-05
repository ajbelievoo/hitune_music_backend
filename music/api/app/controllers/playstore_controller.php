<?php
/**
 * Play Store Data Controller
 * Fetches app info from Google Play Store
 */
class PlayStoreController {
    
    private $cacheFile;
    private $cacheTime = 3600; // 1 hour cache
    
    public function __construct() {
        $this->cacheFile = __DIR__ . '/../../cache/playstore_cache.json';
    }
    
    /**
     * Get iYol app data from Play Store
     */
    public function getIyolAppData() {
        $packageName = 'com.vidmite.app';
        
        // Check cache first
        if ($this->isCacheValid()) {
            return json_decode(file_get_contents($this->cacheFile), true);
        }
        
        // Fetch from Play Store
        $data = $this->scrapePlayStore($packageName);
        
        if ($data) {
            // Save to cache
            $this->saveCache($data);
            return $data;
        }
        
        // Return default if fetch fails
        return $this->getDefaultData();
    }
    
    /**
     * Scrape Play Store page
     */
    private function scrapePlayStore($packageName) {
        $url = "https://play.google.com/store/apps/details?id=com.vidmite.app&hl=en_IN";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $html = curl_exec($ch);
        curl_close($ch);
        
        if (!$html) {
            return null;
        }
        
        return $this->parsePlayStoreData($html);
    }
    
    /**
     * Parse Play Store HTML
     */
    private function parsePlayStoreData($html) {
        $data = [
            'app_name' => 'iYol App',
            'package_name' => 'com.vidmite.app',
            'icon_url' => '',
            'rating' => '4.3',
            'downloads' => '100K+',
            'reviews_count' => '10K+',
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        // Extract icon URL
        if (preg_match('/"(https:\/\/play-lh\.googleusercontent\.com\/[^"]+)"/', $html, $matches)) {
            $data['icon_url'] = $matches[1];
        }
        
        // Extract rating
        if (preg_match('/"ratingValue":"([0-9.]+)"/', $html, $matches)) {
            $data['rating'] = $matches[1];
        }
        
        // Extract review count
        if (preg_match('/"ratingCount":([0-9]+)/', $html, $matches)) {
            $count = intval($matches[1]);
            $data['reviews_count'] = $this->formatNumber($count);
        }
        
        // Extract downloads/installs
        if (preg_match('/"Installs"[^>]*>([0-9,]+)\+?<\/div>/i', $html, $matches) ||
            preg_match('/([0-9,]+)\+?\s*downloads/i', $html, $matches)) {
            $data['downloads'] = str_replace(',', '', $matches[1]) . '+';
        }
        
        return $data;
    }
    
    /**
     * Format large numbers
     */
    private function formatNumber($num) {
        if ($num >= 1000000) {
            return round($num / 1000000, 1) . 'M+';
        }
        if ($num >= 1000) {
            return round($num / 1000, 1) . 'K+';
        }
        return $num . '+';
    }
    
    /**
     * Check if cache is valid
     */
    private function isCacheValid() {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        $age = time() - filemtime($this->cacheFile);
        return $age < $this->cacheTime;
    }
    
    /**
     * Save data to cache
     */
    private function saveCache($data) {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($data));
    }
    
    /**
     * Get default data if fetch fails
     */
    private function getDefaultData() {
        return [
            'app_name' => 'iYol App',
            'package_name' => 'com.vidmite.app',
            'icon_url' => '',
            'rating' => '4.3',
            'downloads' => '100K+',
            'reviews_count' => '10K+',
            'last_updated' => date('Y-m-d H:i:s'),
            'cached' => false
        ];
    }
}
