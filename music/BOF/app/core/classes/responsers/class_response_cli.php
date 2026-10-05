<?php

if ( !defined( "bof_root" ) ) die;

use TerminalProgress\Bar;

class response_cli {

  protected $progress_bar_client = null;
  protected $progress_bar_current_tick = 0;

  public function set( $args ){}
  public function display(){}
  public function echo( $text, $color=null, $bgColor=null, $style=null, $return=false ){

    require_once( bof_root . "/app/core/third/bvanhoekelen_terminal-style/vendor/autoload.php" );

    $output = terminal_style( $text , $color, $bgColor, $style );
    if ( $return ) return $output;
    echo $output . PHP_EOL;

  }
  public function echo_separate($p="="){
    if ( bof()->response->getType( "cli" ) )
    echo str_replace( "=", $p, "================================" ) . PHP_EOL;
  }
  public function progress_ini( $args=[] ){

    $total = null;
    $format = null;
    extract( $args );
    if ( !$total )
    return false;

    require_once( bof_root . "/app/core/third/MarcoMan_PHPTerminalProgressBar/vendor/autoload.php" );

    $this->progress_bar_client = new Bar( $total );
    $this->progress_bar_client->symbolComplete = "#";
    $this->progress_bar_client->symbolIncomplete = "-";
    $this->progress_bar_current_tick = 0;
    return true;

  }
  public function progress_update( $tick ){

    if ( !$this->progress_bar_client ) return false;
    $this->progress_bar_client->update( $tick );
    $this->progress_bar_current_tick = $tick;
    return true;

  }
  public function progress_tick(){

    if ( !$this->progress_bar_client ) return false;
    $this->progress_bar_current_tick++;
    $this->progress_bar_client->update( $this->progress_bar_current_tick );
    return true;

  }
  public function progress_echo( $text, $color=null, $bgColor=null, $style=null ){

    if ( !$this->progress_bar_client ) return false;
    $this->progress_bar_client->interupt( $this->echo( $text, $color, $bgColor, $style, true ) );
    return true;

  }

}

?>
