<?php

if ( !defined( "bof_root" ) ) die;

class ai extends bof_type_class {

  public $nester = true;
  protected $cores = array(
    "openai" => array(
      "services" => [ "image", "text" ],
      "name" => "OpenAI",
      "site" => "openai.com"
    ),
    "prodia" => array(
      "services" => [ "image" ],
      "name" => "Prodia",
      "site" => "prodia.com"
    ),
    "deepai" => array(
      "services" => [ "image" ],
      "name" => "DeepAI",
      "site" => "deepai.org"
    ),
    "pht1" => array(
      "dir" => "playht",
      "services" => [ "speech" ],
      "cloning" => false,
      "name" => "PlayHT",
      "site" => "play.ht"
    ),
    "pht2" => array(
      "dir" => "playht",
      "services" => [ "speech" ],
      "cloning" => true,
      "name" => "PlayHT - Ultra Realistic",
      "site" => "play.ht"
    ),
    "ttp" => array(
      "dir" => "twotee",
      "services" => [ "speech" ],
      "cloning" => true,
      "name" => "TwoTee",
      "site" => "twotee.pro"
    ),
    "ttpx" => array(
      "dir" => "twotee",
      "services" => [ "speech" ],
      "cloning" => true,
      "name" => "TwoTee - X",
      "site" => "twotee.pro"
    ),
    "elio" => array(
      "dir" => "elevenlabs",
      "services" => [ "speech" ],
      "cloning" => true,
      "name" => "ElevenLabs",
      "site" => "elevenlabs.io"
    ),
  );
  protected $cache = array(
    "required_service" => false,
    "required_services" => array(
      "speech" => null,
      "image" => null,
      "text" => null
    ),
    "required_cores" => array(
      "openai" => null,
      "prodia" => null,
      "deepai" => null,
      "pht1" => null,
      "pht2" => null,
      "ttp" => null,
      "ttpx" => null,
      "elio" => null
    ),
    "fee_args" => array(),
  );

  public function __get_core( $coreName ){

    if ( empty( $this->cache["required_cores"][ $coreName ] ) ){
      $dirName = !empty( $this->cores[ $coreName ]["dir"] ) ? $this->cores[ $coreName ]["dir"] : $coreName;
      bof()->object->core_files->add_key( "class", $coreName, ai_plugin_root . "/classes/cores/{$dirName}/class_{$coreName}.php" );
      $this->cache["required_cores"][ $coreName ] = true;
      if ( bof()->__get( $coreName )->method_exists("setup") )
      bof()->__get( $coreName )->setup();
    }

    return bof()->__get( $coreName );

  }
  public function __get( $service_name ){

    // direct core access
    if ( in_array( $service_name, array_keys( $this->cache["required_cores"] ), true ) )
    return $this->__get_core( $service_name );

    // check service
    if ( !in_array( $service_name, array_keys( $this->cache["required_services"] ), true ) )
    throw new Exception( "invalid ai.service: {$service_name}" );

    // require service ( once )
    if ( empty( $this->cache["required_services"][ $service_name ] ) ){
      if ( empty( $this->cache["required_service"] ) ){
        bof()->object->core_files->add_key( "class", "ai_service", ai_plugin_root . "/classes/class_service.php" );
        $this->cache["required_service"] = true;
        bof()->ai_service->setup();
      }
      bof()->object->core_files->add_key( "class", "ai_service_{$service_name}", ai_plugin_root . "/classes/services/class_{$service_name}.php" );
      $this->cache["required_services"][ $service_name ] = true;
    }

    // call service
    $service_class = bof()->__get( "ai_service_{$service_name}" );
    $service_fl = substr( $service_name, 0, 1 );

    // get core
    if ( !empty( $this->cache["settings"]["{$service_name}.core"] ) )
    $service_core_name = $this->cache["settings"]["{$service_name}.core"];
    elseif ( !empty( $this->cache["setting_var_name"] ) ){
      $service_core_name = bof()->object->db_setting->get( "{$this->cache["setting_var_name"]}_{$service_fl}_core" );
      if ( $service_core_name ) $this->set_setting( "{$service_name}.core", $service_core_name );
    }

    if ( empty( $service_core_name ) )
    return $service_class;

    // require core ( once )
    $service_core_class = bof()->ai->__get_core( $service_core_name );

    // check core requirements
    $service_core_class->check_settings( $service_name );

    $service_class->set_core( $service_core_name, $service_core_class );

    return $service_class;

  }
  public function __reset(){

    $this->cache["setting_var_name"] = null;
    $this->cache["settings"] = array(
      "text.core" => null,
      "text.openai_model" => null,
      "text.openai_temperature" => null,
      "text.openai_max_tokens" => null,
      "image.core" => null,
      "image.openai_model" => null,
      "image.openai_quality" => null,
      "image.prodia_base_model" => null,
      "image.prodia_sd_model" => null,
      "image.prodia_sd_sampler" => null,
      "image.prodia_sdxl_model" => null,
      "image.prodia_sdxl_sampler" => null,
      "image.prodia_negative_prompt" => null,
      "image.prodia_steps" => null,
      "image.prodia_cfg" => null,
      "image.prodia_seed" => null,
      "image.deepai_style" => null,
      "image.deepai_negative_prompt" => null,
      "image.deepai_quality" => null,
      "speech.core" => null,
      "speech.elevenlabs.model" => null,
    );
    $this->cache["keys"] = array(
      "openai_key" => null,
      "openai_org" => null,
      "playht_uid" => null,
      "playht_key" => null,
      "twotee_uid" => null,
      "twotee_key" => null,
      "prodia_key" => null,
      "deepai_key" => null,
      "elevenlabs_key" => null
    );

    return $this;

  }

