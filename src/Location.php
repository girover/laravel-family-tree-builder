<?php
/**
 * Example of node location in the tree: 
 * "aaa.bbb.ccc.ddd.eee"
 * 
 * As we can se, the location is a chain of connected segments
 * by character called separator, here is ".", and it may changes to one of these , _ # or @
 * in this locatin there are 5 persons. or 5 locations in one location.
 * 1- aaa                    Root
 * 2- aaa.bbb                Grandfathers father
 * 3- aaa.bbb.ccc            Grandfather
 * 4- aaa.bbb.ccc.ddd        Father
 * 5- aaa.bbb.ccc.ddd.eee    Child
 * so every person is conected with its parent by the separator to create new location.
 * 
 * the location without separator considers as the root location in a tree.
 * 
 * Segment is set of chars or digits and all segments are the same length.
 * 
 * @author  Majed Girover girover.mhf@gmail.com
 * @license MIT
 */

namespace Girover\Tree;

use Girover\Tree\Exceptions\TreeException;

class Location{

    /**
     * The separator of node location
     * a specific character like one of these  
     * .  _  #   @
     * 
     * @var string the separator of location
     */
    // protected static $separator =  '.';
    const SEPARATOR =  '.';

    /**
     * Segment unit
     * indicates that how location`s segment is have to be
     * should be one of these: 'chars' or 'digits'.
     * if setted to 'chars' so segments in location will be generated by set of characters like: 'aaa.ddd'
     * if setted to 'digits' so segments in location will be generated by set of digits like: '000.333'
     * 
     * @var string segment unit
     */
    // protected static $segment_unit =  'chars';
    const SEGMENT_UNIT =  'chars';

    /**
     * Segment length
     * indicates how many chars or digits will be in the location`s segment.
     * 
     * @var integer segment unit
     */
    // protected static $segment_length =  3;
    const SEGMENT_LENGTH =  3;


    /**
     * Get the separator of node location
     * 
     * @return string the separator
     */
    // public static function separator()
    // {
    //     return config('tree.location_separator') ?? static::$separator;
    // }

    /**
     * Get the segment unit config
     * 
     * @return string the unit 
     */
    // public static function segmentUnit()
    // {
    //     return config('tree.location_segment_unit') ?? static::$segment_unit;
    // }

    /**
     * Get the segment length config
     * 
     * @return string the unit 
     */
    // public static function segmentLength()
    // {
    //     return config('tree.location_segment_length') ?? static::$segment_length;
    // }

    /**
     * get Regular Expressions Ranges for locations
     *
     * it will return `0-9` if the location unit config is set to `digits`
     * Or it will return `a-z` if the location unit config is set to `chars`
     *
     * @return string '0-9' | 'a-z'
     */
    public static function rangeREGEXP()
    {
        return static::SEGMENT_UNIT === 'chars' ? 'a-z' : '0-9';
    }

    /*
    |--------------------------------------------------------------------------
    | get REGEXP pattern for getting the root nodein this tree
    |--------------------------------------------------------------------------
    | The root node does not has any separator in its location.
    |
    | @return String  
    */
    public static function rootREGEXP()
    {
        // ^[a-z]{3}$    it means that the location does not has separator.
        // and the lengeth has to the length of segment as defined
        return '^'.static::segmentREGEXP().'$';
    }

    /**
     * get REGEXP pattern for the location`s segment
     * 
     * @return string regexp for one segment 
     */
    public static function segmentREGEXP()
    {
        // [a-z]{3}    where 3 is changable depending on configs
        // [0-9]{3}
        return '['.static::rangeREGEXP().']{'.static::SEGMENT_LENGTH.'}';
    }

    /**
     * Get the REGEX pattern for the children of given location
     * Start with `location` then a separator and then string without separator 
     *
     * @return string REGEXP pattern
     */
    public static function childrenREGEXP($location)
    {
        // ^aaa\.[^\.]+$
        return '^'.$location.static::SEPARATOR.'[^\\'.static::SEPARATOR.']+$';
    }

    /**
     * Get the REGEX pattern for the siblings of the given location
     * including the given location
     * Start with `location` then a separator and then string without separator 
     *
     * @return string REGEXP pattern
     */
    public static function withSiblingsREGEXP($location)
    {
         // ^fatherlocation\.charsWithoutSeparator+$
         // EX: `^aaa\.[^\.]+$`
        return '^'.static::convertLocationToPattern(static::father($location)).'\\'.static::SEPARATOR.'[^\\'.static::SEPARATOR.']+$';
        
    }

    /**
     * get REGEXP pattern for validating node location
     * to check if a location is correct style of chars or digits depending on configs
     * 
     * @return string regexp pattern  
     */
    protected static function locationValidationREGEXP()
    {
        // "/^[a-z]{3}(\.[a-z]{3})*$/"
        // "/^[0-9]{3}(\.[0-9]{3})*$/"
        return '/^'.static::segmentREGEXP().'(\\'.static::SEPARATOR.static::segmentREGEXP().')*$/';
    }

