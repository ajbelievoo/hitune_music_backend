<?php

if (!defined("root") || !defined("bof_root")) die;

class search extends bof_type_class
{

  private $stopWords = [];
  protected $PID = null;
  protected $GID = null;
  public function _cli($string)
  {
    if ($this->PID && $this->GID)
      bof()->cronjob->log_p($this->PID, $this->GID, $string);
  }
  public function __construct()
  {
    $this->stopWords = $this->__tokenizer_stop_words(false);
  }
  protected function __tokenizer_stop_words($lang)
  {

    $words = array(
      "en" => array(
        'a', 'an', 'any', 'are', 'aren\'t', 'as', 'at', 'be', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by', 'can\'t', 'cannot', 'could', 'couldn\'t',
        'did', 'didn\'t', 'do', 'does', 'doesn\'t', 'doing', 'don\'t', 'during', 'each', 'for', 'from', 'had', 'hadn\'t', 'has', 'hasn\'t', 'have', 'haven\'t', 'having', 'he', 'he\'d', 'he\'ll', 'he\'s', 'here',
        'here\'s', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'how\'s', 'i', 'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'if', 'in',
        'is', 'isn\'t', 'it', 'it\'s', 'its', 'itself', 'let\'s', 'me', 'more', 'most', 'mustn\'t', 'my', 'myself', 'no',
        'nor', 'not', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'ours', 'out', 'over',
        'own', 'same', 'shan\'t', 'should', 'shouldn\'t', 'so', 'some', 'such', 'than',
        'that', 'that\'s', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', 'there\'s', 'these', 'they', 'the',
        'they\'d', 'they\'ll', 'they\'re', 'they\'ve', 'this', 'those', 'through', 'to', 'too', 'under', 'until', 'up', 'very',
        'was', 'wasn\'t', 'we', 'we\'d', 'we\'ll', 'we\'re', 'we\'ve', 'were', 'weren\'t',
        'you', 'you\'d', 'you\'ll', 'you\'re', 'you\'ve', 'your', 'yours', 'yourself', 'yourselves'
      ),
      "es" => array(
        "a",
        "al",
        "algo",
        "algunas",
        "algunos",
        "ante",
        "antes",
        "como",
        "con",
        "contra",
        "cual",
        "cuando",
        "de",
        "del",
        "desde",
        "donde",
        "durante",
        "e",
        "el",
        "ella",
        "ellas",
        "ellos",
        "en",
        "entre",
        "era",
        "erais",
        "eran",
        "eras",
        "eres",
        "es",
        "esa",
        "esas",
        "ese",
        "eso",
        "esos",
        "esta",
        "estaba"
      ),
      "fr" => array(
        "a",
        "à",
        "au",
        "aux",
        "avec",
        "ce",
        "ces",
        "dans",
        "de",
        "des",
        "du",
        "elle",
        "en",
        "et",
        "eux",
        "il",
        "ils",
        "je",
        "la",
        "le",
        "les",
        "leur",
        "lui",
        "ma",
        "mais",
        "me",
        "même",
        "mes",
        "moi",
        "mon",
        "ne",
        "nos",
        "notre",
        "nous",
        "on",
        "ou",
        "par",
        "pas"
      ),
      "de" => array(
        "aber",
        "alle",
        "allem",
        "allen",
        "aller",
        "alles",
        "als",
        "also",
        "am",
        "an",
        "ander",
        "andere",
        "anderem",
        "anderen",
        "anderer",
        "anderes",
        "anderm",
        "andern",
        "anderr",
        "anders",
        "auch",
        "auf",
        "aus",
        "bei",
        "bin",
        "bis",
        "bist",
        "da",
        "damit",
        "dann",
        "der",
        "den",
        "des",
        "dem",
        "die",
        "das",
        "daß",
        "derselbe",
        "derselben",
        "denselben"
      ),
      "it" => array(
        "ad",
        "al",
        "allo",
        "ai",
        "agli",
        "all",
        "agl",
        "alla",
        "alle",
        "con",
        "col",
        "coi",
        "da",
        "dal",
        "dallo",
        "dai",
        "dagli",
        "dall",
        "dagl",
        "della",
        "delle",
        "dentro",
        "di",
        "del",
        "dello",
        "dei",
        "degli",
        "dell",
        "degl",
        "davanti",
        "dietro",
        "dopo",
        "durante",
        "e",
        "ed",
        "essendo",
        "fa",
        "faccia",
        "facciamo",
        "facciano"
      ),
      "pt" => array(
        "à",
        "adeus",
        "agora",
        "ainda",
        "além",
        "algo",
        "algumas",
        "alguns",
        "ali",
        "ampla",
        "amplas",
        "amplo",
        "amplos",
        "ano",
        "anos",
        "antes",
        "ao",
        "aos",
        "apenas",
        "apoio",
        "após",
        "aquela",
        "aquelas",
        "aquele",
        "aqueles",
        "aqui",
        "aquilo",
        "as",
        "às",
        "assim",
        "até",
        "através",
        "atrás",
        "atual",
        "atuais",
        "aí",
        "baixo",
        "bastante",
        "bem",
        "boa"
      ),
      "ru" => array(
        "а",
        "без",
        "более",
        "больше",
        "будет",
        "будто",
        "бы",
        "был",
        "была",
        "были",
        "было",
        "быть",
        "в",
        "вам",
        "вас",
        "вдруг",
        "ведь",
        "во",
        "вот",
        "впрочем",
        "все",
        "всегда",
        "всего",
        "всех",
        "всю",
        "вы",
        "где",
        "да",
        "даже",
        "два",
        "для",
        "до",
        "другой",
        "его",
        "ее",
        "ей",
        "ему",
        "если",
        "есть",
        "еще"
      ),
      "zh" => array(
        "一",
        "一些",
        "一切",
        "一旦",
        "一样",
        "一次",
        "一种",
        "一直",
        "一般",
        "七",
        "万一",
        "三",
        "上",
        "下",
        "不",
        "不仅",
        "不但",
        "不光",
        "不单",
        "不只",
        "不如",
        "不妨",
        "不尽",
        "不尽然",
        "不得",
        "不怕",
        "不惟",
        "不成",
        "不拘",
        "不料",
        "不是",
        "不比",
        "不然",
        "不特",
        "不独",
        "不管",
        "不至于",
        "不若",
        "不论",
        "不过"
      ),
      "ja" => array(
        "あ",
        "あい",
        "あいつ",
        "あう",
        "あお",
        "あか",
        "あが",
        "あがる",
        "あきらか",
        "あきらめる",
        "あく",
        "あける",
        "あげる",
        "あこがれる",
        "あさい",
        "あさって",
        "あし",
        "あす",
        "あすこ",
        "あたえる",
        "あたり",
        "あたる",
        "あちこち",
        "あっ",
        "あっち",
        "あつ",
        "あと",
        "あな",
        "あなた",
        "あの",
        "あばた",
        "あぶない",
        "あふれる",
        "あまり",
        "あまりに",
        "あまる",
        "あめ",
        "あやうい",
        "あら",
        "あらた",
        "あらためる"
      ),
      "ar" => array(
        "أن",
        "في",
        "على",
        "من",
        "إلى",
        "هذا",
        "هذه",
        "هذا",
        "هؤلاء",
        "ذلك",
        "ذلكم",
        "ذلكن",
        "هنا",
        "هناك",
        "وهو",
        "وهي",
        "ولكن",
        "عن",
        "عند",
        "قبل",
        "مع",
        "ما",
        "ماذا",
        "من",
        "هل",
        "هو",
        "هي",
        "أو",
        "إذا",
        "كان",
        "كانت",
        "كل",
        "كما",
        "لا",
        "مثل",
        "منذ",
        "نحو"
      ),
      "cs" => array(
        "a",
        "aby",
        "ahoj",
        "aj",
        "ale",
        "anebo",
        "ani",
        "ano",
        "asi",
        "aspoň",
        "atd",
        "atp",
        "až",
        "bez",
        "beze",
        "blízko",
        "bohužel",
        "brzo",
        "bude",
        "budem",
        "budeme",
        "budete",
        "budeš",
        "budou",
        "budu",
        "by",
        "bych",
        "bychom",
        "byl",
        "byla",
        "byli",
        "bylo",
        "byly",
        "bys",
        "být",
        "býti"
      ),
      "fa" => array(
        "آباد",
        "آره",
        "آری",
        "آمد",
        "آمدن",
        "آمده",
        "آمدی",
        "آن",
        "آنان",
        "آنجا",
        "آنها",
        "آنچه",
        "آورد",
        "آوردن",
        "آورده",
        "آوردی",
        "آیا",
        "آید",
        "آینده",
        "اتفاقاً",
        "اثرِ",
        "اخیر",
        "از",
        "ازجمله",
        "اساساً",
        "است",
        "استفاده",
        "اش",
        "اشاره",
        "اکنون",
        "الا",
        "البته",
        "البتّه",
        "الهی",
        "ام",
        "اما",
        "امروز",
        "امسال",
        "من",
        "تو"
      ),
      "fi" => array(
        "aika",
        "ain",
        "aina",
        "ainakin",
        "ainoa",
        "aiti",
        "ajaa",
        "alas",
        "alempi",
        "alkuisin",
        "alkuun",
        "alla",
        "alle",
        "aloitamme",
        "aloitan",
        "aloitat",
        "aloitatte",
        "aloitattivat",
        "aloitettava",
        "aloitettevaksi",
        "aloitettu",
        "aloitimme",
        "aloitin",
        "aloitit",
        "aloititte",
        "aloittaa",
        "aloittamatta",
        "aloitti",
        "aloittivat",
        "alta",
        "aluksi",
        "alussa",
        "alusta",
        "annettavaksi",
        "annettava",
        "annettu",
        "ansiosta",
        "antaa"
      ),
      "hi" => array(
        "का",
        "के",
        "की",
        "को",
        "की",
        "है",
        "हैं",
        "और",
        "से",
        "कर",
        "हो",
        "होता",
        "होती",
        "होते",
        "करना",
        "करता",
        "करते",
        "अपनी",
        "अपने",
        "उसके",
        "उनके",
        "उसकी",
        "उनकी",
        "उसको",
        "उनको",
        "वहाँ",
        "वहीं",
        "वहां",
        "वहाँ",
        "वहीं",
        "वहां",
        "जैसा",
        "जैसे",
        "तथा",
        "कि",
        "जो",
        "हूँ",
        "होता"
      ),
      "hu" => array(
        "a",
        "aby",
        "ahoj",
        "aj",
        "ale",
        "anebo",
        "ani",
        "ano",
        "asi",
        "aspoň",
        "atd",
        "atp",
        "až",
        "bez",
        "beze",
        "blízko",
        "bohužel",
        "brzo",
        "bude",
        "budem",
        "budeme",
        "budete",
        "budeš",
        "budou",
        "budu",
        "by",
        "bych",
        "bychom",
        "byl",
        "byla",
        "byli",
        "bylo",
        "byly",
        "bys",
        "být",
        "býti"
      ),
      "id" => array(
        "ada",
        "adalah",
        "adanya",
        "adapun",
        "agak",
        "agaknya",
        "agar",
        "akan",
        "akankah",
        "akhir",
        "akhiri",
        "akhirnya",
        "aku",
        "akulah",
        "amat",
        "amatlah",
        "anda",
        "andalah",
        "antar",
        "antara",
        "antaranya",
        "apa",
        "apaan",
        "apabila",
        "apakah",
        "apalagi",
        "apatah",
        "artinya",
        "asal",
        "asalkan",
        "atas",
        "atau",
        "ataukah",
        "ataupun",
        "awal",
        "awalnya",
        "bagai",
        "bagaikan"
      ),
      "tr" => array(
        "acaba",
        "ama",
        "aslında",
        "az",
        "bazı",
        "belki",
        "biri",
        "birkaç",
        "birşey",
        "biz",
        "bu",
        "çok",
        "çünkü",
        "da",
        "daha",
        "de",
        "defa",
        "diye",
        "eğer",
        "en",
        "gibi",
        "hem",
        "hep",
        "hepsi",
        "her",
        "hiç",
        "için",
        "ile",
        "ise",
        "kez",
        "ki",
        "kim",
        "mı",
        "mu",
        "mü",
        "nasıl",
        "ne",
        "neden",
        "nerde"
      ),
      "nl" => array(
        "aan",
        "als",
        "bij",
        "dat",
        "de",
        "den",
        "der",
        "des",
        "deze",
        "die",
        "dit",
        "door",
        "een",
        "en",
        "enige",
        "enkele",
        "enz",
        "etc",
        "haar",
        "het",
        "hun",
        "ik",
        "in",
        "is",
        "je",
        "met",
        "na",
        "naar",
        "niet",
        "nooit",
        "nu",
        "of",
        "om",
        "onder",
        "ons",
        "onze",
        "ook",
        "over",
        "te"
      ),
      "sv" => array(
        "alla",
        "allt",
        "att",
        "av",
        "bara",
        "bli",
        "blir",
        "blev",
        "bli",
        "bort",
        "bra",
        "där",
        "då",
        "de",
        "dem",
        "den",
        "deras",
        "dess",
        "det",
        "detta",
        "dig",
        "dina",
        "dit",
        "ditt",
        "dock",
        "du",
        "efter",
        "egen",
        "eller",
        "en",
        "en",
        "er",
        "era",
        "ert",
        "ett",
        "ett",
        "för",
        "från"
      ),
      "pl" => array(
        "a",
        "aby",
        "ach",
        "acz",
        "aczkolwiek",
        "aj",
        "ale",
        "alez",
        "ależ",
        "ani",
        "az",
        "aż",
        "bardziej",
        "bardzo",
        "beda",
        "bedzie",
        "bez",
        "deda",
        "będą",
        "bede",
        "będę",
        "będzie",
        "bo",
        "bowiem",
        "by",
        "byc",
        "być",
        "byl",
        "byla",
        "byli",
        "bylo",
        "byly",
        "był",
        "była",
        "było",
        "były"
      ),
      "el" => array(
        "αλλα",
        "αν",
        "αντι",
        "απο",
        "αυτα",
        "αυτεσ",
        "αυτη",
        "αυτο",
        "αυτοι",
        "αυτοσ",
        "αυτουσ",
        "αυτων",
        "αἱ",
        "αἳ",
        "αἷς",
        "αὐτόσ",
        "αὐτὸς",
        "αὖ",
        "γάρ",
        "γα",
        "γα^",
        "γε",
        "για",
        "γοῦν",
        "γὰρ",
        "δ'",
        "δέ",
        "δή",
        "δαί",
        "δαίσ",
        "δαὶ",
        "δαὶς",
        "δε",
        "δεν",
        "δι'",
        "διά"
      ),
      "no" => array(
        "alle",
        "andre",
        "av",
        "både",
        "bare",
        "begge",
        "ble",
        "blei",
        "bli",
        "blir",
        "blitt",
        "bort",
        "bra",
        "bruke",
        "både",
        "båe",
        "da",
        "de",
        "deg",
        "dei",
        "deim",
        "deira",
        "deires",
        "dem",
        "den",
        "denne",
        "der",
        "dere",
        "deres",
        "det",
        "dette",
        "di",
        "din",
        "disse",
        "ditt",
        "du",
        "dykk",
        "dykkar"
      )
    );

    if (!$lang) {
      $_words = [];
      foreach ($words as $lang => $lWords) {
        $_words = array_merge($_words, $lWords);
      }
      return $_words;
    }
    return !empty($words[$lang]) ? $words[$lang] : [];
  }
  protected function __tokenizer_detect_language($string)
  {

    require_once(bof_root . "/app/core/third/landrok_language-detector/vendor/autoload.php");
    $detector = new LanguageDetector\LanguageDetector();
    $lang = $detector->evaluate($string)->getLanguage();
    return (string) $lang;
  }
  protected function __tokenize($string)
  {

    // $lang = $this->__tokenizer_detect_language($string);
    // die($lang);
    // $this->stopWords = $this->__tokenizer_stop_words($lang);
    $string = htmlspecialchars_decode(strval($string), ENT_QUOTES);
    $string = mb_strtolower($string, "utf-8");
    $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);