  public function set_setting_db_var( $varName ){
    $this->cache[ "setting_var_name" ] = $varName;
    return $this;
  }
  public function get_setting_db_var(){
    return $this->cache["setting_var_name"];
  }

  public function set_settings( $array ){
    $this->cache[ "settings" ] = $array;
    return $this;
  }
  public function set_setting_from_db( $service, $core, $var, $defVal, $required ){

    if ( empty( $this->cache["setting_var_name"] ) ){
      if ( $required )
      throw new Exception("No {$var} set for {$core} in database");
      return;
    }

    $service_fl = substr( $service, 0, 1 );

    $check_db = bof()->object->db_setting->get( "{$this->cache["setting_var_name"]}_{$core}_{$service_fl}_{$var}" );
    if ( !$check_db ){
      if ( $required )
      throw new Exception("No {$this->cache["setting_var_name"]}_{$core}_{$service_fl}_{$var} set for {$core} in database");
      if ( $defVal ) $check_db = $defVal;
    }

    $this->set_setting( "{$service}.{$core}_{$var}", $check_db );

  }
  public function set_setting( $k, $val ){
    $this->cache[ "settings" ][ $k ] = $val;
    return $this;
  }
  public function get_settings(){
    return $this->cache["settings"];
  }
  public function get_setting( $k ){
    return $this->cache["settings"][$k];
  }

  public function set_keys( $array ){
    $this->cache[ "keys" ] = $array;
    return $this;
  }
  public function set_key_from_db( $core, $var, $required ){

    if ( empty( $this->cache["setting_var_name"] ) ){
      if ( $required )
      throw new Exception("No {$var} set for {$core} in database");
      return;
    }

    $check_db = bof()->object->db_setting->get( "{$this->cache["setting_var_name"]}_{$core}_{$var}" );
    if ( !$check_db ){
      if ( $required )
      throw new Exception("No {$var} set for {$core} in database");
      return;
    }

    $this->set_key( "{$core}_{$var}", $check_db );

  }
  public function set_key( $k, $val ){
    $this->cache[ "keys" ][ $k ] = $val;
    return $this;
  }
  public function get_keys(){
    return $this->cache["keys"];
  }
  public function get_key( $k ){
    return $this->cache["keys"][$k];
  }

  public function get_cores( $service_name=null, $for=null ){

    $cores = [];
    foreach( $this->cores as $coreName => $coreData ){
      if ( !$service_name ? true : in_array( $service_name, $coreData["services"], true ) )
      $cores[ $coreName ] = $coreData;
    }

    $output = [];
    if ( $for == "options" ){
      foreach( $cores as $coreName => $coreData )
      $output[] = [ $coreName, $coreData["name"] ];
      return $output;
    }
    if ( $for == "validator" ){
      foreach( $cores as $coreName => $coreData )
      $output[] = $coreName;
      return $output;
    }

    return $cores;

  }

