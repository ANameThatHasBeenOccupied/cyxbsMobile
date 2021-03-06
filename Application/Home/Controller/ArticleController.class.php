<?php
namespace Home\Controller;

use Think\Controller;

class ArticleController extends BaseController {
    protected    $newsList = array('jwzx','cyxw','xsjz','xwgg');
    public function index(){
        $article = D("hotarticles");
        $articles = $article->relation(true)->select();
    }

    public function searchContent(){
        $article    = D('articles');
        $type_id = I('post.type_id');
        $stunum = I('post.stuNum');
        $article_id = I('post.article_id');
        if($stunum == null || $article_id == null || $type_id == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        }else{
            $condition_user = array(
                "stunum" => $stunum
            );
            $condition_article = array(
                    'cyxbsmobile_articles.id'      => $article_id,
                    'cyxbsmobile_articles.type_id' => $type_id,

                );
            $content = $article->where($condition_article)->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')->field('cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src,cyxbsmobile_articles.thumbnail_src,cyxbsmobile_articles.content,cyxbsmobile_articles.type_id,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,cyxbsmobile_articles.like_num,cyxbsmobile_articles.remark_num,cyxbsmobile_users.photo_src as user_photo,cyxbsmobile_users.nickname')->select();
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $content
            );
            echo json_encode($info);
        }
    }

    public function searchTrends(){
        $stunum_other = I('post.stunum_other');
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        if($stunum_other == null){
            $stunum = I('post.stuNum');
        }else{
            $stunum = $stunum_other;
        }
        if($stunum == null){
            $info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
            echo json_encode($info);exit;
        }else{
            $condition_user = array(
                "stunum" => $stunum
            );
            $user = M('users');
            $user = $user->where($condition_user)->find();
            $article = D('articles');
            $condition_article = array(
                    'user_id' =>$user['id']
                );
            $content = $article->where($condition_article)->order('updated_time DESC')->limit($start,$size)->field('id,photo_src,thumbnail_src,content,type_id,created_time,updated_time,created_time,like_num,remark_num')->select();
            $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $content
            );
            echo json_encode($info);
        }
    }

    public function aboutMe(){
        $stunum = I('post.stuNum');
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        $remark = D('articleremarks');
        $user = M('users');
        $article = D('articles');
        $user_id = $user->where("stunum = '$stunum'")->find();
        $user_id = $user_id['id'];
        $sql = " SELECT 'remark' as type,cyxbsmobile_articleremarks.content as content,cyxbsmobile_articles.content as article_content ,cyxbsmobile_articles.thumbnail_src as article_photo_src ,cyxbsmobile_articleremarks.created_time,cyxbsmobile_articleremarks.article_id,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src
                FROM (cyxbsmobile_articleremarks JOIN cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id = cyxbsmobile_users.id)JOIN cyxbsmobile_articles
        ON  cyxbsmobile_articleremarks.article_id = cyxbsmobile_articles.id
         WHERE 
            cyxbsmobile_users.stunum != '$stunum' AND
            cyxbsmobile_articleremarks.article_id IN(
                SELECT id FROM cyxbsmobile_articles WHERE user_id = '$user_id' OR answer_user_id = '$user_id'
        ) UNION
        SELECT 'praise' as type,'' as content,cyxbsmobile_articles.content as article_content,cyxbsmobile_articles.thumbnail_src as article_photo_src,cyxbsmobile_articlepraises.created_time,cyxbsmobile_articlepraises.article_id,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src
        FROM (cyxbsmobile_articlepraises JOIN cyxbsmobile_users ON cyxbsmobile_articlepraises.stunum = cyxbsmobile_users.stunum )JOIN cyxbsmobile_articles
        ON cyxbsmobile_articlepraises.article_id = cyxbsmobile_articles.id
        WHERE 
            cyxbsmobile_users.stunum != '$stunum' AND
            cyxbsmobile_articlepraises.article_id IN(
                SELECT id FROM cyxbsmobile_articles WHERE user_id = '$user_id'
        ) 
            ORDER BY created_time DESC
            limit $start,$size
        ";
        $result = M('')->query($sql);
        $info = array(
                'status' => '200',
                "info"   => "success",
                'data'   => $result
            );
        echo json_encode($info);
    }

    /**
     * 添加文章
     */
    public function addArticle()
    {
        $data = I('post.');
        if(!$this->produceArticleInformation($data, true, $error)){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => $error,
                );
            echo json_encode($info,true);
            exit;
        }
        $user = M('users');
        $user_condition = array(
            "stunum" =>$data['stuNum']
        );
        // $thumbnail_src_array = I('post.thumbnail_src');
        // $photo_src_array = I('post.photo_src_array');
        // $thumbnail_src_array=explode(",",$thumbnail_src_array);
        // if(sizeof($thumbnail_src_array) > 10){
        //     $photo_src_array=explode(",",$photo_src_array);
        //     $photo_src_array=array_slice($photo_src_array,0,9);
        //     $photo_src_string = implode(',',$photo_src_array).",";
        //     $thumbnail_src_array=array_slice($thumbnail_src_array,0,9);
        //     $thumbnail_src_string = implode(',',$thumbnail_src_array).",";
        //     $data['thumbnail_src'] = $thumbnail_src_string;
        //     $data['photo_src'] = $$photo_src_string;
        // }
        $user_id = $user->where($user_condition)->find();
        if (!$user_id) {
            returnJson(404, 'error stuNum');
        }
        switch ($data['type_id']) {
            case 5:        
                $article  = D('articles');
                break;
            
            case 7:
                if(empty($data['keyword'])) {
                    returnJson(801);
                }
                $result = M('topic')->where(array('keyword' => $data['keyword']))->find();
                if (!$result) {
                    returnJson(801, 'this topic isn\'t exist');
                }
                $data['topic_id'] = $result['id'];
                $article = D('topicarticles');
                break;
            
            default:
            returnJson(801, 'error type_id');
        }
        $article_field = $article->getDbFields();
        
        foreach ($data as $key => $value) {
            if(!in_array($key, $article_field)){
                unset($data[$key]);
            }
        }
        
        $data['user_id'] = $user_id['id'];
        $data['created_time'] = date("Y-m-d H:i:s", time());
        $data['updated_time'] = date("Y-m-d H:i:s", time());
        $data['read_num'] = 0;
        $data['like_num'] = 0;
        $data['remark_num'] = 0;
        $article_check = $article->add($data);
        $hotarticles = M('hotarticles');
        $content = $data;
        $content['articletype_id'] = $data['type_id'];
        $content['article_id'] = $article_check;
        $hotarticles->add($content);
        if($article_check){
            $info = array(
                    'state' => 200,
                    'status' => 200,
                    'info'  => 'success',
                );
            echo json_encode($info,true);
            exit;
        }else{
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                );
            echo json_encode($info,true);
            exit;
        }
    }

    public function listNews(){
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;

        $articleType = D('articletypes');
        $article     = D('news');
        // $condition = array(
        //     'articletype_id' => $type
        // );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->limit($start,$size)->order('id DESC')->select();

        $praise  = M('articlepraises');
        $result = array();
        foreach($content as $key => $value){
            $praise_condition = array(
                "articletype_id"  => $value['articletype_id'],
                "article_id"      => $value['id'],
                "stunum"          => I('post.stuNum')
            );
            $praise_exist = $praise->where($praise_condition)->find();
            if($praise_exist){
                $value['is_my_like'] = true;
            }else{
                $value['is_my_like'] = false;
            }
            array_push($result,$value);
        }


        $info = array(
                'status' => '200',
                "page"   => $page,
                'data'   => $result
        );
        echo json_encode($info);
    }

    public function listArticle(){
        $type = I('post.type_id');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        if($type == null){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $articleType = D('articletypes');
        $article     = D('articles');
        $condition = array(
            'type_id' => $type
        );
        // ->order('updated_time DESC')->limit($start,$start+15)->field('user_id,title,id,photo_src,thumbnail_src,type_id,content,updated_time,created_time,like_num,remark_num')
        $content = $article->where($condition)->join('cyxbsmobile_users ON cyxbsmobile_articles.user_id = cyxbsmobile_users.id')->field('cyxbsmobile_articles.title,cyxbsmobile_articles.id,cyxbsmobile_articles.photo_src as article_photo_src,cyxbsmobile_articles.thumbnail_src as article_thumbnail_src,cyxbsmobile_articles.type_id,cyxbsmobile_articles.content,cyxbsmobile_articles.updated_time,cyxbsmobile_articles.created_time,like_num,remark_num,cyxbsmobile_users.stunum,cyxbsmobile_users.nickname,cyxbsmobile_users.photo_src,cyxbsmobile_users.photo_thumbnail_src  ')->limit($start,$size)->order('updated_time DESC')->select();
        $praise  = M('articlepraises');
        $result = array();
        foreach($content as $key => $value){
            $praise_condition = array(
                "articletype_id" => $value['type_id'],
                "article_id"      => $value['id'],
                "stunum"          => I('post.stuNum')
            );
            $praise_exist = $praise->where($praise_condition)->find();
            if($praise_exist){
                $value['is_my_like'] = true;
            }else{
                $value['is_my_like'] = false;
            }
            array_push($result,$value);
        }


        $info = array(
                'status' => '200',
                "page"   => $page,
                'data'   => $result
        );
        echo json_encode($info);
    }

    

    public function searchHotArticle(){
        $hotArticle = D("hotarticles");
        $article = D("articles");
        $praise  = M('articlepraises');
        $user = D('users');
        $page = I('post.page');
        $size = I('post.size');
        $page = empty($page) ? 0 : $page;
        $size = empty($size) ? 15 : $size;
        $start = $page*$size;
        $info = array();
        $now_date = date("Y-m-d H:i:s",mktime(0,0,0,date("m"),date("d")-7,date("Y")));
        if($page == 0){
            $notice = M('notices');
            $data_notice_condition = array(
                    "created_time" => array('GT',$now_date),
                    "state"        => 1,
                );
            $data_notice   = $notice->where($data_notice_condition)->order('created_time')->select();
            $site = $_SERVER["SERVER_NAME"];
            foreach ($data_notice as $key => $value) {
                $praise_condition = array(
                    "articletype_id" => "6",
                    "article_id"      => $data_notice[$key]['id'],
                    "stunum"          => I('post.stuNum')
                );
                $praise_exist = $praise->where($praise_condition)->find();
                if($praise_exist){
                    $exist = true;
                }else{
                    $exist = false;
                }
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $data_notice[$key]['id'],
                                'type'      => "notice",
                                'type_id'   => "6",
                                'article_id'=> $value['id'],
                                'user_id'   => "0",
                                'nick_name' => "红岩网校工作站",
                                'user_head' => "http://".$site.'/cyxbsMobile/Public/HONGY.jpg',
                                'time'      => $value['created_time'],
                                'content'   => array(
                                                    "content" =>$value['content'],
                                                ),
                                'img'       => array(
                                                'img_small_src' => $value['thumbnail_src'],
                                                'img_src' => $value['photo_src'],
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
               array_push($info,$now_info);
            }
        }
        $info = array_reverse($info);
        $data = $hotArticle->where("created_time > '$now_date'")->order('(remark_num*2+like_num) DESC,updated_time DESC')->limit($start,$size)->relation(true)->select();
        foreach ($data as $key => $value) {
            $condiion_articles = array(
                "id" => $data[$key]['article_id'],
                );
            if($data[$key]['Articletypes']['typename'] == null){

            }elseif($data[$key]['articletype_id'] < 5){
                $article = M("news");
                $praise  = M('articlepraises');
                $praise_condition = array(
                    "articletype_id"  => $data[$key]['articletype_id'],
                    "article_id"      => $data[$key]['article_id'],
                    "stunum"          => I('post.stuNum')
                );
                $praise_exist = $praise->where($praise_condition)->find();
                if($praise_exist){
                    $exist = true;
                }else{
                    $exist = false;
                }
                $articles = $article->where($condiion_articles)->find();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $data[$key]['id'],
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'type_id'   => $data[$key]['articletype_id'],
                                'article_id'   => $data[$key]['article_id'],
                                'user_id'   => "",
                                'user_name' => "",
                                'user_head' => "",
                                'time'      => $articles['date'],
                                'content'   => $articles,
                                'img'       => array(
                                                'img_small_src' => "",
                                                'img_src' => "",
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
                array_push($info,$now_info);
            }else{
                $article = D('articles');
                $praise  = M('articlepraises');
                $articlePhoto  = M('articlephoto');
                $articles = $article->where($condiion_articles)->relation(true)->find();
                $praise_condition = array(
                    "articletypes_id" => $data[$key]['articletypes_id'],
                    "article_id"      => $data[$key]['article_id'],
                    "stunum"          => I('post.stuNum')
                );
                $praise_exist = $praise->where($praise_condition)->find();
                if($praise_exist){
                    $exist = true;
                }else{
                    $exist = false;
                }
                $photo_content = $articlePhoto->where($photo_condition)->select();
                $now_info = array(
                    'status' => 200,
                    'page'   => $page,
                    'data'   =>array(
                                'id'        => $data[$key]['id'],
                                'type'      => $data[$key]['Articletypes']['typename'],
                                'type_id'   => $data[$key]['articletype_id'],
                                'article_id'=>$data[$key]['article_id'],
                                'user_id'   => $articles['Users']['stunum'],
                                'nick_name' => $articles['Users']['nickname'],
                                'user_head' => $articles['Users']['photo_src'],
                                'time'      => $articles['created_time'],
                                'content'   => array(
                                                    "content" => $articles['content'],
                                                ),
                                'img'       => array(
                                                'img_small_src' => $articles['thumbnail_src'],
                                                'img_src' => $articles['photo_src'],
                                            ),
                                'like_num'  => $value['like_num'],
                                'remark_num'=> $value['remark_num'],
                                "is_my_Like"=> $exist,
                            ),
                );
                array_push($info,$now_info);
            }
        }

        echo json_encode($info);
    }

    public function postNotice(){
        $administrators = M('administrators');
        $users = M('users');
        $user_condition = array(
                "stunum" =>I('post.stuNum')
            );
        $user_id = $users->where($user_condition)->find();

        $admin_condition = array(
                "user_id" => $user_id['id']
            );
        $admin = $administrators->where($admin_condition)->find();
        if(I('post.keyword') == 'cyxbsmobile' && $admin){
            $notice = M('notices');
            $content = array(
                "user_id"      => $user_id['id'],
                "created_time" => date("Y-m-d H:i:s", time()),
                "updated_time" => date("Y-m-d H:i:s", time()),
                "content"      => I('post.content'),
                "title"        => I('post.title'),
                "photo_src"    => I('post.photo_src'),
                "thumbnail_src"=> I('post.thumbnail_src')
            );
            $notice->add($content);
            $info = array(
                    'state' => 200,
                    'status' => 200,
                );
            echo json_encode($info,true);exit;
        }else{
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
    }

    public function deleteNotice(){
        $administrators = M('administrators');
        $users = M('users');
        $notice = M('notices');
        $user_condition = array(
                "stunum" => I('post.stuNum')
            );
        $user_id = $users->where($user_condition)->find();

        $admin_condition = array(
                "user_id" => $user_id['id']
            );
        $admin = $administrators->where($admin_condition)->find();
        $notice_condition = array(
                "id" => I('post.notice_id'),
                "state" => 1
            );
        $notice_exist = $notice->where($notice_condition)->find();
        if(I('post.keyword') == 'cyxbsmobile' && $admin && I('post.notice_id')&&$notice_exist){
            $notice_info = array(
                    "state" => 0
                );
            $notice->where($notice_condition)->data($notice_info)->save();
            $info = array(
                    'state' => 200,
                    'status' => 200,
                );
            echo json_encode($info,true);exit;
        }else{
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
    }

    public function praise()
    {
    	if(I('post.id') == null){
    		$info = array(
                "status" => 801,
                "info"   => "invalid parameter"
            );
    	}else{
    		$praise = M('articlepraise');
    		$condition = array(
    			"stunum"     => I('post.stuNum'),
    			"article_id" => I('post.id')
    		);
    		$goal = $condition->where($condition)->find();
    		if($goal){
    			$info = array(
	                "status" => 404,
	                "info"   => "已赞"
	            );
    		}else{
    			$praise->add($condition);
    		}
    	}

    	echo json_encode($info,true);
    }


    /**
     * 添加话题
     */
    public function addTopic()
    {
        $infomation = I('post.');
  
        if ($this->produceTopicInformation($information, true, $error)) {
            returnJson(404, $error);
        }

        if ($information['official'] == 'redrock') {
            //官方发起话题
            if (!$this->is_admin($information['stuNum'])) {
                returnJson(403, '你还不是管理员哟');
            }
            $information['user_id'] = 0;            //以红岩网校工作站名义创建的话题
        } else {
            //个人发起话题
            $user = M('users')->where('stunum=\'%s\'', $information['stuNum'])->find();
            if(!$user) {
                returnJson(403, '你还不是掌邮的用户');
            }
            $information['user_id'] = $user['id'];
        }
        $information['created_time'] = date("Y-m-d H:i:s");
        $information['updated_time'] = date("Y-m-d H:i:s");
        $result = M('topics')->add($information);
        if ($result) {
            returnJson(200);
        } else {
            returnJson(404);
        }

    }


    public function produceTopicInformation(&$information, $is_add=false, &$error='')
    {
        if(empty($information)) {
            return $information;
        }
        
        foreach ($information as $field => $value) {
        
            switch ($field) {
                
                case 'keyword':            
                case 'content':
                case 'title':
                    if(empty($value) || !forbidwordCheck($value, $field)) {
                        $error = $field."'s value is error";    
                        return false;
                    }
                    break;
                
                case 'topic_id':
                    if (!is_numeric($value) && !$is_add) {
                        $error = $field."'s value is error";
                        return false;
                    }
                    break;
                case 'id':
                case 'state':
                    unset($information[$field]);
                    break;
                break; 
            }
        }

      
        if(empty($keyword)) {
            $error = "keyword is not matched";
            return false;
        }

        return true;
    }

    /**
     * 对上传文章和给改文章的信息进行处理
     * @param  array  &$information post过来的信息
     * @param  boolean $is_add      是否是添加文章
     * @param  string  &$error      错误信息
     * @return bool                信息是否正确
     */
    public function produceArticleInformation(&$information, $is_add=false, &$error='')
    {
        if(empty($information)) {
            $error = "empty information";
            return false;
        }
        //对各个字段进行处理
        foreach ($information as $field => &$value) {
            switch ($field) {

                case 'title':
                case 'content':
                     if(empty($value) || !forbidwordCheck($value, $field)) {
                            $error = $field."'s value is error";    
                            return false;
                        }
                    break;
                
                case 'type_id':
                    if ($value < 5 || $value == 6 ){
                        $error = "this type is not allowed";
                        return false;
                    }
                    break;

                case 'article_id':
                    if (!is_numeric($value) && !$is_add) {
                        $error = $field."'s value is error";
                        return false;
                    }
                    break;

                case 'stuNum':
                    if (strlen($value) !== 10) {
                        $error = 'errpr student number';
                        return false;
                    }
                    break;
                case 'id':
                case 'state':
                    unset($information[$field]);
                    break;
            } 
        }
        if (empty($information['type_id']) || empty($information['stuNum'])) {
            $error = 'Don\'t match the type_id in the information';
            return false;
        }
        //当不为添加的时候，文章的id必须存在
        if ($is_add) {
            $information['photo_src'] = isset($information['photo_src']) ? $information['photo_src'] : '';
            $information['thumbnail_src'] = isset($information['thumbnail_src']) ? $information['thumbnail_src'] : '';
        } else {
            //当不为添加的时候，文章的id必须存在
            if(empty($information['article_id'])) {
                $error = 'Can\'t match the article_id';
                return false;
            }
        }
        return true;   
    }


}