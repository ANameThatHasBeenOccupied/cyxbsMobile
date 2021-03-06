<?php

namespace Admin\Controller;

use Think\Controller;

use Home\Controller\EditController as edit;

class ArticleController extends Controller
{
        private $operate = array(
                'delete'  => 'delete',
                'recover' => 'articleState',
                'lock'    => 'articleState',
                'unlock'  => 'articleState'
        );

        protected static $article_table = array(
                1               => 'news',
                2               => 'news',
                3               => 'news',
                4               => 'news',
                5               => 'articles',
                6               => 'notices',
        );

        protected static $article_type = array(
                1               => '重邮新闻',
                2               => '教务在线',
                3               => '学术讲座',
                4               => '校务公告',
                5               => '哔哔叨叨',
                6               => '公告',
                7               => '话题',
        );

        private $_state = array(
                'delete' => 0,
                'normal' => 1,
                'lock'   => 2
        );
        protected $_status = array();

        public function _initialize()
        {

            //初始化
            $this->_status = array(
                    'recover' => array('before_state'=>$this->_state['delete'],'after_state'=>$this->_state['normal']),
                    'unlock' => array('before_state'=>$this->_state['lock'],'after_state'=>$this->_state['normal']),
                    'lock' => array('before_state'=>$this->_state['normal'],'after_state'=>$this->_state['lock']),
                    'delete' => array('before_state'=>array($this->_state['normal'], $this->_state['lock']),'after_state'=>$this->_state['delete']),
            );
        }
        
        public static function getType()
        {
                return self::$article_type;
        }


        public function operate()
        {
                $operate = I('post.operate');
                $data = I('post.data');
                if (!isset($operate) || !isset($data)) {
                        returnJson(801);
                }

                $action =   empty($this->operate[$operate])
                                                ? $operate : $this->operate[$operate];

                if (!method_exists($this, $action)) {
                        returnJson(403);
                }

                foreach ($data as $key => &$value) {
                        $result = call_user_func(array($this, $action), $operate, $value);
                        if (!is_null($result)) {
                                $value['result'] = $result;
                        }
                }
                returnJson(200, '', $data);
        }

        /**
         * 修改数据库里的属性
         * @param  string $operate 操作名
         * @param  array $data    数据
         * @return bool          是否成功
         */
        public function articleState($operate, $data)
        {
                if (empty($operate) || empty($data)) {
                        return false;
                }
                $table = self::$article_table[$data['type_id']];
                $id = $data['id'];

                if (empty($id) || empty($table)) {
                        return false;
                }

                $Data = new DataController();

                $operate = strtolower($operate);

                //操作不存在,返回false
                if (empty($this->_status[$operate])) {
                        return false;
                } else {
                        extract($this->_status[$operate]);
                        if (!is_array($before_state)) {
                                $before_state = explode(',', $before_state);
                        }
                }

                $data = $Data->parameter($data, $table);

                $article = M($table)->where($data)->find();

                if(!$article) {
                        return false;
                }

                if (in_array($article['state'], $before_state)) {
                        $article['state'] = $after_state;
                        //修改成功
                        $result = M($table)->save($article);
                        if ($result) {
                                return true;
                        }
                }

                return false;
        }
        /**
         * 删除文章
         * @param  string $operate 需要执行的操作
         * @param  array $data    对象的属性
         * @return bool          执行是否成功
         */
        protected function delete($operate, $data)
        {

                if(empty($data['id']) || empty($data['type_id'])) {
                        return false;
                }
                //表
                $table = self::$article_table[$data['type_id']];

                if (empty($table)) {
                        return false;
                }

                $article = M($table)->where($data)->find();

                if (empty($article)) {
                        return false;
                }

                //表里是否有state字段
                if(isset($article['state'])) {
                        if ($article['state'] != $this->_status['delete']['after_state']) {
                                return $this->articleState('delete', $data);
                        }
                }
                //硬删除
                $edit = new edit();
                return $edit->delete($data['id'], $data['type_id']);

        }

        public function getWriteType()
        {
            $data = array();
            foreach (self::$article_type as $key => $value) {
                if ($key > 4) {
                    $data[] = array('id'=>$key, 'text' => $value);
                }       
            }
            returnJson(200, '', $data);
        }

        public function getWriteTemplet()
        {
            $templet = array(
                    'title' => array('type' => 'text', 'text'=>'标题', 'name'=>'title', 'placeholder'=> '请输入标题'),
                    'content' => array('type' => 'text', 'text' => '内容', 'name'=> 'content', 'placeholder'=> '详细内容..'),
                    'photo' => array('type' => 'file', 'text' => '上传图片', 'name'=>'photo_src'),
                    'keyword' => array('type' => 'text', 'text'=>'话题', 'name' => 'keyword', 'placeholder' => '话题..')
            );

            $display = array(

                '5' =>    array(
                                'title', 
                                'content',
                                'photo', 
                            ),
                '6' =>  array(
                                'title', 
                                'content',
                                'photo', 
                            ),
                '7' => array(
                                'title', 
                                'keyword',
                                'content',
                                'photo',
                            ),
            );

            $type_id = I('type_id');
            $data = $display[$type_id];
            if (empty($data)) {
                returnJson(404);
            }
           returnJson(200, '', $data);
        }

}
