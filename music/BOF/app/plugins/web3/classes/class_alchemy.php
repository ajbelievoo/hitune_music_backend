<?php

if (!defined("bof_root")) die;

class web3_alchemy extends bof_type_class
{

    protected $base = "https://{network}.g.alchemy.com/nft/v3/{apikey}/";
    protected $key  = null;
    protected $network_base_url = null;

    public function set_key()
    {

        $key = bof()->object->db_setting->get("w3_alchemy");
        if (!$key) return $this;
        $this->key = $key;
        return $this;
    }
    public function set_network_base_url($network_base_url)
    {
        $this->network_base_url = $network_base_url;
        return $this;
    }

    public function getNFTsForOwner($wallet, $args = [])
    {

        $token_types = false;
        $simplify = false;
        extract($args);

        $data = $this->_req("getNFTsForOwner", array(
            "params" => array(
                "owner" => "0x" . $wallet
            )
        ));

        $sorted_data = [];
        if ( !empty( $data["ownedNfts"] ) ){
            foreach( $data["ownedNfts"] as $nft ){

                if ( $token_types ? !in_array( $nft["tokenType"], $token_types, true ) : false )
                continue;

                if ( $simplify ){
                    $nft = array(
                        "CID" => $nft["tokenId"],
                        "name" => $nft["contract"]["name"],
                        "image" => !empty( $nft["image"]["originalUrl"] ) ? $nft["image"]["originalUrl"] : null,
                        "contract" => $nft["contract"]["address"],
                        "token_type" => intval( str_replace( "ERC", "", $nft["tokenType"] ) ),

                    );
                }

                $sorted_data[] = $nft;

            }
        }

        return $sorted_data;
    }

    protected function _req($endpoint, $args = [])
    {

        $params = null;
        extract($args);

        $base = str_replace(["{network}", "{apikey}"], [$this->network_base_url, $this->key], $this->base);

        $url = $base . $endpoint . ($params ? "?" . http_build_query($params) : "");
        $curl = bof()->curl->exe(array(
            "url" => $url,
            "json" => true,
            "cache_load" => true,
            "cache" => true
        ));

        return $curl["data"];
        
    }

}
