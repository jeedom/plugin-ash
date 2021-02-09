<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ash_ColorController {
	
	/*     * *************************Attributs****************************** */
	
	private static $_COLOR_STATE = array('LIGHT_COLOR');
	private static $_COLOR = array('LIGHT_SET_COLOR');
	
	/*     * ***********************Methode static*************************** */
	
	public static function discover($_device,$_eqLogic) {
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_COLOR)) {
				$return['capabilities']['Alexa.ColorController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.ColorController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'color'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['ColorController_setState'] = $cmd->getId();
			}
		}
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_COLOR_STATE)) {
				if(isset($return['capabilities']['Alexa.ColorController'])){
					$return['capabilities']['Alexa.ColorController']['properties']['retrievable'] = true;
				}
				$return['cookie']['ColorController_getState'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array('missingGenericType' => array(
				__('Couleur',__FILE__) => self::$_COLOR,
				__('Etat couleur',__FILE__) => self::$_COLOR_STATE,
			));
		}
		$return['capabilities']['AlexaInterface'] = array(
			"type" => "AlexaInterface",
			"interface" => "Alexa",
			"version" => "3",
		);
		return $return;
	}
	
	public static function exec($_device, $_directive) {
		switch ($_directive['header']['name']) {
			case 'SetColor':
			if (isset($_directive['endpoint']['cookie']['ColorController_setState'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['ColorController_setState']);
			}
			if (is_object($cmd)) {
				$value = self::hslToRgb($_directive['payload']['color']['hue'], $_directive['payload']['color']['saturation']*100, $_directive['payload']['color']['brightness']*100);
				$color = sprintf("#%02x%02x%02x", $value[0], $value[1], $value[2]);
				$cmd->execCmd(array('color' => $color));
			}
			break;
		}
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		if (isset($_directive['endpoint']['cookie']['ColorController_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['ColorController_getState']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				if ($cmd->getSubtype() == 'string') {
					list($r, $g, $b) = sscanf($value, "#%02x%02x%02x");
					$value = self::rgb_to_hsv($r, $g, $b);
					$return['Alexa.ColorController'] = array(
						'namespace' => 'Alexa.ColorController',
						'name' => 'color',
						'value' => array('hue' => $value[0],'saturation'=>$value[1]/100,'brightness'=>$value[2]/100),
						'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
						'uncertaintyInMilliseconds' => 0,
					);
				}
			}
		}
		return array('properties' => array_values($return));
	}
	
	public static function rgb_to_hsv($R, $G, $B) {
		$R = ($R / 255);
		$G = ($G / 255);
		$B = ($B / 255);
		$maxRGB = max($R, $G, $B);
		$minRGB = min($R, $G, $B);
		$chroma = $maxRGB - $minRGB;
		$computedV = 100 * $maxRGB;
		if ($chroma == 0) {
			return array(0, 0, $computedV);
		}
		$computedS = 100 * ($chroma / $maxRGB);
		if ($R == $minRGB) {
			$h = 3 - (($G - $B) / $chroma);
		} elseif ($B == $minRGB) {
			$h = 1 - (($R - $G) / $chroma);
		} else {
			$h = 5 - (($B - $R) / $chroma);
		}
		$computedH = 60 * $h;
		return array($computedH, $computedS, $computedV);
	}
	
	public static function rgbToHsl( $r, $g, $b ) {
		$oldR = $r;
		$oldG = $g;
		$oldB = $b;
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$h;
		$s;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;
		if( $d == 0 ){
			$h = $s = 0; // achromatic
		} else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );
			switch( $max ){
				case $r:
				$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
				if ($b > $g) {
					$h += 360;
				}
				break;
				case $g:
				$h = 60 * ( ( $b - $r ) / $d + 2 );
				break;
				case $b:
				$h = 60 * ( ( $r - $g ) / $d + 4 );
				break;
			}
		}
		return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
	}
	public static function hslToRgb($iH, $iS, $iV){
		if ($iH < 0) {
			$iH = 0;
		}
		if ($iH > 360) {
			$iH = 360;
		}
		if ($iS < 0) {
			$iS = 0;
		}
		if ($iS > 100) {
			$iS = 100;
		}
		if ($iV < 0) {
			$iV = 0;
		}
		if ($iV > 100) {
			$iV = 100;
		}
		$dS = $iS / 100.0;
		$dV = $iV / 100.0;
		$dC = $dV * $dS;
		$dH = $iH / 60.0;
		if ($dH === null) {
			$dH = 0;
		}
		$dT = $dH;
		while ($dT >= 2.0) {
			$dT -= 2.0;
		}
		$dX = $dC * (1 - abs($dT - 1));
		if ($dH >= 0.0 && $dH < 1.0) {
			$dR = $dC;
			$dG = $dX;
			$dB = 0.0;
		} else if ($dH >= 1.0 && $dH < 2.0) {
			$dR = $dX;
			$dG = $dC;
			$dB = 0.0;
		} else if ($dH >= 2.0 && $dH < 3.0) {
			$dR = 0.0;
			$dG = $dC;
			$dB = $dX;
		} else if ($dH >= 3.0 && $dH < 4.0) {
			$dR = 0.0;
			$dG = $dX;
			$dB = $dC;
		} else if ($dH >= 4.0 && $dH < 5.0) {
			$dR = $dX;
			$dG = 0.0;
			$dB = $dC;
		} else if ($dH >= 5.0 && $dH < 6.0) {
			$dR = $dC;
			$dG = 0.0;
			$dB = $dX;
		} else {
			$dR = 0.0;
			$dG = 0.0;
			$dB = 0.0;
		}
		$dM = $dV - $dC;
		$dR += $dM;
		$dG += $dM;
		$dB += $dM;
		$dR *= 255;
		$dG *= 255;
		$dB *= 255;
		return array(round($dR), round($dG), round($dB));
	}
	
	/*     * *********************Méthodes d'instance************************* */
	
	/*     * **********************Getteur Setteur*************************** */
	
}
