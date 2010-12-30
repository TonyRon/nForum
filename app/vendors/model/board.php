<?php
/****************************************************
 * FileName: app/vendors/model/board.php
 * Author: xw <wei.xiao.bupt@gmail.com>
 *****************************************************/
App::import("vendor", array("model/overload", "model/threads", "model/iwidget", "inc/pagination"));

/**
 * class Board is a board in kbs
 * Board is the element of collection,Board can be used to construct
 * Section only when board is directory
 * bid = -1 is favor dir
 * The base structure of $_info is
 *     array(12) {
 *        ["BID"]=> int(75)
 *        ["NAME"]=> string(5) "Flash"
 *        ["BM"]=> string(6) "xw2423"
 *        ["FLAG"]=> int(512)
 *        ["DESC"]=> string(8)
 *        ["CLASS"]=> string(6)
 *        ["SECNUM"]=> string(1) "6"
 *        ["LEVEL"]=> int(0)  //the right
 *        ["GROUP"]=> int(0)  //the parent dir id is it is in dir
 *        ["CURRENTUSERS"]=> int(1)
 *        ["LASTPOST"]=> int(43254)
 *        ["ARTCNT"]=> int(5)
 *         ["NPOS"]=> position of favor
 *         ["UNREAD"]=>
 *  }
 *
 * @extends OverloadObject
 * @implements Pageable
 * @implements iWidget
 * @author xw
 */
class Board extends OverloadObject implements Pageable, iWidget{
    
    /**
     * dir mode of board
     * @var string 
     */
    public static $NORMAL = 0;
    public static $DIGEST = 1;
    public static $THREAD = 2;
    public static $MARK = 3;
    public static $DELETED = 4;
    public static $JUNK = 5;
    public static $ORIGIN = 6;
    public static $AUTHOR = 7;
    public static $TITLE = 8;
    public static $ZHIDING = 9;

    /**
     * number of threads in board
     * @var int $threadsNum
     */
    public $threadsNum = 0;

    /**
     * board mode
     * @var int $_mode
     */
    private $_mode = 2;

    /**
     * function getInstance get a Board object from board name
     *
     * @param mixed $mixed int bid|string boardname
     * @return Board object
     * @static
     * @access public
     * @throws BoardNullException
     */
    public static function getInstance($mixed){
        $info = array();
        if(is_int($mixed) || preg_match("/^\d+$/", $mixed)){
            $ret = bbs_getboard_bid(intval($mixed), $info);
        }else{
            $ret = bbs_getboard_nforum($mixed, $info);
        }
        if($ret == 0)
            throw new BoardNullException();
        return new Board($info);
    }

    /**
     * function search match with board name
     *
     * @param $name 
     * @return array
     * @static
     * @access public
     */
    public static function search($name){
        $boards = array();
        if (!bbs_searchboard(trim($name),0,$boards)) 
            return array();
        $ret = array();
        foreach($boards as $v){
            try{
                $ret[] = Board::getInstance($v['NAME']);
            }catch(BoardNullException $e){
            }
        }
        return $ret;
    }

    /**
     * function __contstruct()
     * do not use this to get a object
     *
     * @param array $info
     * @param int $pos
     * @return Board
     * @access public
     * @throws BoardNullException
     */
    public function __construct($info){
        if(!is_array($info))
            throw new BoardNullException();
        $this->_info = $info;
        $this->threadsNum = bbs_getthreadnum($this->BID);
        if($this->threadsNum < 0)
             $this->threadsNum = 0;
    }

    public function __get($name){
        switch($name){
            case 'BM':
                if($this->isDir())
                    return "[����Ŀ¼]";
                if($this->_info["$name"] === "" && !$this->isDir())
                    return "����������";
                break;
            case 'CLASS':
                if($this->isDir())
                    return "����Ŀ¼";
                break;
            case 'ARTCNT':
            case 'TOTAL':
            case 'CURRENTUSERS':
                if($this->isDir())
                    return 0;
                break;
        }
        return parent::__get($name);
    }

    public function getTotalNum(){
        if($this->_mode === self::$THREAD)
            return $this->threadsNum;
        else
            return $this->getTypeNum($this->_mode);
    }

    public function getRecord($start, $num){
        if($this->_mode === self::$THREAD)
            return $this->getThreads($start - 1, $num);
        else{
            return array_reverse($this->getTypeArticles($start - 1, $num, $this->_mode));
        }
    }

    public function wGetName(){
        return "board-" . $this->NAME;
    }

    public function wGetTitle(){
        return array("text"=>$this->DESC, "url"=>"/board/".$this->NAME);
    }

    public function wGetList(){
        App::import('Sanitize');
        $ret = array();
        $articles = array_reverse($this->getTypeArticles(0, 10, self::$ORIGIN));
        if(!empty($articles)){
            foreach($articles as $v){
                $ret[] = array("text"=>Sanitize::html($v->TITLE), "url"=>"/article/{$this->NAME}/{$v->GROUPID}");
            }
            return array("s"=>"w-list-line", "v"=>$ret);    
        }else{
            return array("s"=>"w-list-line", "v"=>array(array("text" => ECode::msg(ECode::$BOARD_NOTHREADS), "url" => "")));    
        }
    }