    $tokens = preg_split('/\s+/', $string);

    /*$Ctokens = array_filter($tokens, function ($token) {
      return $token && !in_array($token, $this->stopWords, true);
    });

    var_dump($tokens,$Ctokens);
    die;

    $tokens = !empty($Ctokens) ? $Ctokens : $tokens;*/

    require_once(bof_root . "/app/core/third/teamtnt_tntsearch/vendor/autoload.php");

    $Stokens = array_map(function ($token) {
      return TeamTNT\TNTSearch\Stemmer\PorterStemmer::stem($token);
    }, $tokens);

    if (!$Stokens)
      return;

    $Ctokens = array_filter($tokens, function ($token) {
      return mb_strlen($token, "utf-8") > 1;
    });

    if (!$Ctokens)
      return;

    return $Ctokens;
  }
  protected function __record_progress( $object_name ){

    $done = bof()->db->_select(array(
      "table" => "_d_search_postings",
      "columns" => "COUNT(DISTINCT(object_id)) as c",
      "where" => array(
        [ "object_type", "=", $object_name ],
      ),
      "limit" => 1,
      "single" => true
    ));
    
    $done = !empty( $done["c"] ) ? $done["c"] : 0;
    $total = bof()->object->__get( $object_name )->count([]);

    $_stats_box = bof()->object->db_setting->get( "search_ii_p", [] );
    $_stats_box[ $object_name ] = array(
      "done" => $done,
      "total" => $total,
      "time" => time()
    );
    bof()->object->db_setting->set( "search_ii_p", json_encode( $_stats_box ), "json" );

  }

  public function generate_terms($PID, $GID)
  {

    $this->PID = $PID;
    $this->GID = $GID;
    bof()->db->disable_cache();

    $s = $e = $t = 0;

    foreach (bof()->bofAdmin->_get_objects() as $objectName => $objectArgs) {
      if ($objectArgs['search'] ? bof()->object->__get($objectName)->method_exists("clean_search_terms") : false) {
        list( $_s, $_e, $_t ) = $this->generate_terms_for_object($objectName);
        $s += $_s;
        $e += $_e;
        $t += $_t;
      }
    }

    return "Parsed " . number_format($s) . " items in " . round($e, 3) . " seconds. " . number_format($t) . " terms total";

  }
  public function generate_terms_for_object($object_name)
  {

    $object = bof()->object->__get($object_name);

    $pointer = 0;
    $maxPerObject = 1000 * 1000 * 1000 * 10;

    $__ss = $__ee = $__tt = 0;

    while ($pointer < $maxPerObject) {

      $items = $object->select(
        array(
          ["ID", "NOT IN", "SELECT object_id from _d_search_postings WHERE object_type = '{$object_name}'", true]
        ),
        array(
          "limit" => 1000,
          "single" => false,
          "clean" => false,
          "columns" => "ID",
          "cache_load_rt" => false
        )
      );

      $this->__record_progress($object_name);
      if ($items) {

        $_ss = 0;
        $_ee = 0;
        $_tt = 0;
        foreach ($items as $item) {

          $_s = microtime(true);
          $term_ids = $this->generate_terms_for_object_item($object_name, $object, $item);

          if ($term_ids) {
            $_tt += count($term_ids);
          } else {
            bof()->db->query("INSERT INTO _d_search_postings ( term_id, object_type, object_id, sugg_id, score ) VALUES ( null, '{$object_name}', {$item["ID"]}, null, 0 ) ");
          }
          $_e = microtime(true) - $_s;

          $_ss++;
          $_ee += $_e;
          $pointer++;
        }

        $__ss += $_ss;
        $__ee += $_ee;
        $__tt += $_tt;

        $this->_cli("{$object_name} -> Parsed " . number_format($_ss) . " items in " . round($_ee, 3) . " seconds. " . number_format($_tt) . " terms total");
        
        bof()->db->_update( array(
          "table" => "_bof_setting",
          "set" => array(
            [ "time_update", "now()", true ]
          ),
          "where" => array(
            [ "var", "=", "crond_stat" ]
          )
        ) );

      } else {
        break;
      }

    }

    if( $__ss > 1000 )
    $this->_cli("{$object_name} -> Total -> Parsed " . number_format($__ss) . " items in " . round($__ee, 3) . " seconds. " . number_format($__tt) . " terms total");

    return array(
      $__ss,
      $__ee,
      $__tt,
    );
  }
  public function generate_terms_for_object_item($object_name, $object, $item)
  {

    // bof()->db->query("DELETE FROM _d_search_postings WHERE object_type = '{$object_name}' AND object_id = {$item["ID"]}");

    $item_search_terms = $object->select(
      array(
        "ID" => $item["ID"]
      ),
      array(
        "search_terms" => true
      )
    );

    $item_term_ids = [];

    $c = 0;
    if ($item_search_terms) {

      foreach ($item_search_terms as $item_search_term => $item_search_term_score) {

        $item_search_term_tokens = $this->__tokenize($item_search_term);

        if (!$item_search_term_tokens) continue;

        $item_search_term_as_suggestion = trim(mb_substr(htmlspecialchars_decode(strval($item_search_term), ENT_QUOTES), 0, 90));
        // $item_search_term_as_suggestion_md5 = md5( $item_search_term_as_suggestion );

        if (($item_search_term_as_suggestion_exists = bof()->db->_select(array(
          "table" => "_d_search_suggestions",
          "where" => array(
            ["string", "=", $item_search_term_as_suggestion]
          ),
          "limit" => 1,
          "single" => true,
          "cache" => false
        )))) {
          $item_search_term_as_suggestion_id = $item_search_term_as_suggestion_exists["ID"];
        } else {
          $item_search_term_as_suggestion_id = bof()->db->_insert(array(
            "table" => "_d_search_suggestions",
            "set" => array(
              ["string", $item_search_term_as_suggestion],
              // ["string_hash", $item_search_term_as_suggestion_md5]
            )
          ));
        }

        foreach ($item_search_term_tokens as $item_search_term_token) {

          $item_search_term_token_score = count($item_search_term_tokens) == 1 ? $item_search_term_score : ($item_search_term_score / count($item_search_term_tokens) * 1.4);
          $item_search_term_token_score = round($item_search_term_token_score * 100);
          if (($item_search_term_token_exists = bof()->db->_select(array(
            "table" => "_d_search_terms",
            "where" => array(
              ["term", "=", $item_search_term_token]
            ),
            "limit" => 1,
            "single" => true
          )))) {
            $item_search_term_token_id = $item_search_term_token_exists["ID"];
          } else {
            $item_search_term_token_id = bof()->db->_insert(array(
              "table" => "_d_search_terms",
              "set" => array(
                ["term", $item_search_term_token]
              )
            ));
          }

          $item_term_ids[] = $item_search_term_token_id;

          bof()->db->query("INSERT INTO _d_search_postings ( term_id, object_type, object_id, sugg_id, score ) 
          VALUES ( {$item_search_term_token_id}, '{$object_name}', {$item["ID"]}, {$item_search_term_as_suggestion_id}, '{$item_search_term_token_score}' ) ");

          $c++;
        }
      }
    }

    /*bof()->db->_insert(array(
      "table" => "_d_search_indexed",
      "set" => array(
        ["object_id", $item["ID"]],
        ["object_type", $object_name]
      )
    ));*/

    return $item_term_ids;
  }

  public function __parse_query($query)
  {

    $query_tokens = $this->__tokenize($query);
    if (!$query_tokens) return;

    $term_groups = [];
    $terms = [];

    $_h = hash("md5", microtime(true) . uniqid() . $query);
    $_set = array(
      ["hash",  $_h],
      ["query", $query],
      ["token", implode(",", $query_tokens)],
      ["user_ip", bof()->request->get_userIP()["string"]]
    );

    if (bof()->user->check()->ID)
      $_set[] = ["user_id", bof()->user->check()->ID];

    $history_id = bof()->db->_insert(array(
      "table" => "_d_search_history",
      "set" => $_set
    ));

    foreach ($query_tokens as $i => $query_token) {

      $maxLev = $i + 1 == count($query_tokens) ? 3 : 0;

      $query_term_group_raw = bof()->db->_select(array(
        "table" => "_d_search_terms",
        "where" => array(
          ["term", "-LIKE%", $query_token]
        ),
        "limit" => 10,
        "order_by" => "`count`",
        "single" => false
      ));

      if (!$query_term_group_raw) continue;

      foreach ($query_term_group_raw as $query_term_raw) {

        $query_term_raw_lev = levenshtein($query_token, $query_term_raw["term"]);
        if ($query_term_raw_lev > $maxLev) continue;

        $query_term_raw_score = 1 - ($query_term_raw_lev ? ($query_term_raw_lev / 6) : 0);

        $terms[$query_term_raw["ID"]] = $query_term_raw_score;
        $term_groups[$query_token][$query_term_raw["ID"]] = $query_term_raw_score;
        if (empty($terms) ? true : count($terms) < 150)
          $term_history_terms[] = "( {$history_id}, " . ($i + 1) . ", {$query_term_raw["ID"]} )";
      }
    }

    if (!empty($term_history_terms)) {
      bof()->db->query("INSERT INTO _d_search_history_terms ( history_id, term_group, term_id ) VALUES " . implode(",", $term_history_terms));
    }

    return array(
      "tokens" => $query_tokens,
      "all" => $terms,
      "grouped" => $term_groups,
      "history" => array(
        "ID" => $history_id,
        "hash" => $_h
      )
    );
  }
  public function __perform_search($parsed_query, $args = [])
  {

    $object_type = null;
    $limit = 10;
    $page = 1;
    extract($args);

    $where = array();

    // if ( $object_type )
    // $where[] = [ "object_type", "=", $object_type ];

    $check_groupBy = bof()->db->is_only_full_groupby();
    $extraSearchQueries = $check_groupBy ? ", term_group, sugg_id" : "";

    $get = bof()->db->_select(array(
      "table" => "(
    SELECT term_group,object_type,object_id,sugg_id,score,p.term_id
    FROM _d_search_postings as p
    JOIN _d_search_history_terms as t ON p.term_id = t.term_id AND t.history_id = {$parsed_query["history"]["ID"]} " . (
        $object_type ? " AND p.object_type = '{$object_type}'" : ""
      ) . "
    LIMIT " . ($object_type ? "20000" : "50000") . "
        ) as m",
      "columns" => "term_group,object_type,object_id,sugg_id,COUNT(*) as c, SUM(score) as s,COUNT(DISTINCT(term_group)) as pc",
      "where" => $where,
      "group" => "GROUP BY " . ($object_type ? "object_id" : "object_type, object_id") . $extraSearchQueries,
      "order_by" => count($parsed_query["tokens"]) == 1 ? "s" : "pc DESC, c DESC, s",
      "single" => false,
      "limit" => $limit,
      "offset" => ($page-1)*$limit
    ));

    if (!$get)
      return;

    return $get;
  }
  /*public function __perform_suggs_search($parsed_query)
  {

    $get = bof()->db->_select(array(
      "table" => "(
    SELECT term_group,object_type,object_id,sugg_id,score,p.term_id
    FROM _d_search_postings as p
    JOIN _d_search_history_terms as t ON p.term_id = t.term_id AND t.history_id = {$parsed_query["history"]["ID"]}
    LIMIT 1000
        ) as m",
      "columns" => "term_group,string,object_type,object_id,sugg_id,COUNT(*) as c, SUM(score) as s,COUNT(DISTINCT(term_group)) as pc",
      "where" => array(),
      "joins" => "LEFT JOIN _d_search_suggestions ON _d_search_suggestions.ID = m.sugg_id",
      "group" => "GROUP BY object_type, object_id",
      "order_by" => count($parsed_query["tokens"]) == 1 ? "s" : "pc DESC, s DESC, c",
      "single" => false,
      "limit" => 10
    ));

    if (!$get)
      return;

    return array_unique(array_map(function ($item) {
      return $item["string"];
    }, $get));
  }*/
  public function sort(&$result, $query)
  {
    $items = [];
    foreach ($result as $item) {
      $items[$item["raw"]["ID"]] = $item;
    }
    $items_sorted = [];
    foreach ($query as $i) {
      if (!empty($items[$i["object_id"]]))
        $items_sorted[] = $items[$i["object_id"]];
    }
    $result = $items_sorted;
    return $result;
  }
  public function exe($args)
  {

    if ( bof()->object->core_setting->get("search_index_type") != "inverted_indexing" ){
      return $this->exe_old($args);
    }

    $query = null;
    extract($args);

    $parse_query = $this->__parse_query($query);

    $_tes = [];

    if ($parse_query["all"]) {

      $objects = bof()->bofClient->_get_objects();
      $widgets = array();
      $items = 0;

      $perform_search_all = $this->__perform_search($parse_query, $args);
      if ($perform_search_all) {

        $_sao = [];
        foreach ($perform_search_all as $si) {
          $siO = bof()->object->__get($si["object_type"]);
          $sii = $siO->select(
            array(
              "ID" => $si["object_id"]
            ),
            array(
              "as_widget" => true
            )
          );
          if ( !$sii ) continue;
          $sii["sub_data"] = bof()->object->language->turn($si["object_type"], [], ["uc_first" => true, "lang" => "users"]);
          $sii["buttons"] = bof()->bofClient->__parse_item_buttons(
            $si["object_type"],
            $siO,
            $sii["raw"],
            $siO->bof_client()["buttons"]
          );
          $sii["ot"] = $si["object_type"];
          $_sao[] = $sii;
        }

        $widgets["best"] = array(
          "ID" => "best",
          "display" => array(
            "type" => "slider",
            "title" => bof()->object->language->turn("best_results"),
            "link" => false,
            "pagination" => false,
            "slider_size" => "medium",
            "slider_rows" => 1,
            "slider_mason" => false
          ),
          "items" => $_sao
        );
      }

      foreach ($objects as $object_name => $object) {

        $the_object = bof()->object->__get($object_name);

        if (empty($object["search"])) continue;

        $_ti = microtime(true);
        $perform_search = $this->__perform_search($parse_query, array_merge($args, array(
          "object_type" => $object_name
        )));
        $_tes[] = microtime(true) - $_ti;

        if (!$perform_search) continue;

        $object_results = $the_object->select(
          array(
            "ID_in" => array_map(function ($item) {
              return $item["object_id"];
            }, $perform_search)
          ),
          array(
            "single" => false,
            "limit" => false,
            "as_widget" => true,
          )
        );

        if ($object_results) {

          $this->sort($object_results, $perform_search);

          foreach ($object_results as $_s => $i) {
            // $i["sub_data"] = bof()->object->language->turn($object_name, [], ["uc_first" => true, "lang" => "users"]);
            $items++;
          }
        }

        if (!$object_results)
          continue;

        $widgets[$object_name] = array(
          "ID" => $object_name,
          "display" => array(
            "type" => "slider",
            "title" => bof()->object->language->turn($object_name, [], ["uc_first" => true, "lang" => "users"]),
            "link" => count( $object_results ) >= 10 ? "search?query={$query}&type={$object_name}" : false,
            "pagination" => false,
            "slider_size" => "medium",
            "slider_rows" => 1,
            "slider_mason" => false
          ),
          "object" => array(
            "name" => $object_name,
          ),
          "items" => $object_results ? array_values($object_results) : $object_results
        );
      }

      $_te = round(array_sum($_tes), 3);
      bof()->db->query("UPDATE _d_search_history SET time_exe = {$_te} WHERE ID = {$parse_query["history"]["ID"]}");
    }

    if ( empty($items) ) {
      $widgets["nada"] = array(
        "display" => array(
          "type" => "html",
          "title" => bof()->object->language->turn("nothing_found", [], ["uc_first" => true, "lang" => "users"]),
          "html" => "<span style='opacity:0.5'>" . bof()->object->language->turn("nothing_found_s_tip", [], ["uc_first" => true, "lang" => "users"]) . "</span>",
          "ID" => "nada"
        ),
      );
    }

    foreach ($widgets as &$widget) {
      $widget = bof()->bofClient->__parse_widget(
        !empty($widget["object"]["name"]) ? $widget["object"]["name"] : null,
        !empty($widget["object"]["name"]) ? bof()->object->__get($widget["object"]["name"]) : null,
        $widget
      );
      $widget["display"]["classes"] .= " search_result_widget";
    }

    $widgets = array_reverse($widgets);
    return array(
      "widgets" => $widgets,
      "history" => $parse_query["history"]
    );
  }
  protected function exe_old( $args ){

    $query = null;
    $object_type = null;
    $page = null;
    extract( $args );

    $objects = bof()->bofClient->_get_objects();
    $widgets = array();
    $items = 0;

    foreach( $objects as $object_name => $object ){

      $the_object = bof()->object->__get( $object_name );

      if ( empty( $object["search"] ) ) continue;

      $object_results = $the_object->search( array(
        "query" => $query,
        "page" => $page
      ) );

      if ( $object_results ){

        $this->sort_old( $object_results, $query );

        foreach( $object_results as $_s => $i ){
          $i["sub_data"] = $the_object->bof()["label"];
          $items++;
        }

      }

      if ( !$object_results )
      continue;

      $widgets[ $object_name ] = array(
        "ID" => $object_name,
        "display" => array(
          "type" => "slider",
          "title" => $the_object->bof()["label"],
          "link" => false,
          "pagination" => false,
          "slider_size" => "medium",
          "slider_rows" => 1,
          "slider_mason" => false
        ),
        "object" => array(
          "name" => $object_name,
        ),
        "items" => $object_results ? array_values( $object_results ) : $object_results
      );

    }

    if ( !$items ){
      $widgets["best"] = array(
        "display" => array(
          "type" => "html",
          "title" => "Found nothing!",
          "html" => "<span style='opacity:0.5'>Try browsing or use other keywords</span>"
        ),
      );
    }

    foreach( $widgets as &$widget ){
      $widget = bof()->bofClient->__parse_widget(
        !empty( $widget["object"]["name"] ) ? $widget["object"]["name"] : null,
        !empty( $widget["object"]["name"] ) ? bof()->object->__get( $widget["object"]["name"] ) : null,
        $widget
      );
      $widget["display"]["classes"] .= " search_result_widget";
    }

    $widgets = array_reverse( $widgets );
    return array(
      "widgets" => $widgets,
      "history" => null
    );

  }
  protected function sort_old( &$result, $query ){

    return;
    $sorted_result = [];
    if ( $result ){
      foreach( $result as $item ){

        similar_text( $query, $item["title"], $sim );
        while( isset( $sorted_result[ $sim ] ) ){
          $sim += 0.01;
        }
        $sorted_result[ $sim ] = $item;

      }
    }
    $result = $sorted_result;
    krsort( $result );

  }

  public function __generate_dummy_data()
  {

    bof()->db->disable_cache();

    require_once(bof_root . "/app/core/third/fakerphp_faker/vendor/autoload.php");
    $faker = Faker\Factory::create("en_US");

    $i = 0;
    $i = 0;
    while ($i < 10000) {

      $artist_name = $faker->unique()->name();

      $artist_id = bof()->object->m_artist->create(
        array(
          "code" => bof()->general->make_code($artist_name),
        ),
        array(
          "code" => bof()->general->make_code($artist_name),
          "name" => $artist_name,
          "hash" => md5($artist_name . time() . uniqid() . rand(1, 1111)),
          "seo_url"  => uniqid() . uniqid(),
        ),
        array(),
        false,
        false
      );

      for ($ii = 1; $ii < rand(1, 20); $ii++) {
        $album_title = $faker->name() . (rand(1, 2) == 1 ? " " . $faker->name() : "") . (rand(1, 2) == 1 ? " " . $faker->name() : "") . (rand(1, 2) == 1 ? " " . $faker->name() : "");
        $album_id = bof()->object->m_album->create(
          array(
            "code" => bof()->general->make_code([$artist_name, $album_title]),
          ),
          array(
            "title" => $album_title,
            "hash" => md5($album_title . time() . uniqid() . rand(1, 1111)),
            "code" => bof()->general->make_code([$artist_name, $album_title]),
            "seo_url"  => uniqid() . uniqid(),
            "type" => "studio",
            "artist_id" => $artist_id,
            "time_release" => bof()->general->mysql_timestamp(),
            "description" => null,
            "price" => null,
          ),
          array(),
          false,
          false
        );

        for ($iii = 1; $iii < rand(3, 20); $iii++) {

          $track_title = $faker->name() . (rand(1, 2) == 1 ? " " . $faker->name() : "") . (rand(1, 2) == 1 ? " " . $faker->name() : "") . (rand(1, 2) == 1 ? " " . $faker->name() : "");

          bof()->object->m_track->create(
            array(
              "code" => bof()->general->make_code([$artist_name, $album_title, $track_title]),
            ),
            array(
              "title" => $track_title,
              "hash" => md5($artist_name . $album_title . $track_title . uniqid() . rand(1, 11111)),
              "code" => bof()->general->make_code([$artist_name, $album_title, $track_title]),
              "seo_url"  => uniqid() . uniqid() . uniqid() . rand(1, 1111111) . uniqid(),
              "artist_id" => $artist_id,
              "time_release" => bof()->general->mysql_timestamp(),
              "description" => null,
              "lyrics" => null,
              "price" => null,
              "price_setting" => null,
              "ft_artist_ids" => false,
              "album_id" => $album_id,
              "album_artist_id" => $artist_id,
              "album_index" => $iii,
              "album_cd" => null,
              "album_price" => null,
              "duration" => rand(60, 300)
            ),
            [],
            false,
            false
          );
        }
      }


      $i++;
    }

    die;
  }
}

?>