    /**
     * Convert the Given location to REGEXP pattern
     * 
     * @param Boolen to cover the pattern or not
     * example aaa.bbb = (^aaa\.bbb$)
     * @return REGEXP pattern
     */
    public static function convertLocationToPattern(string $location, Bool $covered = false)
    {
        $locationPattern = str_replace(static::SEPARATOR, '\\'.static::SEPARATOR, $location);
        return $covered ? '(^'.$locationPattern.'$)' : $locationPattern;
    }

    /**
     * Validating the node location unit
     *
     * determines if the node location is chars or digits
     * it could be one of ['chars' | 'digits']
     * 
     * @throws App\MHF\Tree\Exceptions\TreeException
     * @return boolean
     */
    public static function validateSegmentUnit()
    {
        if(static::SEGMENT_UNIT !== 'chars' && static::SEGMENT_UNIT !== 'digits'){
            throw new TreeException("Error. The location segment unit have to be 'chars' or 'digitd' ", 1);            
        }
        return true; 
    }
    /**
     * Validating the node location
     *
     * determines if the node location is valide and has the correct style
     * of set of chars or digits followed by separator and so on.
     * 
     * @param string $location ocation to validate
     * @throws App\MHF\Tree\Exceptions\TreeException
     * @return Boolean | TreeException
     */
    public static function validate($location)
    {
        if(!preg_match(static::locationValidationREGEXP(), $location)){
            throw new TreeException("Error Location ".$location." is not valid location in this tree", 1);
            
        }
        return true;
    }

    /**
     * First segment will be set of `0` or set of `a`
     * Dependeing on config value of location_segment_unit
     * And the length of segment that will be generated dependes 
     * on config value $location_segment_length
     * 
     * @return string first possible segment
     */
    public static function firstPossibleSegment()
    {
        $cipher = static::SEGMENT_UNIT == 'chars' ? 'a' : '0';
        // `aaa+++` or `000+++`
        return str_pad('', static::SEGMENT_LENGTH, $cipher);
    }
    /**
     * Last segment will be set of `9` or set of `z`
     * Dependeing on config value of location_segment_unit
     * And the length of segment that will be generated dependes 
     * on config value $location_segment_length
     * 
     * @return string last possible segment
     */
    public static function lastPossibleSegment()
    {
        $cipher = static::SEGMENT_UNIT == 'chars' ? 'z' : '9';
        // `zzz+++` or `999+++`
        return str_pad('', static::SEGMENT_LENGTH, $cipher);
    }

    /**
     * get REGEXP pattern for retriving specific generation in this tree.
     *
     *  ^[a-z]{3}(\.[a-z]{3}){number}$
     * @return string Regexp pattern
     */
    public static function singleGenerationREGEXP($generation_number = 1)
    {
        return '^'.static::segmentREGEXP().'(\\'.static::SEPARATOR.static::segmentREGEXP().'){'.($generation_number-1).'}$';
    }

    /**
     * get REGEXP pattern for multi generations
     * from the given location generation to 
     * the generation that given as secound parameter
     * 
     * @return string REGEXP pattern
     */
    public static function multiGenerationsREGEXP(String $location, int $generations = 1)
    {
        // ^aaa(\.[a-z]{3}){0,2}$   where 2 is changable depending on varibale $generations
        $pattern = '^'.$location().'(\\'.static::SEPARATOR.static::segmentREGEXP().'){0,'.($generations-1).'}$';
        
        return $pattern;
    }

    /**
     * Detrmine if the given location is the root
     * 
     * @param string $location
     * @return boolean
     */
    public static function isRoot($location)
    {
        return $location === static::firstPossibleSegment() ? true : false;
    }

    /**
     * Detrmine if the given location is segment
     * and has no separator
     * 
     * @param string $location
     * @return boolean
     */
    public static function isSegment($location)
    {
        // check the location that pointer indicating to
        return strpos($location, static::SEPARATOR) === false ? true : false;
    }

    /**
     * Getting all locations from the given location
     * to the root location
     *
     * aa.bb.cc.dd.ee ==> [aa.bb.cc.dd.ee, aa.bb.cc.dd, aa.bb.cc, aa.bb, aa]
     * @param string $location
     * @param array $locations
     * @return array
     */
    public static function allLocationsToRoot(string $location, array $locations =[])
    {
        if (static::isRoot($location)) {
            array_push($locations, $location);
            return $locations;
        }
        array_push($locations, $location);
        return static::allLocationsToRoot(static::father($location), $locations);
    }

    /**
     * Get all locations on the same branch from the current node
     * to the root. fx. aa.bb.cc.dd.ee ==> [aa.bb.cc.dd.ee, aa.bb.cc.dd, aa.bb.cc, aa.bb, aa]
     * 
     * @param string $location
     * @param array $locations
     * @return array
     */
    public static function getLocationsFromTopBranchToRoot($location, $locations = [])
    {
        return static::allLocationsToRoot($location, $locations);
    }

    /**
     * Get the last segment of node location: section that is after the last separator
     * 
     * @param string $location
     * @return string
     */
    public static function lastSegment($location)
    {
        if(static::isSegment($location)){
            return $location;
        }
        return substr($location, strrpos($location, static::SEPARATOR)+1);
    }