    public function wGetTime(){
        $file = 'boards/' . $this->NAME . '/.ORIGIN';
        if(!file_exists($file))
            return time();
        return filemtime($file);
    }

    /**
     * function getThreads get a range of threads
     * it will contain the top threads, the sequence will change when there is a new post
     * index start in zero
     *
     * @param int $start
     * @param int $num
     * @return array
     * @access public
     */
    public function getThreads($start,$num){
        if($this->threadsNum == 0)
            return array();
        $arr = bbs_getthreads($this->NAME, $start, $num, 1);
        if(!is_array($arr))
            return array();
        foreach($arr as &$v){
            $v = new Threads($v, $this);
        }
        return $arr;
    }

    /**
     * function getLastThreads get the last threads of board not top article
     *
     * @return Threads
     * @access public
     */
    public function getLastThreads(){
        $threads = $this->getThreads(0, 15);
        if(!is_array($threads))
            return null;
        foreach($threads as $v){
            if(!$v->FIRST->isTop()){
                return $v;
            }
        }
        return null;
    }

    /**
     * function getTypeArticles get a range of articles via $type
     * $NORMAL get articles like in telnet
     * $DIGEST digest articles
     * $DELETED delete articles
     * $JUNK junk articles
     * $ORIGIN same threads mode
     * $ZHIDING top articles
     *
     * @param int $start
     * @param int $num
     * @param int $type
     * @return array
     * @access public
     */
    public function getTypeArticles($start, $num, $type = null){
        if(is_null($type))
            $type = self::$NORMAL;
        $totalNum = $this->getTypeNum($type);
        $start = $totalNum - $num - $start + 1;
        $ret = bbs_getarticles($this->NAME, $start, $num, $type);
        if(!is_array($ret))
            return array();
        foreach($ret as $k => &$v){
            $v = new Article($v, $this, $k + $start);
        }
        return $ret;
    }

    /**
     * function setMode change current board mode 
     *
     * @param int $mode
     * @return void
     * @access public
     */
    public function setMode($mode){
        $o = new ReflectionClass('Board');
        if(in_array((int)$mode, $o->getStaticProperties())){
            $this->_mode = $mode;
        }
    }

    /**
     * function getMode get current board mode
     *
     * @return int
     * @access public
     */
    public function getMode(){
        return $this->_mode;    
    }

    /**
     * function hasReadPerm whether board can read 
     *
     * @param User $user
     * @return boolean true|false
     * @access public
     */
    public function hasReadPerm($user){
        if(bbs_checkreadperm($user->uid, $this->BID) == 0)
            return false;
        return true;
    }

    /**
     * function hasPostPerm whether board can post
     *
     * @param User $user
     * @return boolean true|false
     * @access public
     */
    public function hasPostPerm($user){
        if(bbs_checkpostperm($user->uid, $this->BID) == 0)
            return false;
        return true;
    }

    /**
     * function getTodayNum get the number that post today
     *
     * @return int
     * @access public
     */
    public function getTodayNum(){
        $num = bbs_get_today_article_num($this->NAME);
        return ($num >= 0)?$num : 0;
    }

    /**
     * function getTypeNum get the article number of $type
     *
     * @param int $type
     * @return int
     * @access public
     */
    public function getTypeNum($type = null){
        if(is_null($type))
            $type = self::$NORMAL;
        return bbs_countarticles($this->BID, $type);
    }

    /**
     * function getElitePath
     *
     * @return string
     * @access public
     */
    public function getElitePath(){
        $ret = bbs_getannpath($this->NAME);
        if($ret === false)
            return "";
        $ret = preg_replace("/^0Announce\//", "", $ret);
        return $ret;
    }
    
    /**
     * function setOnBoard set current user on this board
     *
     * @return null
     * @access public
     */
    public function setOnBoard(){
        bbs_set_onboard($this->BID, 1);
    }
    
    public function isReadOnly(){
        return $this->_checkFlag(BBS_BOARD_READONLY);
    }

    public function isAttach(){
        return $this->_checkFlag(BBS_BOARD_ATTACH);
    }

    public function isNoReply(){
        return $this->_checkFlag(BBS_BOARD_NOREPLY);
    }

    public function isAnony(){
        return $this->_checkFlag(BBS_BOARD_ANNONY);
    }

    public function isTmplPost(){
        return $this->_checkFlag(BBS_BOARD_TMP_POST);
    }

    public function isNormal(){
        return (bbs_normalboard($this->NAME) == 1);
    }

    /**
     * function isDir check whether is directory board
     *
     * @return boolean
     * @access public
     */
    public function isDir(){
        //normal dir board || fav dir
        return $this->_checkFlag(BBS_BOARD_GROUP) || $this->BID == -1;
    }

    /**
     * function getDir get the parent board
     *
     * @return Board
     * @access public
     */
    public function getDir(){
        try{
            if($this->GROUP != 0)
                return self::getInstance($this->GROUP);
            return null;
        }catch(BoardNullException $e){
            return null;
        }
        
    }

    private function _checkFlag($flag){
        return ($this->FLAG & $flag)?true:false;
    }
}
class BoardNullException extends Exception {}
?>