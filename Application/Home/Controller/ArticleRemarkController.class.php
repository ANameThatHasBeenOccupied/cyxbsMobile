<?php
namespace Home\Controller;
use Think\Controller;

class ArticleRemarkController extends BaseController {
    public function getRemark(){
        $page = I('post.page');
        $size = I('post.size');
        $remark_id = I('post.article_id');
        $type_id   = I('post.type_id');
        if($remark_id == null||$type_id == null){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        }
        $remark = M('articleremarks');
        $condition = array(
            "article_id" => $remark_id,
        );
        
        $remark = $remark
                    ->join('cyxbsmobile_users ON cyxbsmobile_articleremarks.user_id =cyxbsmobile_users.id')
                    ->where("cyxbsmobile_articleremarks.article_id = '$remark_id' and cyxbsmobile_articleremarks.articletypes_id = '$type_id'")
                    ->order('created_time DESC')
                    ->field('stunum,nickname,username,photo_src,photo_thumbnail_src,cyxbsmobile_articleremarks.created_time,content,answer_user_id');
        //如果设置page将分页返回回复
        if($page != null ){
            $page = empty($page) ? 0 : $page;
            $size = empty($size) ? 15 : $size;
            $start = $page*$size;
            $remark->limit($start,$size);
        }

        $result = $remark->select();
       	$info = array(
                    'state' => 200,
                    'status' => 200,
                    'data'  => $result,
                );
        echo json_encode($info,true);
    }

    public function postRemarks(){
        $content = I('post.content');
        $article_id = I('post.article_id');
        $type_id    = I('post.type_id');
        $user = M('users');
        $answer_user_id = I('post.answer_user_id')?I('post.answer_user_id'):0;
        if($answer_user_id){
            $answer_user_condition = array(
                'stunum' => $answer_user_id
            );
            $user_id = $user->where($answer_user_condition)->field('id')->find();
            if($user_id){
                $answer_user_id = $user_id['id'];
            }
        }
        if($content == null || $type_id == null || $article_id == null){
            $info = array(
                    'state' => 801,
                    'status' => 801,
                    'info'  => 'invalid parameter',
                    'data'  => array(),
                );
            echo json_encode($info,true);
            exit;
        } else {
            $remark = M('articleremarks');
            $condition = array(
                    "stunum"  => I('post.stuNum')
                );
            $condition_article = array(
                        "id"  => $article_id,
                    );
            //the remark writor's id
            $user_id = $user->where($condition)->field('id')->find();

            if ($type_id == 6) {
                $notice = M('notices');
                $goal = $notice->where($condition_article)->setInc('remark_num');
                if(!$goal){
                    $info = array(
                        'state' => 801,
                        'status' => 801,
                        'info'  => 'invalid parameter',
                        'data'  => array(),
                    );
                    echo json_encode($info,true);
                    exit;
                }
                $notcie_update = array(
                    "updated_time" =>date("Y-m-d H:i:s", time()),
                );
                $notice->where($condition_article)->data($notcie_update)->save();
            } else {
                //是不是自己回答
                $is_self_remark = false;
                
                if($type_id > 4){
                    $article = M('articles');
                    $goal = $article->where($condition_article)->setInc('remark_num');
                    if(!$goal){
                        $info = array(
                            'state' => 801,
                            'status' => 801,
                            'info'  => 'invalid parameter',
                            'data'  => array(),
                        );
                        echo json_encode($info,true);
                        exit;
                    }
                    $article_update = array(
                        "updated_time"=>date("Y-m-d H:i:s", time()),
                    );
                    $article->where($condition_article)->data($article_update)->save();
                    $author_id = $article->where($condition_article)->field('user_id')->find();
                    if ($author_id['user_id'] == $user_id['id']) {
                        $is_self_remark = true;
                    }
                }else{
                    $news = M('news');
                    $condition_news = array(
                        "id"  => $article_id,
                    );
                    $goal = $news->where($condition_news)->setInc('remark_num');
                    if(!$goal){
                        $info = array(
                            'state' => 801,
                            'status' => 801,
                            'info'  => 'invalid parameter',
                            'data'  => array(),
                        );
                        echo json_encode($info,true);
                        exit;
                    }
                    $article_update = array(
                        "updated_time"=>date("Y-m-d H:i:s", time()),
                    );
                    $news->where($condition_article)->data($article_update)->save();
                }

                //热门排序
                $hotarticles = M('hotarticles');
                $hotarticle_condition = array(
                    "article_id" => $article_id,
                    "articletype_id" =>$type_id
                );
                $hotarticles->where($hotarticle_condition)->setInc('remark_num');
                //如果为自己回答的，加一
                if ($is_self_remark) {
                    $hotarticles->where($hotarticle_condition)->setInc('self_remark_num');
                }
            }

            $content = array(
                "content"         => $content,
                "created_time"    =>  date("Y-m-d H:i:s", time()),
                "user_id"         =>  $user_id['id'],
                "article_id"      => $article_id,
                "articletypes_id" => $type_id,
                "answer_user_id"  => $answer_user_id
            );
            $remark->add($content);
            $info = array(
                    'state' => 200,
                    'status' => 200,
                );
            echo json_encode($info,true);
        }
    }

}