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
        'a', 'above', 'after', 'again', 'all', 'am', 'an', 'and', 'any', 'are', 'aren\'t', 'as', 'at', 'be',
        'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by', 'can\'t', 'cannot', 'could', 'couldn\'t',
        'did', 'didn\'t', 'do', 'does', 'doesn\'t', 'doing', 'don\'t', 'down', 'during', 'each', 'few', 'for', 'from',
        'had', 'hadn\'t', 'has', 'hasn\'t', 'have', 'haven\'t', 'having', 'he', 'he\'d', 'he\'ll', 'he\'s', 'her', 'here',
        'here\'s', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'how\'s', 'i', 'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'if', 'in',
        'into', 'is', 'isn\'t', 'it', 'it\'s', 'its', 'itself', 'let\'s', 'me', 'more', 'most', 'mustn\'t', 'my', 'myself', 'no',
        'nor', 'not', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'ours', 'ourselves', 'out', 'over',
        'own', 'same', 'shan\'t', 'she', 'she\'d', 'she\'ll', 'she\'s', 'should', 'shouldn\'t', 'so', 'some', 'such', 'than',
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
    $string = mb_strtolower($string, "utf-8");
    $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);

    $tokens = preg_split('/\s+/', $string);

    $Ctokens = array_filter($tokens, function ($token) {
      return $token && !in_array($token, $this->stopWords, true);
    });

    $tokens = !empty($Ctokens) ? $Ctokens : $tokens;

    require_once(bof_root . "/app/core/third/teamtnt_tntsearch/vendor/autoload.php");

    $Stokens = array_map(function ($token) {
      return TeamTNT\TNTSearch\Stemmer\PorterStemmer::stem($token);
    }, $tokens);

    return !empty($Stokens) ? $Stokens : $tokens;
  }

  public function generate_terms($PID, $GID)
  {

    $this->PID = $PID;
    $this->GID = $GID;
    bof()->db->disable_cache();

    foreach (bof()->bofAdmin->_get_objects() as $objectName => $objectArgs) {
      if ($objectArgs['search'] ? bof()->object->__get($objectName)->method_exists("clean_search_terms") : false) {
        $this->generate_terms_for_object($objectName);
      }
    }
  }
  public function generate_terms_for_object($object_name)
  {

    $object = bof()->object->__get($object_name);

    $pointer = 0;
    $maxPerObject = 100000;

    while ($pointer < $maxPerObject) {

      $item = $object->select(
        array(
          ["ID", "NOT IN", "SELECT object_id from _d_search_indexed WHERE object_type = '{$object_name}'", true]
        ),
        array(
          "limit" => 1,
          "single" => true,
          "clean" => false,
          "columns" => "ID",
          "cache_load_rt" => false
        )
      );

      if (!$item)
        return false;

      $c = $this->generate_terms_for_object_item($object_name, $object, $item);

      $this->_cli("{$object_name}: {$item["ID"]} -> {$c} terms");
    }
  }
  public function generate_terms_for_object_item($object_name, $object, $item)
  {

    bof()->db->_delete(array(
      "table" => "_d_search_postings",
      "where" => array(
        ["object_type", "=", $object_name],
        ["object_id", "=", $item["ID"]],
      )
    ));

    $item_search_terms = $object->select(
      array(
        "ID" => $item["ID"]
      ),
      array(
        "search_terms" => true
      )
    );

    $c = 0;
    if ($item_search_terms) {

      foreach ($item_search_terms as $item_search_term => $item_search_term_score) {

        $item_search_term_tokens = $this->__tokenize($item_search_term);

        foreach ($item_search_term_tokens as $item_search_term_token) {

          $item_search_term_token_score = count($item_search_term_tokens) == 1 ? $item_search_term_score : ($item_search_term_score / count($item_search_term_tokens) * 1.4);
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

          bof()->db->_insert(array(
            "table" => "_d_search_postings",
            "set" => array(
              ["term_id", $item_search_term_token_id],
              ["object_type", $object_name],
              ["object_id", $item["ID"]],
              ["score", $item_search_term_token_score]
            )
          ));

          $c++;
        }
      }
    }

    bof()->db->_insert(array(
      "table" => "_d_search_indexed",
      "set" => array(
        ["object_id", $item["ID"]],
        ["object_type", $object_name]
      )
    ));

    return $c;
  }

  public function __parse_query($query)
  {

    $query_tokens = $this->__tokenize(htmlspecialchars_decode(strval($query), ENT_QUOTES));
    if (!$query_tokens) return;

    $term_groups = [];
    $terms = [];

    foreach ($query_tokens as $i => $query_token) {

      $maxLev = $i + 1 == count($query_tokens) ? 6 : 2;

      $query_term_group_raw = bof()->db->_select(array(
        "table" => "_d_search_terms",
        "where" => array(
          ["term", "LIKE%", $query_token]
        ),
        "limit" => 20,
        "single" => false
      ));

      if (!$query_term_group_raw) continue;

      foreach ($query_term_group_raw as $query_term_raw) {

        $query_term_raw_lev = levenshtein($query_token, $query_term_raw["term"]);
        if ($query_term_raw_lev > $maxLev) continue;

        $query_term_raw_score = 1 - ($query_term_raw_lev ? ($query_term_raw_lev / 6) : 0);

        $terms[$query_term_raw["ID"]] = $query_term_raw_score;
        $term_groups[$query_token][$query_term_raw["ID"]] = $query_term_raw_score;
      }
    }

    return array(
      "tokens" => $query_tokens,
      "all" => $terms,
      "grouped" => $term_groups
    );
  }
  public function __perform_search($parsed_query, $object_type = null)
  {

    /*$queryString = "SELECT object_type,object_id,COUNT(*) as c, SUM(score) as s, COUNT(*) * SUM(score) as cs 
    FROM `_d_search_postings` 
    WHERE " .
      ($object_type ? "object_type = '{$object_type}' AND " : "")
      . "`term_id` IN (" . implode(",", array_keys($parsed_query["all"])) . ")
    GROUP BY " . ($object_type ? "" : "object_type, ") . "object_id  
    ORDER BY `cs` desc";*/

    $where = array(
      ["term_id", "IN", implode(",", array_keys($parsed_query["all"])), true]
    );
    if ($object_type)
      $where[] = ["object_type", "=", $object_type];

    $get = bof()->db->_select(array(
      "table" => "_d_search_postings",
      "columns" => "object_type,object_id,COUNT(*) as c, SUM(score) as s",
      "where" => $where,
      "group" => "GROUP BY " . ($object_type ? "object_id" : "object_type, object_id"),
      "order_by" => count($parsed_query["tokens"]) == 1 ? "s" : "c DESC, s",
      "single" => false,
      "limit" => 100
    ));

    if (!$get)
      return;

    if ($object_type)
      $get = array_map(function ($item) {
        return $item["object_id"];
      }, $get);

    return $get;
  }
  public function exe($args)
  {

    $query = null;
    $object_type = null;
    $page = null;
    extract($args);

    $parse_query = $this->__parse_query($query);



    /*
    $terms = $parse_query["all"];
    $term_groups = $parse_query["grouped"];

    $c = 1;
    foreach ($term_groups as $term_group) {
      $_gs = implode(",", array_keys($term_group));
      $_gss[] = "( SELECT term_id,object_type,object_id,score,COUNT(*),SUM(score) FROM `_d_search_postings` WHERE `term_id` IN ({$_gs}) GROUP BY object ) as tg{$c}" . PHP_EOL;
      $c++;
    }
    
    $map = [];
    foreach ($query_tokens_parsed as $query_token_term => $query_token_terms_parsed) {
      $query_tokens_term_ids = [];
      $query_tokens_terms_map = [];
      foreach ($query_token_terms_parsed as $term_id => $term_score) {
        $query_tokens_term_ids[] = $term_id;
        $query_tokens_terms_map[$term_id] = $term_score;
      }

      $term_postings = bof()->db->_select(
        array(
          "table" => "_d_search_postings",
          "where" => array(
            ["term_id", "IN", implode(",", $query_tokens_term_ids), true]
          ),
          "order_by" => "score",
          "order" => "DESC",
          "limit" => 1000,
          "single" => false
        )
      );

      if ($term_postings) {
        foreach ($term_postings as $term_posting) {
          $__s = $term_posting["score"] * $query_tokens_terms_map[$term_posting["term_id"]];
          $map[$term_posting["object_type"] . $term_posting["object_id"]][$term_posting["term_id"]] = [$term_posting["score"], $query_tokens_terms_map[$term_posting["term_id"]]];
        }
      }
    }

    echo json_encode($map);
    die;
    */

    if ($parse_query["all"]) {
      $objects = bof()->bofClient->_get_objects();
      $widgets = array();
      $items = 0;

      foreach ($objects as $object_name => $object) {

        $the_object = bof()->object->__get($object_name);

        if (empty($object["search"])) continue;

        $perform_search = $this->__perform_search($parse_query, $object_name);

        if (!$perform_search) continue;

        $object_results = $the_object->select(
          array(
            "ID_in" => $perform_search
          ),
          array(
            "single" => false,
            "limit" => false,
            "as_widget" => true,
          )
        );

        if ($object_results) {

          $this->sort($object_results, $query);

          foreach ($object_results as $_s => $i) {
            $i["sub_data"] = bof()->object->language->turn($object_name, [], ["uc_first" => true, "lang" => "users"]);
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
            "link" => false,
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
    }


    if (empty($items)) {
      $widgets["best"] = array(
        "display" => array(
          "type" => "html",
          "title" => bof()->object->language->turn("nothing_found", [], ["uc_first" => true, "lang" => "users"]),
          "html" => "<span style='opacity:0.5'>" . bof()->object->language->turn("nothing_found_s_tip", [], ["uc_first" => true, "lang" => "users"]) . "</span>"
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
    return $widgets;
  }
  protected function sort(&$result, $query)
  {

    return;
    $sorted_result = [];
    if ($result) {
      foreach ($result as $item) {

        similar_text($query, $item["title"], $sim);
        while (isset($sorted_result[$sim])) {
          $sim += 0.01;
        }
        $sorted_result[$sim] = $item;
      }
    }
    $result = $sorted_result;
    krsort($result);
  }
  public function __generate_dummy_data()
  {
    /*bof()->db->disable_cache();

    require_once(bof_root . "/app/core/third/fakerphp_faker/vendor/autoload.php");
    $faker = Faker\Factory::create("en_US");

    $i = 0;
    $i = 0;
    while ($i < 1000) {

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
    */
  }
}