    /**
     * Get the location of the father of the given node
     * separate location of current node by last "."
     *
     * @param string $location
     * @return string | Null
     */
    public static function father($location)
    {
        // return father of node that pointer is indicating to
        return substr($location, 0, strrpos($location, static::SEPARATOR));
    }

    /**
     * Get the location of the Grandfather of the given node
     * 
     * @param string $location
     * @return string | Null
     */
    public static function grandfather($location)
    {
        // if location is only one or two segment like: `aa` or `aa.aa`
        if(substr_count($location, static::SEPARATOR) <=1){
            return null;
        }
        $segments = explode(static::SEPARATOR, $location);
        // remove the current node segment and the father segment
        array_pop($segments);
        array_pop($segments);
        // return grandfather location of node that pointer is indicating to
        return implode(static::SEPARATOR, $segments);
    }

    /**
     * Get the location of the given ancestor of the given location
     *
     * @param string $location
     * @param int ancestor    number like: father=1, grandfather=2, grandgrandfather=3 etc...
     * @return string location of given ancestor
     */
    public static function ancestor(string $location, int $ancestor)
    {   
        // if ancestor not found
        if(substr_count($location, static::SEPARATOR) < $ancestor){
            return null;
        }
        $segments = explode(static::SEPARATOR, $location);
        // remove segments from current to ancestor 
        for($i = 1; $i<=$ancestor; $i++)
            array_pop($segments);
        // return grandfather location of node that pointer is indicating to
        return implode(static::SEPARATOR, $segments);
    }

    /**
     * Make the first child of the node that the pointer is indicating to.
     *
     * @param string $location
     * @return string the first child location
     */
    public static function firstChild($location)
    {
        return $location.static::SEPARATOR.static::firstPossibleSegment();
    }
    /**
     * Make the first child of the node that the pointer is indicating to.
     *
     * @param string $location
     * @return string the first child location
     */
    public static function lastChild($location)
    {
        return $location.static::SEPARATOR.static::lastPossibleSegment();
    }

    /**
     * Increase the segment one step
     * Depending on wich micanism the location segments are generated [chars | digits]
     * 
     * Ex: abf ----> abg
     * Ex: 045 ----> 046
     * 
     * @param string $location_segment
     * @return string
     */
    public static function incrementSegment($location_segment)
    {
        ++$location_segment;
        // Check if the segment is zzz..  or 999..
        // if it is the last allowed segment
        if(strlen($location_segment) > static::SEGMENT_LENGTH){
            return null;
        }
        // the segment is set of chars like: `abc`
        if(static::SEGMENT_UNIT === 'chars'){
            return $location_segment;
        }
        // the segment is set of numbers like: `354`
        return str_pad($location_segment, static::SEGMENT_LENGTH, '0', STR_PAD_LEFT);
    }

    /** 
     * Generate the location after the location that pointer is indicating to
     * 
     * Note: Here it does not ditacte if the the next location allready exists or not
     * 
     * @return String new location
     */

    public static function generateNextLocation($location)
    {
        $last_segment = static::lastSegment($location);
        // 
        if($last_segment === static::lastPossibleSegment()){
            return null;
        }
        $new_segment = static::incrementSegment($last_segment);

        return static::father($location).static::SEPARATOR.$new_segment;
    }

    /**
     * Next sibling of given location
     * 
     * @param string $location
     * @return string new location wich is new sibling to the given location
     */
    public static function nextSibling($location)
    {
        $last_segment = static::lastSegment($location);
        // 
        if($last_segment === static::lastPossibleSegment()){
            return null;
        }
        $new_segment = static::incrementSegment($last_segment);

        return static::father($location).static::SEPARATOR.$new_segment;
    }

    /**
     * Indicates wich generation the given node location is
     *
     * @param string $location
     * @return int | NULL
     */
    public static function generation($location)
    {
        static::validate($location);
        return substr_count($location, static::SEPARATOR) + 1;
    }

    /**
     * Determine if the location_2 is son of location_1
     *
     * @param string $location_1
     * @param string $location_2
     * @return boolean
     */
    public static function areFatherAndSon($location_1, $location_2){
        if($location_2 === null)
            return false;

        $patern = '#^'.$location_1.'\\'.static::SEPARATOR.'#';
        if(preg_match($patern, $location_2) AND ($location_1 !== $location_2)){
            return true;
        }
        return false;
    }

    /**
     * Determine if the given nodes are Siblings [ node and the next_node in nodes array ]
     *
     * @param App\Node $node
     * @param App\Node $next_node
     * @return Boolean
     */
    public static function areSiblings($location_1, $location_2){
        if($location_2 === null || $location_1 == $location_2)
            return false;
        $node_pos      = strrpos($location_1, Location::SEPARATOR);
        $node_next_pos = strrpos($location_2, Location::SEPARATOR);

        $node_father      = substr($location_1, 0, $node_pos);
        $node_next_father = substr($location_2, 0, $node_next_pos);

        if($node_father === $node_next_father){
            return true;
        }
        return false;
    }
}