  public function get_admin_aiapi_settings( $pName ){

    $deepai_styles = json_decode( '{"text2img":"AI Image Generator","fantasy-world-generator":"an image in fantasy style.","cyberpunk-generator":"an image in cyberpunk style.","cute-creature-generator":"an image of a cute animal.","fantasy-portrait-generator":"a portrait in fantasy style.","renaissance-painting-generator":"a renaissance painting.","anime-portrait-generator":"a hyper-realistic portrait in anime style.","comics-portrait-generator":"a portrait in comics style.","old-style-generator":"an image in 18th century drawing style.","cyberpunk-portrait-generator":"a portrait in cyberpunk style.","surreal-graphics-generator":"detailed surreal graphics.","contemporary-architecture-generator":"contemporary architecture concept image.","3d-objects-generator":"a highly detailed 3d image of any object.","3d-character-generator":"a 3D character design.","impressionism-painting-generator":"an impressionism painting.","logo-generator":"a logo concept image.","abstract-painting-generator":"an abstract painting.","watercolor-painting-generator":"a watercolor painting.","surreal-portrait-generator":"surreal portrait image.","future-architecture-generator":"future architecture design concept image.","fantasy-character-generator":"a character in fantasy style.","anime-world-generator":"anime style world image.","steampunk-generator":"an image in steampunk style.","hologram-3d-generator":"a 3D hologram in neon colors.","pop-art-generator":"pop art comic style image.","street-art-generator":"a street art style image.","origami-3d-generator":"a 3D origami style character.","watercolor-architecture-generator":"watercolor architecture concept image.","pixel-art-generator":"an image pixel art style.","neo-noir-generator":"a neo noir style.","photorealistic-portrait-generator":"a photorealistic image.","supernatural-character-generator":"a supernatural character.","cyber-beast-generator":"cyborg of an object or character.","mythic-creature-generator":"a mythical creature.","anime-superhero-generator":"an anime style superhero.","romantic-art-generator":"a romantic style portrait.","dystopian-landscape-generator":"a dystopian landscape style.","haunted-portrait-generator":"a haunted portrait.","manga-panel-genarator":"a manga panel.","zombie-apocalypse-generator":"a zombie apocalypse scene.","prophetic-vision-generator":"a dreamy vision style.","children-book-generator":"a childrens book illustration.","art-nouveau-generator":"an art nouveau poster.","alien-flora-generator":"an alien flora style.","dystopian-cyberpunk-generator":"a dystopian cyberpunk style.","gothic-architecture-generator":"a gothic architecture style.","pixel-world-generator":"a world in pixel art style.","fantasy-map-generator":"a fantasy map style.","tribal-art-generator":"a tribal art style.","baroque-art-generator":"baroque art style.","gothic-art-generator":"a gothic art style.","dreamscape-generator":"a surrealistic dreamscape.","witchcraft-symbol-generator":"a witchcraft symbol style.","elven-world-generator":"an elven world style.","comics-superhero-generator":"a superhero in comics style.","urban-fashion-generator":"urban street fashion.","abstract-expressionism-generator":"an abstract expressionism painting.","gladiator-arena-generator":"a character in gladiator arena.","post-apocalyptic-generator":"post-apocalyptic scene.","psychedelic-poster-generator":"a psychedelic style poster.","alien-civilization-generator":"an alien civilization theme.","political-satire-generator":"a political satire cartoon.","3d-cartoon-generator":"a stylized 3D cartoon character.","space-world-generator":"a space landscape or world style.","art-deco-generator":"an art deco space.","fairy-tale-art-generator":"a fairy tale theme.","gothic-literature-generator":"gothic literature art style.","tropical-paradise-generator":"a tropical paradise style.","grotesque-art-generator":"a grotesque style image.","mysticism-art-generator":"a mystic style art.","chibi-character-generator":"a chibi style character.","intergalactic-battle-generator":"an intergalactic battle theme.","surrealist-sculpture-generator":"a surrealist sculpture.","mars-life-generator":"Mars life style.","decoupage-art-generator":"a decoupage art style.","terrarium-world-generator":"a terrarium world style.","renaissance-fresco-generator":"a renaissance fresco.","old-masters-generator":"old masters art generator.","retro-game-generator":"a retro game style.","action-figure-generator":"an action figure design.","samurai-art-generator":"a samurai style.","colonial-portrait-generator":"a colonial portrait style.","marionette-design-generator":"a marionette design.","ocean-life-generator":"a oceanic creature.","steampunk-landscape-generator":"a steampunk landscape style.","mecha-suit-generator":"a mecha suit style.","ancient-egyptian-generator":"an ancient egyptian style.","greek-mythology-generator":"a greek mythology character.","underground-city-generator":"an underground city.","film-poster-generator":"a vintage film poster style.","bioluminescent-life-generator":"a bioluminescent life style.","ice-world-generator":"an ice world style.","atlantis-world-generator":"an atlantis world style.","unreal-cityscape-generator":"an unreal cityscape theme.","zentangle-design-generator":"a zentangle design.","treehouse-design-generator":"a treehouse design.","robot-battle-generator":"a giant robot battle scene.","playing-card-generator":"a vintage playing card style.","minimalistic-art-generator":"a minimalistic theme.","cubist-art-generator":"a cubist art style.","solar-system-generator":"a solar system style.","depths-sea-generator":"a deep sea theme.","urban-graffiti-generator":"a graffiti style.","wild-west-generator":"a wild west theme.","origami-paper-generator":"origami paper art.","carnival-scene-generator":"a carnival scene style.","spaceship-blueprint-generator":"a detailed blueprint inspired by a spaceship design.","concrete-jungle-generator":"a black and white cityscape.","ice-sculpture-generator":"an ice sculpture theme.","stained-glass-generator":"a stained glass window theme.","post-impressionist-painting-generator":"a post-impressionist painting style.","film-collage-generator":"a film photo collage.","zodiac-design-generator":"a zodiac design.","bauhaus-design-generator":"a Bauhaus design style.","opera-costume-generator":"an opera inspired costume design.","ancient-hieroglyph-generator":"an ancient hieroglyph style.","solar-flare-generator":"a solar flare theme.","mechanical-anatomy-generator":"a full body mechanical anatomy.","brutalist-architecture-generator":"a brutalist architecture design.","ancient-mayan-art-generator":"an ancient mayan art style.","victorian-art-generator":"victorian art style.","food-sculpture-generator":"a sculpture made of food.","kawaii-emoji-generator":"a kawaii style emoji.","clockwork-toy-generator":"a clockwork toy.","cave-painting-generator":"a cave painting style."}', true );

    $openai_text_models = bof()->ai->openai->get_text_models();
    $openai_text_models_options = bof()->ai->openai->get_text_models("options");

    try {
      $prodia_sd_models = bof()->ai->set_setting_db_var($pName)->prodia->check_settings('none')->models("sd");
      $prodia_sd_samplers = bof()->ai->set_setting_db_var($pName)->prodia->check_settings('none')->samplers("sd");
      $prodia_sdxl_models = bof()->ai->set_setting_db_var($pName)->prodia->check_settings('none')->models("sdxl");
      $prodia_sdxl_samplers = bof()->ai->set_setting_db_var($pName)->prodia->check_settings('none')->samplers("sdxl");
      $prodia_warning = "";
    } catch( Exception $err ){
      $prodia_sd_models = false;
      $prodia_sd_samplers = false;
      $prodia_sdxl_models = false;
      $prodia_sdxl_samplers = false;
      $prodia_warning = "List is not updated. Enter your API-key to enable RKHM to retrieve the list";
    }

    try {
      $elio_models = [];
      $elevenlabs_models = bof()->ai->set_setting_db_var($pName)->elio->check_settings('none')->getModels();
      $elio_warning = [];
      foreach( $elevenlabs_models as $_elio_model ){
        $elio_models[ $_elio_model["model_id"] ] = $_elio_model["model_id"];
        $elio_warning[] = "<b>{$_elio_model["model_id"]}</b>: {$_elio_model["description"]}<br>";
      }
      $elio_warning = implode( "<br><br>", $elio_warning );

    } catch( Exception $err ){
      $elio_models = [];
      $elio_warning = "List is not updated. Enter your API-key to enable RKHM to retrieve the list";
    }

    return array(
      "groups" => array(
        "services" => array(
          "title" => "Services",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_i_core" => array(
              "title" => "Image Service",
              "tip" => "Choose default service for image generation",
              "col_name" => "{$pName}_i_core",
              "input" => array(
                "name" => "{$pName}_i_core",
                "type" => "select_i",
                "options" => bof()->ai->get_cores( "image", "options" ),
                "value" => "openai"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => bof()->ai->get_cores( "image", "validator" )
                )
              )
            ),
            "{$pName}_t_core" => array(
              "title" => "Text Service",
              "tip" => "Choose default service for text generation",
              "col_name" => "{$pName}_t_core",
              "input" => array(
                "name" => "{$pName}_t_core",
                "type" => "select_i",
                "options" => bof()->ai->get_cores( "text", "options" ),
                "value" => "openai"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => bof()->ai->get_cores( "text", "validator" )
                )
              )
            ),
            "{$pName}_s_core" => array(
              "title" => "Speech Service",
              "tip" => "Choose default service for speech generation",
              "col_name" => "{$pName}_s_core",
              "input" => array(
                "name" => "{$pName}_s_core",
                "type" => "select_i",
                "options" => bof()->ai->get_cores( "speech", "options" ),
                "value" => "playht"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => bof()->ai->get_cores( "speech", "validator" )
                )
              )
            ),
          )
        ),
        "openai" => array(
          "title" => "OpenAI",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_openai_key" => array(
              "title" => "OpenAI API Key",
              "tip" => "Enter your OpenAI API key here. You can get it from <a href='https://platform.openai.com/account/api-keys' target='_blank'>this page</a>. <div class=\"btn btn-primary\" id=\"openai_key_test\">Test</div>",
              "col_name" => "{$pName}_openai_key",
              "input" => array(
                "name" => "{$pName}_openai_key",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_openai_org" => array(
              "title" => "OpenAI Organization",
              "tip" => "<b>Leave it empty</b> if you don't know your organization. For users who belong to multiple organizations, you can pass a string to specify which organization is used for an API request. Usage from these API requests will count against the specified organization's subscription quota. <a href='https://platform.openai.com/docs/api-reference/authentication' target='_blank'>Docs</a>",
              "col_name" => "{$pName}_openai_org",
              "input" => array(
                "name" => "{$pName}_openai_org",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_openai_core" => array(
              "title" => "OpenAI Chat Model",
              "tip" => "Which model should be used? Click <a href='https://openai.com/api/pricing/' target='_blank'>here</a> & <a href='https://platform.openai.com/docs/models' target='_blank'>here</a> for more info",
              "col_name" => "{$pName}_openai_core",
              "input" => array(
                "name" => "{$pName}_openai_core",
                "type" => "select_i",
                "options" => $openai_text_models_options,
                "value" => "gpt4"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => array_keys( $openai_text_models )
                )
              )
            ),
            "{$pName}_openai_i_model" => array(
              "play" => true,
              "title" => "OpenAI Image Model",
              "tip" => "Which image model should be used?",
              "col_name" => "{$pName}_openai_i_model",
              "input" => array(
                "name" => "{$pName}_openai_i_model",
                "type" => "select_i",
                "options" => array(
                  [ "dalle_2", "DALL.E 2" ],
                  [ "dalle_3", "DALL.E 3" ],
                ),
                "value" => "dalle_3"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "dalle_2", "dalle_3" ]
                )
              )
            ),
            "{$pName}_openai_i_quality" => array(
              "play" => true,
              "title" => "OpenAI Image Quality",
              "tip" => "HD is supported by Dall.E 3 only",
              "col_name" => "{$pName}_openai_i_quality",
              "input" => array(
                "name" => "{$pName}_openai_i_quality",
                "type" => "select_i",
                "options" => array(
                  [ "standard", "Standard" ],
                  [ "hd", "HD" ],
                ),
                "value" => "standard"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "standard", "hd" ]
                )
              )
            ),
          )
        ),
        "prodia" => array(
          "title" => "Prodia",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_prodia_key" => array(
              "title" => "Prodia API Key",
              "tip" => "Enter your Prodia API key here. You can get it from <a href='https://app.prodia.com/api' target='_blank'>this page</a>",
              "col_name" => "{$pName}_prodia_key",
              "input" => array(
                "name" => "{$pName}_prodia_key",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_base_model" => array(
              "play" => true,
              "title" => "Base model",
              "col_name" => "{$pName}_prodia_i_base_model",
              "tip" => "Select Stable Diffusion version. See prices <a href='https://docs.prodia.com/reference/pricing' type='_blank'>Here</a>",
              "input" => array(
                "name" => "{$pName}_prodia_i_base_model",
                "type" => "select_i",
                "options" => array(
                  [ "sd", "SD 1.5" ],
                  [ "sdxl", "SDXL" ]
                ),
                "value" => "sdxl"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "sd", "sdxl" ]
                )
              )
            ),
            "{$pName}_prodia_i_sd_model" => array(
              "play" => true,
              "title" => "SD 1.5 model",
              "col_name" => "{$pName}_prodia_i_sd_model",
              "tip" => $prodia_warning,
              "input" => array(
                "name" => "{$pName}_prodia_i_sd_model",
                "type" => "select",
                "options" => $prodia_sd_models ? bof()->general->bofify_options( $prodia_sd_models, "value" ) : []
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $prodia_sd_models,
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_sd_sampler" => array(
              "play" => true,
              "title" => "SD 1.5 sampler",
              "col_name" => "{$pName}_prodia_i_sd_sampler",
              "tip" => $prodia_warning,
              "input" => array(
                "name" => "{$pName}_prodia_i_sd_sampler",
                "type" => "select",
                "options" => $prodia_sd_samplers ? bof()->general->bofify_options( $prodia_sd_samplers, "value" ) : [],
                "value" => "DPM++ 2M Karras"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $prodia_sd_samplers,
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_sdxl_model" => array(
              "play" => true,
              "title" => "SDXL model",
              "col_name" => "{$pName}_prodia_i_sdxl_model",
              "tip" => $prodia_warning,
              "input" => array(
                "name" => "{$pName}_prodia_i_sdxl_model",
                "type" => "select",
                "options" => $prodia_sdxl_models ? bof()->general->bofify_options( $prodia_sdxl_models, "value" ) : []
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $prodia_sdxl_models,
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_sdxl_sampler" => array(
              "play" => true,
              "title" => "SDXL sampler",
              "col_name" => "{$pName}_prodia_i_sdxl_sampler",
              "tip" => $prodia_warning,
              "input" => array(
                "name" => "{$pName}_prodia_i_sdxl_sampler",
                "type" => "select",
                "options" => $prodia_sdxl_samplers ? bof()->general->bofify_options( $prodia_sdxl_samplers, "value" ) : [],
                "value" => "DPM++ 2M Karras"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $prodia_sdxl_samplers,
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_negative_prompt" => array(
              "play" => true,
              "title" => "Negative prompt",
              "tip" => "Do not modify before educating yourself on Stable Diffusion",
              "col_name" => "{$pName}_prodia_i_negative_prompt",
              "input" => array(
                "name" => "{$pName}_prodia_i_negative_prompt",
                "type" => "textarea",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_steps" => array(
              "play" => true,
              "title" => "Steps",
              "tip" => "Do not modify before educating yourself on Stable Diffusion",
              "col_name" => "{$pName}_prodia_i_steps",
              "input" => array(
                "name" => "{$pName}_prodia_i_steps",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1,
                  "max" => 50,
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_cfg" => array(
              "play" => true,
              "title" => "CFG",
              "tip" => "Do not modify before educating yourself on Stable Diffusion",
              "col_name" => "{$pName}_prodia_i_cfg",
              "input" => array(
                "name" => "{$pName}_prodia_i_cfg",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => 1,
                  "max" => 10,
                  "empty()"
                )
              )
            ),
            "{$pName}_prodia_i_seed" => array(
              "play" => true,
              "title" => "Seed",
              "tip" => "Do not modify before educating yourself on Stable Diffusion",
              "col_name" => "{$pName}_prodia_i_seed",
              "input" => array(
                "name" => "{$pName}_prodia_i_seed",
                "type" => "digit",
              ),
              "validator" => array(
                "int",
                array(
                  "min" => -1,
                  "max" => 999999999999,
                  "empty()"
                )
              )
            ),
          )
        ),
        "deepai" => array(
          "title" => "DeepAI",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_deepai_key" => array(
              "title" => "deepai API Key",
              "tip" => "Enter your DeepAI API key here. You can get it from <a href='https://deepai.org/dashboard/profile' target='_blank'>this page</a>",
              "col_name" => "{$pName}_deepai_key",
              "input" => array(
                "name" => "{$pName}_deepai_key",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_deepai_i_style" => array(
              "play" => true,
              "title" => "Style",
              "col_name" => "{$pName}_deepai_i_style",
              "input" => array(
                "name" => "{$pName}_deepai_i_style",
                "type" => "select",
                "options" => $deepai_styles ? bof()->general->bofify_options( $deepai_styles ) : []
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => array_keys($deepai_styles),
                  "empty()"
                )
              )
            ),
            "{$pName}_deepai_i_negative_prompt" => array(
              "play" => true,
              "title" => "Negative prompt",
              "col_name" => "{$pName}_deepai_i_negative_prompt",
              "input" => array(
                "name" => "{$pName}_deepai_i_negative_prompt",
                "type" => "textarea",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_deepai_i_quality" => array(
              "play" => true,
              "title" => "DeepAI Version",
              "col_name" => "{$pName}_deepai_i_quality",
              "input" => array(
                "name" => "{$pName}_deepai_i_quality",
                "type" => "select_i",
                "options" => array(
                  [ "standard", "Standard" ],
                  [ "hd", "HD" ],
                ),
                "value" => "standard"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "standard", "hd" ]
                )
              )
            ),
          )
        ),
        "playht" => array(
          "title" => "PlayHT",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_playht_uid" => array(
							"title" => "PlayHT User ID",
							"tip" => "Enter your PlayHT User ID here. You can get it from <a href='https://play.ht/studio/api-access' target='_blank'>this page</a>",
              "col_name" => "{$pName}_playht_uid",
							"input" => array(
                "name" => "{$pName}_playht_uid",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
            "{$pName}_playht_key" => array(
							"title" => "PlayHT Secret Key",
							"tip" => "Enter your PlayHT User ID here. You can get it from <a href='https://play.ht/studio/api-access' target='_blank'>this page</a>",
              "col_name" => "{$pName}_playht_key",
							"input" => array(
                "name" => "{$pName}_playht_key",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
          )
        ),
        "twotee" => array(
          "title" => "TwoTee",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_twotee_uid" => array(
							"title" => "TwoTee User ID",
              "col_name" => "{$pName}_twotee_uid",
							"input" => array(
                "name" => "{$pName}_twotee_uid",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
            "{$pName}_twotee_key" => array(
							"title" => "TwoTee Secret Key",
              "col_name" => "{$pName}_twotee_key",
							"input" => array(
                "name" => "{$pName}_twotee_key",
								"type" => "text",
							),
							"validator" => array(
								"string",
								array(
									"empty()"
								)
							)
						),
          )
        ),
        "elevenlabs" => array(
          "title" => "ElevenLabs",
          "icon" => "dns",
          "inputs" => array(
            "{$pName}_elevenlabs_key" => array(
              "title" => "ElevenLabs API Key",
              "col_name" => "{$pName}_elevenlabs_key",
              "input" => array(
                "name" => "{$pName}_elevenlabs_key",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()"
                )
              )
            ),
            "{$pName}_elevenlabs_s_model" => array(
              "play" => true,
              "title" => "Model",
              "col_name" => "{$pName}_elevenlabs_s_model",
              "tip" => $elio_warning,
              "input" => array(
                "name" => "{$pName}_elevenlabs_s_model",
                "type" => "select",
                "options" => $elio_models ? bof()->general->bofify_options( $elio_models, "key", "value" ) : [],
                "value" => "eleven_english_sts_v2"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => $elio_models,
                  "empty()"
                )
              )
            ),
          )
        ),
      )
    );

  }

  public function set_fee_args( $k, $args ){
    $this->cache["fee_args"][$k] = $args;
    return $this;
  }
  public function get_fee_args( $k ){
    $o = null;
    if ( isset( $this->cache["fee_args"][$k] ) ){
      $o = $this->cache["fee_args"][$k];
      unset( $this->cache["fee_args"][$k] );
    }
    return $o;
  }
  public function fee( $service, $core, $action, $fees ){

    return bof()->db->_insert(array(
      "table" => "_bof_log_ai_fees",
      "set" => array(
        [ "service", $service ],
        [ "ai", $core ],
        [ "action", $action ],
        [ "fee", $fees["total_price"] ]
      )
    ));

  }

}

?>
