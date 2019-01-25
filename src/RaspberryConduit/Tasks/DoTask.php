<?php 
/*
 * commands are not treated in order they are received. 
 */

namespace RaspberryConduit\Tasks; 

use pocketmine\scheduler\Task; 
use pocketmine\math\Vector3; 
use pocketmine\block\Block;
use pocketmine\utils\TextFormat;

class DoTask extends Task { 

    protected $re_nbr =  '/[-+]?\d+/';

    public function __construct($plugin, $conduit) { 
        $this->plugin = $plugin;
        $this->conduit = $conduit;
    }

    private function makeBox(array $pos){
        if ($pos[0] < $pos[3]) {
            $minx = $pos[0];
            $maxx = $pos[3];
        } else {
            $minx = $pos[3];
            $maxx = $pos[0];
        }
       
        if ($pos[1] < $pos[4]) {
            $miny = $pos[1];
            $maxy = $pos[4];
        } else {
            $miny = $pos[4];
            $maxy = $pos[1];
        }
       
        if ($pos[2] < $pos[5]) {
            $minz = $pos[2];
            $maxz = $pos[5];
        } else {
            $minz = $pos[5];
            $maxz = $pos[2];
        }
        return array(
            'minx'=>$minx,
            'miny'=>$miny,
            'minz'=>$minz,
            'maxx'=>$maxx,
            'maxy'=>$maxy,
            'maxz'=>$maxz);
    }

    public function onRun(int $tick) { 
        $players = $this->plugin->getServer()->getOnlinePlayers();
        # get the first player 
        if (count($players)) {
            $player = array_shift($players);
        } else {
            $player = NULL;
        }

        foreach($this->conduit as $job => $data) {
            $cmd = $data['command'];
            if ($this->conduit[$job]['result']){
                #echo "Already processed\n";
            } else if ($player) {
                if ($cmd == "player.getPos()") {
                    $pos = $player->getPosition();
                    $this->conduit[$job]['result'] = $pos->x . ',' . $pos->y . ',' . $pos->z;

                } else if ($cmd == "player.getDirection()") {
                    $pos = $player->getDirectionVector();
                    $this->conduit[$job]['result'] = $pos->x . ',' . $pos->y . ',' . $pos->z;

                } else if (strpos($cmd,"player.setPos(") !== False) {
                    preg_match_all($this->re_nbr, $cmd, $s);
                    $pos = $s[0];
                    $vec = new Vector3((float)$pos[0], (float)$pos[1], (float)$pos[2]);
                    $player->teleport($vec);
                    $this->conduit[$job]['result'] = "NA";
                } else if (strpos($cmd,"chat.post(") !== False) {
                    preg_match_all('/chat.post\((.*)\)/', $cmd, $scan);
                    if (count($scan)) {
                        $message = $scan[1][0];
                        $player->sendMessage($message);
                    }
                    $this->conduit[$job]['result'] = "NA";

                } else if (strpos($cmd,"world.getBlock(") !== False) {
                    preg_match_all($this->re_nbr, $cmd, $s);
                    $pos = $s[0];
                    $vec = new Vector3($pos[0], $pos[1], $pos[2]);
                    $level = $this->plugin->getServer()->getDefaultLevel();
                    $block = $level->getBlock($vec)->getId();
                    $this->conduit[$job]['result'] = "$block";

                } else if (strpos($cmd,"world.getBlocks(") !== False) {
                    $level = $this->plugin->getServer()->getDefaultLevel();
                    preg_match_all($this->re_nbr, $cmd, $s);
                    $box = $this->makebox($s[0]);
                    $result = array();
                    $v = new Vector3();

                    for ($y = $box['miny']; $y < $box['maxy']; $y++) {
                        for ($x = $box['minx']; $x < $box['maxx']; $x++) {
                            for ($z = $box['minz']; $z < $box['maxz']; $z++) {
                                $v->setComponents($x,$y,$z);
                                $block = $level->getBlock($v)->getId();
                                $result[] = $block;
                            }
                        }
                    }

                    $response = implode(",",$result);
                    $this->conduit[$job]['result'] = $response;
                } else if (strpos($cmd,"world.setBlocks(") !== False) {
                    $level = $this->plugin->getServer()->getDefaultLevel();
                    preg_match_all($this->re_nbr, $cmd, $s);
                    $data = $s[0];
                    if (!array_key_exists(7, $data)) {
                        $data[7] = 0;
                    }
                    $newblock = new Block($data[6], $data[7]);
                    $box = $this->makebox($data);
                    $v = new Vector3();

                    for ($y = $box['miny']; $y <= $box['maxy']; $y++) {
                        for ($x = $box['minx']; $x <= $box['maxx']; $x++) {
                            for ($z = $box['minz']; $z <= $box['maxz']; $z++) {
                                $v->setComponents($x,$y,$z);
                                $block = $level->setBlock($v, $newblock);
                            }
                        }
                    }
                    $this->conduit[$job]['result'] = "NA";

                } else if (strpos($cmd,"world.setBlock(") !== False) {
                    preg_match_all($this->re_nbr, $cmd, $s);
                    $pos = $s[0];
                    if (!array_key_exists(4, $pos)) {
                        $pos[4] = 0;
                    }
                    $vec = new Vector3($pos[0], $pos[1], $pos[2]);
                    $level = $this->plugin->getServer()->getDefaultLevel();
                    $newblock = new Block($pos[3],$pos[4]);
                    $block = $level->setBlock($vec, $newblock);
                    $this->conduit[$job]['result'] = "NA";
                } else { #unknown command 
                    $this->conduit[$job]['result'] = "Fail";
                    }
            } else {
                #echo "Received command but No player found";
                $this->conduit[$job]['result'] = "Fail";
            } # not processed 
        }   
    }
 }
